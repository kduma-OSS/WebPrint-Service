<?php

namespace App\Printers\Protocols;

use App\Api\JobModel;
use Illuminate\Support\Str;

class DebugProtocol implements PrinterProtocolInterface
{
    public function supportedTypes(): array
    {
        return ['raw', 'ppd'];
    }

    public function printJob(JobModel $job, array $options): void
    {
        [$manifest_file, $content_file] = $this->getDirectories($job->id, $job->file_name);

        file_put_contents($manifest_file, json_encode([
            'id' => $job->id,
            'name' => $job->name,
            'printer' => $job->printer,
            'options' => $job->options,
            'type' => $job->type,
            'file_name' => $job->file_name,
        ], JSON_PRETTY_PRINT));
        file_put_contents($content_file, $job->content);

        echo sprintf("\nDebug: Print job stored at:\n - %s\n - %s\n\n", $manifest_file, $content_file);
    }

    protected function getDirectories(string $id, string $file_name)
    {
        $extension = Str::slug(pathinfo($file_name, PATHINFO_EXTENSION));
        if (! $extension) {
            $extension = 'txt';
        }

        $prefix = sprintf('%s/%s_%s_%s', config('debug.dir'), time(), $id, Str::slug(pathinfo($file_name, PATHINFO_FILENAME)));
        $counter = 0;

        $name = $prefix.'_manifest.txt';
        $contents = $prefix.'.'.$extension;
        while (file_exists($name)) {
            $name = sprintf('%s_%s_manifest.txt', $prefix, str_pad(++$counter, 4, '0', STR_PAD_LEFT));
            $contents = sprintf('%s_%s.%s', $prefix, str_pad($counter, 4, '0', STR_PAD_LEFT), $extension);
        }

        return [$name, $contents];
    }
}
