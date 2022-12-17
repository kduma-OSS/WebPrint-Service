<?php

namespace App\PollingCalculators;

class DynamicPollTime implements PollTimeCalculatorInterface
{
    protected float $current_delay;

    /**
     * DynamicPollTime constructor.
     *
     * @param  int  $minimum         Minimal delay
     * @param  int  $maximum         Maximum delay
     * @param  float  $increase_factor Increase factor
     * @param  float  $decrease_factor Decrease factor
     * @param  float  $warmup_factor   WarmUp factor
     */
    public function __construct(
        public readonly int $minimum = 1000,
        public readonly int $maximum = 60000,
        public readonly float $increase_factor = 1.02,
        public readonly float $decrease_factor = 0,
        public readonly float $warmup_factor = 0,
    ) {
        $this->current_delay = $this->minimum;
    }

    public function getDelay(): int
    {
        return (int) $this->current_delay;
    }

    public function markAttempt(int $new_jobs_received): void
    {
        if ($new_jobs_received == 0) {
            $this->current_delay = min($this->maximum, $this->current_delay * $this->increase_factor);
        } else {
            $this->current_delay = max($this->minimum, $this->current_delay * pow($this->decrease_factor, $new_jobs_received));
        }
    }

    public function warmUp(): void
    {
        $this->current_delay = max($this->minimum, $this->current_delay * $this->warmup_factor);
    }

    public function shouldLongPoll(): bool
    {
        return false;
    }
}
