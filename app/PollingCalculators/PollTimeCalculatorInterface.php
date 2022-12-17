<?php

namespace App\PollingCalculators;

interface PollTimeCalculatorInterface
{
    /**
     * Gets delay before next attempt to get new print jobs
     */
    public function getDelay(): int;

    /**
     * Receive information from runner about last call results
     *
     * @param  int  $new_jobs_received count of received jobs (0 if none)
     */
    public function markAttempt(int $new_jobs_received): void;

    /**
     * Receive information from runner about warming up job queue
     */
    public function warmUp(): void;

    /**
     * Returns if long polling should be used for next call
     */
    public function shouldLongPoll(): bool;
}
