<?php

namespace App\Commands;

use App\Api\Exceptions\JobLockedException;
use App\Api\WebPrintHostInterface;
use App\Api\Exceptions\JobNotFoundException;
use App\Exceptions\PrintFailedException;
use App\Exceptions\ProtocolNotSupportedException;
use App\Exceptions\TypeNotSupportedException;
use App\PollingCalculators\PollTimeCalculatorInterface;
use App\Printers\Protocols\CupsProtocol;
use App\Printers\Protocols\DebugProtocol;
use App\Printers\Protocols\LpdProtocol;
use App\Printers\Protocols\PrinterProtocolInterface;
use App\Printers\Protocols\RawProtocol;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

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
     * @param WebPrintHostInterface       $api
     * @param PollTimeCalculatorInterface $ptc
     *
     * @return mixed
     */
    public function handle(WebPrintHostInterface $api, PollTimeCalculatorInterface $ptc)
    {
        while(true){
            $this->line('Checking for new jobs...');
            $new_jobs = $api->checkForNewJobs();

            if(!$new_jobs) {
                $this->line('No new jobs.');
            } else {
                $this->info('Found '.count($new_jobs).' job(s).');
            }

            foreach ($new_jobs as $job_id) {
                $this->processJob($api, $job_id);
            }

            $ptc->markAttempt(count($new_jobs));
            usleep($ptc->getDelay() * 1000);
        }
    }

    private function processJob(WebPrintHostInterface $api, string $id)
    {
        $this->info('Processing job with id='.$id);

        try {
            $job = $api->getJob($id);

            /** @var PrinterProtocolInterface $handler */
            $handler = app()->make(match (strtolower(parse_url($job->printer, PHP_URL_SCHEME))) {
                'lpd' => LpdProtocol::class,
                'socket' => RawProtocol::class,
                'cups' => CupsProtocol::class,
                'debug' => DebugProtocol::class,
                default => throw new ProtocolNotSupportedException
            });

            $handler->print($job);

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
        }
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
