<?php

namespace App\Printers\Protocols;

use App\Api\JobModel;
use App\Exceptions\PrintFailedException;
use App\Exceptions\TypeNotSupportedException;

class RawProtocol implements PrinterProtocolInterface
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
                if ($options['timeout'] ?? false) {
                    $socket = @fsockopen($options['host'], $options['port'] ?? 9100, $errno, $errstr);
                } else {
                    $socket = @fsockopen($options['host'], $options['port'] ?? 9100, $errno, $errstr, (float) $options['timeout']);
                }

                if ($socket === false) {
                    throw new PrintFailedException('Cannot initialise NetworkPrintConnector: '.$errstr);
                }

                fwrite($socket, $job->content);
                fwrite($socket, chr(0));
                fclose($socket);
                $socket = false;

                break;

            default:
                throw new TypeNotSupportedException;
        }
    }
}
