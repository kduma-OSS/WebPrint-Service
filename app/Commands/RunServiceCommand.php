<?php

namespace App\Commands;

use App\Api\Exceptions\JobLockedException;
use App\Api\Exceptions\JobNotFoundException;
use App\Api\WebPrintHostInterface;
use App\Exceptions\PrintFailedException;
use App\Exceptions\ProtocolNotSupportedException;
use App\Exceptions\TypeNotSupportedException;
use App\PollingCalculators\PollTimeCalculatorInterface;
use App\Printers\PrinterProtocolFactory;
use LaravelZero\Framework\Commands\Command;
use ValueError;

class RunServiceCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'watch';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Watch for new jobs on remote server';

    /**
     * Execute the console command.
     *
     * @param  WebPrintHostInterface  $api
     * @param  PollTimeCalculatorInterface  $ptc
     * @return never
     */
    public function handle(
        WebPrintHostInterface $api,
        PollTimeCalculatorInterface $ptc,
        PrinterProtocolFactory $protocolFactory,
    ): never {
        while (true) {
            $this->line('Checking for new jobs...');
            $new_jobs = $api->checkForNewJobs($ptc->shouldLongPoll());

            if (! $new_jobs) {
                $this->line('No new jobs.');
            } else {
                $this->info('Found '.count($new_jobs).' job(s).');
            }

            foreach ($new_jobs as $job_id) {
                $this->processJob($api, $protocolFactory, $job_id);
            }

            $ptc->markAttempt(count($new_jobs));
            usleep($ptc->getDelay() * 1000);
        }
    }

    private function processJob(
        WebPrintHostInterface $api,
        PrinterProtocolFactory $protocolFactory,
        string $id
    ): void {
        $this->info('Processing job with id='.$id);

        try {
            $job = $api->getJob($id);

            [
                'protocol' => $protocol,
                'options' => $options,
            ] = $protocolFactory->parse($job->printer);

            $handler = app()->make($protocol);

            $handler->printJob($job, $options);

            $api->markJobAsDone($id);
            $this->info('Job processed!');
        } catch (JobNotFoundException) {
            $this->error('Job not found.');
        } catch (JobLockedException) {
            $this->error('Job is locked.');
        } catch (ProtocolNotSupportedException) {
            $this->error('Print protocol not supported.');
            $api->markJobAsFailed($id, 'ProtocolNotSupportedException');
        } catch (PrintFailedException $e) {
            $this->error('Print failed: '.$e->getMessage());
            $api->markJobAsFailed($id, 'PrintFailedException');
        } catch (TypeNotSupportedException) {
            $this->error('Content type not supported.');
            $api->markJobAsFailed($id, 'TypeNotSupportedException');
        } catch (ValueError $e) {
            $this->error($e->getMessage());
            $api->markJobAsFailed($id, 'ValueError');
        }
    }
}
