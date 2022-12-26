<?php

namespace App\Printers;

use App\Exceptions\ProtocolNotSupportedException;
use App\Printers\Protocols\CupsProtocol;
use App\Printers\Protocols\DebugProtocol;
use App\Printers\Protocols\LpdProtocol;
use App\Printers\Protocols\RawProtocol;
use ValueError;

class PrinterProtocolFactory
{
    /**
     * @throws ProtocolNotSupportedException
     * @throws ValueError
     */
    public function parse(string $configuration): array
    {
        [
            'mode' => $mode,
            'options' => $options
        ] = $this->parseConfigString($configuration);

        return match ($mode) {
            'lpd' => $this->makeLpdProtocol($options),
            'socket' => $this->makeSocketProtocol($options),
            'cups' => $this->makeCupsProtocol($options),
            'debug' => $this->makeDebugProtocol($options),
            default => throw new ProtocolNotSupportedException,
        };
    }

    protected function parseConfigString(string $configuration): array
    {
        $url = parse_url($configuration);
        parse_str($url['query'] ?? '', $query);

        if (($url['host'] ?? null) != null) {
            $query['host'] = $url['host'];
        }

        if (($url['port'] ?? null) != null) {
            $query['port'] = $url['port'];
        }

        if (($url['path'] ?? null) != null) {
            $query['path'] = $url['path'];
        }

        return [
            'mode' => $url['scheme'] ?? null,
            'options' => $query,
        ];
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
                    throw new ValueError(
                        sprintf(
                            'Invalid value for option "%s". Value "%s" is not of type "%s".',
                            $key,
                            $value,
                            $rule['type'])
                    );
                }

                if ($rule['type'] === 'int' || $rule['type'] === 'float') {
                    if (($rule['min'] ?? null) !== null && $value < $rule['min']) {
                        throw new ValueError(
                            sprintf(
                                'Invalid value for option "%s". Value "%s" is less than minimum "%s".',
                                $key,
                                $value,
                                $rule['min'])
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
                    throw new ValueError(
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

    protected function makeDebugProtocol(array $options): array
    {
        $options = $this->validateOptions($options, []);

        return [
            'protocol' => DebugProtocol::class,
            'options' => $options,
        ];
    }

    protected function makeLpdProtocol(array $options): array
    {
        $options = $this->validateOptions($options, [
            'path' => [
                'type' => 'string',
                'required' => false,
                'default' => 'default',
            ],
            'host' => [
                'type' => 'string',
                'required' => true,
            ],
            'port' => [
                'type' => 'int',
                'required' => false,
                'default' => 515,
                'min' => 1,
            ],
            'timeout' => [
                'type' => 'int',
                'required' => false,
                'default' => 60,
                'min' => 1,
            ],
            'tries' => [
                'type' => 'int',
                'required' => false,
                'default' => 1,
                'min' => 1,
            ],
        ]);

        return [
            'protocol' => LpdProtocol::class,
            'options' => $options,
        ];
    }

    protected function makeSocketProtocol(array $options): array
    {
        $options = $this->validateOptions($options, [
            'host' => [
                'type' => 'string',
                'required' => true,
            ],
            'port' => [
                'type' => 'int',
                'required' => false,
                'default' => 9100,
                'min' => 1,
            ],
            'timeout' => [
                'type' => 'int',
                'required' => false,
                'default' => null,
                'min' => 1,
            ],
        ]);

        return [
            'protocol' => RawProtocol::class,
            'options' => $options,
        ];
    }

    protected function makeCupsProtocol(array $options): array
    {
        $options = $this->validateOptions($options, [
            'host' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);

        return [
            'protocol' => CupsProtocol::class,
            'options' => $options,
        ];
    }
}
