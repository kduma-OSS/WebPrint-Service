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
     *
     * @throws PrintFailedException
     * @throws TypeNotSupportedException
     */
    public function print(JobModel $job): void
    {
        $url = parse_url($job->printer);
        parse_str($url['query'] ?? '', $query);

        switch ($job->type) {
            case 'raw':
                if ($query['timeout'] ?? false) {
                    $socket = @fsockopen($url['host'], $url['port'] ?? 9100, $errno, $errstr);
                } else {
                    $socket = @fsockopen($url['host'], $url['port'] ?? 9100, $errno, $errstr, (float) $query['timeout']);
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
