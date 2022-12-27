<?php

namespace App\PollingCalculators;

class ConstantPollTime implements PollTimeCalculatorInterface
{
    /**
     * ConstantPollTime constructor.
     *
     * @param  int  $delay Delay in miliseconds between each check of new print jobs
     * @param  bool  $longPoll If true, long polling will be used for requesting new jobs
     */
    public function __construct(
        public readonly int $delay,
        public readonly bool $longPoll = true
    ) {
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function shouldLongPoll(): bool
    {
        return $this->longPoll;
    }

    public function markAttempt(int $new_jobs_received): void
    {
        // do nothing.
    }

    public function warmUp(): void
    {
        // do nothing.
    }
}
