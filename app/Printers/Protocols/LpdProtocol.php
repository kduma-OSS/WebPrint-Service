<?php


namespace App\Printers\Protocols;


use App\Api\JobModel;
use App\Exceptions\PrintFailedException;
use App\Exceptions\TypeNotSupportedException;
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
     * @param JobModel $job
     *
     * @throws PrintFailedException
     * @throws TypeNotSupportedException
     */
    public function print(JobModel $job): void
    {
        $url = parse_url($job->printer);
        parse_str($url['query'] ?? '', $query);

        switch ($job->type){
            case 'raw':
                $queue = isset($url['path'])  && trim($url['path'], '\\/') ? trim($url['path'], '\\/') : null;

                $print_service = new PrintService(new Configuration(
                    $url['host'],
                    $queue ?? Configuration::DEFAULT_QUEUE_NAME,
                    $url['port'] ?? Configuration::LPD_DEFAULT_PORT,
                    $query['timeout'] ?? 60
                ));

                $tries = $query['tries'] ?? 1;
                do {
                    $tries--;

                    try {
                        $print_service->sendJob(new TextJob($job->content));
                        $tries = 0;
                    } catch (InvalidJobException $e) {
                        throw new PrintFailedException($e->getMessage());
                    } catch (\ErrorException | PrintErrorException $e) {
                        if($tries) {
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
