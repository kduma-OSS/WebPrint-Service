<?php

namespace App\Printers\Protocols;

use App\Api\JobModel;
use App\Exceptions\PrintFailedException;
use App\Exceptions\TypeNotSupportedException;
use ErrorException;
use KDuma\LPD\Client\Configuration;
use KDuma\LPD\Client\Exceptions\InvalidJobException;
use KDuma\LPD\Client\Exceptions\PrintErrorException;
use KDuma\LPD\Client\Jobs\TextJob;
use KDuma\LPD\Client\PrintService;

class LpdProtocol implements PrinterProtocolInterface
{
    public function supportedTypes(): array
    {
        return ['raw'];
    }

    /**
     * @param  JobModel  $job
     * @param  array  $options
     *
     * @throws PrintFailedException
     * @throws TypeNotSupportedException
     */
    public function printJob(JobModel $job, array $options): void
    {
        switch ($job->type) {
            case 'raw':
                $queue = isset($options['path']) && trim($options['path'], '\\/') ? trim($options['path'], '\\/') : null;

                $print_service = new PrintService(new Configuration(
                    $options['host'],
                    $queue ?? Configuration::DEFAULT_QUEUE_NAME,
                    $options['port'] ?? Configuration::LPD_DEFAULT_PORT,
                    $options['timeout'] ?? 60
                ));

                $tries = $options['tries'] ?? 1;
                do {
                    $tries--;

                    try {
                        $print_service->sendJob(new TextJob($job->content));
                        $tries = 0;
                    } catch (InvalidJobException $e) {
                        throw new PrintFailedException($e->getMessage());
                    } catch (ErrorException|PrintErrorException $e) {
                        if ($tries) {
                            usleep(100000);
                            dump($e->getMessage());
                        } else {
                            throw new PrintFailedException($e->getMessage());
                        }
                    }
                } while ($tries);

                break;
            default:
                throw new TypeNotSupportedException;
        }
    }
}
