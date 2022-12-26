<?php

namespace App\Api;

use App\Api\Exceptions\ApiErrorException;
use App\Api\Exceptions\JobNotFoundException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use const null;

class HttpWebPrintHost implements WebPrintHostInterface
{
    private string $endpoint;

    private string $token;

    public function __construct()
    {
        $this->endpoint = Str::finish(config('api.endpoint'), '/');
        $this->token = config('api.key');
    }

    public function checkForNewJobs(bool $long_polling = true): array
    {
        $response = Http::timeout(120)
            ->withToken($this->token)
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->get(
                $this->endpoint.'jobs', [
                    'long_poll' => (int) $long_polling,
                ]
            );

        if ($response->status() == 404) {
            throw new JobNotFoundException();
        }

        if (! $response->successful()) {
            throw new ApiErrorException($response->body(), $response->status());
        }

        return $response->json();
    }

    public function getJob(string $id): JobModel
    {
        $response = Http::timeout(60)
            ->withToken($this->token)
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->get($this->endpoint.'jobs/'.$id);

        $job = $response->json();

        $response = $this->updateStatus($id, 'printing');
        if (! $response->successful()) {
            throw new ApiErrorException($response->body(), $response->status());
        }

        try {
            $content = match ($job['content_type']) {
                'plain' => $job['content'],
                'base64' => gzuncompress(base64_decode($job['content'])),
                'file' => file_get_contents($job['content']),
                default => throw new ApiErrorException('Unknown content type received: '.$job['content_type'])
            };
        } catch (ApiErrorException $e) {
            $response = $this->updateStatus($id, 'failed', $e->getMessage());
            if (! $response->successful()) {
                throw new ApiErrorException($response->body(), $response->status());
            }

            throw $e;
        }

        return new JobModel(
            $job['ulid'],
            $job['name'],
            $job['printer']['uri'],
            $job['options'] ?? [],
            $job['ppd'] ? 'ppd' : 'raw',
            $job['file_name'],
            $content
        );
    }

    protected function updateStatus(string $id, string $status, ?string $message = null): Response
    {
        return Http::timeout(60)
            ->withToken($this->token)
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->put(
                $this->endpoint.'jobs/'.$id,
                [
                    'status' => $status,
                    'status_message' => $message,
                ]
            );
    }

    public function markJobAsDone(string $id): void
    {
        $response = $this->updateStatus($id, 'finished');

        if (! $response->successful()) {
            throw new ApiErrorException($response->body(), $response->status());
        }
    }

    public function markJobAsFailed(string $id, string $error): void
    {
        $response = $this->updateStatus($id, 'failed', $error);

        if (! $response->successful()) {
            throw new ApiErrorException($response->body(), $response->status());
        }
    }
}
