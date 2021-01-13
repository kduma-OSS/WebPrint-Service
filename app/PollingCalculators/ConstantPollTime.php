<?php


namespace App\PollingCalculators;


class ConstantPollTime implements PollTimeCalculatorInterface
{
    /**
     * ConstantPollTime constructor.
     *
     * @param int $delay Delay in miliseconds between each check of new print jobs
     */
    public function __construct(protected int $delay)
    {
    }

    public function getDelay(): int
    {
        return $this->delay;
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
