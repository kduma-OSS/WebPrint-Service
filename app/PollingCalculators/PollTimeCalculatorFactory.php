<?php

namespace App\PollingCalculators;

class PollTimeCalculatorFactory
{
    /**
     * @throws \Exception
     */
    public function fromString(string $configuration): PollTimeCalculatorInterface
    {
        ['mode' => $mode, 'options' => $options] = $this->parseConfigString($configuration);

        return match ($mode) {
            'constant' => $this->makeConstantPollTime($options),
            'dynamic' => $this->makeDynamicPollTime($options),
            default => throw new \Exception('Unknown polling mode: '.$mode),
        };
    }

    /**
     * @param  string  $configuration
     * @return array
     */
    protected function parseConfigString(string $configuration): array
    {
        $url = parse_url($configuration);
        parse_str($url['query'] ?? '', $query);

        return [
            'mode' => $url['path'] ?? null,
            'options' => $query,
        ];
    }

    /**
     * @param  array  $options
     * @return ConstantPollTime
     */
    protected function makeConstantPollTime(array $options): ConstantPollTime
    {
        $options = $this->validateOptions($options, [
            'delay' => [
                'type' => 'int',
                'required' => false,
                'default' => 1000,
                'min' => 0,
            ],
            'poll' => [
                'type' => 'bool',
                'required' => false,
                'default' => true,
            ],
        ]);

        return new ConstantPollTime(
            delay: $options['delay'],
            longPoll: $options['poll'],
        );
    }

    /**
     * @param  array  $options
     * @return DynamicPollTime
     */
    protected function makeDynamicPollTime(array $options): DynamicPollTime
    {
        $options = $this->validateOptions($options, [
            'initial_delay' => [
                'type' => 'int',
                'required' => false,
                'default' => 1000,
                'min' => 1,
            ],
            'max_delay' => [
                'type' => 'int',
                'required' => false,
                'default' => 60000,
                'min' => 1,
            ],
            'multiplier' => [
                'type' => 'float',
                'required' => false,
                'default' => 1.02,
                'min' => 1,
            ],
            'backoff' => [
                'type' => 'float',
                'required' => false,
                'default' => 0,
                'min' => 0,
            ],
            'warmup' => [
                'type' => 'float',
                'required' => false,
                'default' => 0,
                'min' => 0,
            ],
        ]);

        return new DynamicPollTime(
            minimum: $options['initial_delay'],
            maximum: $options['max_delay'],
            increase_factor: $options['multiplier'],
            decrease_factor: $options['backoff'],
            warmup_factor: $options['warmup'],
        );
    }

    protected function validateOptions(array $options, array $rules): array
    {
        $rules = collect($rules);

        $options = collect($options)
            ->only($rules->keys())
            ->map(function ($value, $key) use ($rules) {
                $rule = $rules->get($key);

                if ($rule['type'] === 'int' && is_numeric($value)) {
                    $value = (int) $value;
                } elseif ($rule['type'] === 'float' && is_numeric($value)) {
                    $value = (float) $value;
                } elseif ($rule['type'] === 'bool' && (is_bool($value) || $value === 'true' || $value === 'false' || $value === '1' || $value === '0')) {
                    $value = match ($value) {
                        'true', '1' => true,
                        'false', '0' => false,
                        default => $value,
                    };
                } elseif ($rule['type'] === 'string') {
                    $value = (string) $value;
                } else {
                    throw new \ValueError(
                        sprintf(
                            'Invalid value for option "%s". Value "%s" is not of type "%s".',
                            $key,
                            $value,
                            $rule['type']
                        )
                    );
                }

                if ($rule['type'] === 'int' || $rule['type'] === 'float') {
                    if (($rule['min'] ?? null) !== null && $value < $rule['min']) {
                        throw new \ValueError(
                            sprintf(
                                'Invalid value for option "%s". Value "%s" is less than minimum "%s".',
                                $key,
                                $value,
                                $rule['min']
                            )
                        );
                    }
                }

                return $value;
            });

        $rules
            ->filter(fn ($rule) => $rule['required'] ?? false)
            ->keys()
            ->diff($options->keys())
            ->tap(function ($missing) {
                if ($missing->isNotEmpty()) {
                    throw new \ValueError(
                        sprintf(
                            'Missing required options: %s',
                            $missing->join(', ')
                        )
                    );
                }
            });

        $rules
            ->except($options->keys())
            ->each(function ($rule, $key) use ($options) {
                $options[$key] = $rule['default'] ?? null;
            });

        return $options->toArray();
    }
}
