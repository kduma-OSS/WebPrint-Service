<?php

use App\PollingCalculators\ConstantPollTime;
use App\PollingCalculators\DynamicPollTime;
use App\PollingCalculators\PollTimeCalculatorFactory;

test('creating ConstantPollTime with PollTimeCalculatorFactory', function ($configuration_string, $expected_delay, $expected_poll) {
    $sut = new PollTimeCalculatorFactory();

    expect($sut->fromString($configuration_string))
        ->toBeInstanceOf(ConstantPollTime::class)
        ->toHaveProperty('delay', $expected_delay)
        ->toHaveProperty('longPoll', $expected_poll);
})->with([
    'defaults' => ['constant', 1000, true],
    'with delay' => ['constant?delay=250', 250, true],
    'with delay and polling enabled' => ['constant?delay=2000&poll=true', 2000, true],
    'with delay and polling disabled' => ['constant?delay=750&poll=false', 750, false],
]);

test('creating DynamicPollTime with PollTimeCalculatorFactory', function ($configuration_string, $expected_minimum, $expected_maximum, $expected_increase_factor, $expected_decrease_factor, $expected_warmup_factor) {
    $sut = new PollTimeCalculatorFactory();

    expect($sut->fromString($configuration_string))
        ->toBeInstanceOf(DynamicPollTime::class)
        ->toHaveProperty('minimum', $expected_minimum)
        ->toHaveProperty('maximum', $expected_maximum)
        ->toHaveProperty('increase_factor', $expected_increase_factor)
        ->toHaveProperty('decrease_factor', $expected_decrease_factor)
        ->toHaveProperty('warmup_factor', $expected_warmup_factor);
})->with([
    'defaults' => ['dynamic', 1000, 60000, 1.02, 0, 0],
    'with all options set to defaults' => ['dynamic?initial_delay=1000&max_delay=60000&multiplier=1.02&backoff=0&warmup=0', 1000, 60000, 1.02, 0, 0],
    'with all options set to non defaults' => ['dynamic?initial_delay=500&max_delay=100000&multiplier=2&backoff=0.5&warmup=0', 500, 100000, 2, 0.5, 0],
]);

test('can\'t create PollTimeCalculator\'s using PollTimeCalculatorFactory with invalid parameters', function ($configuration_string, $expected_exception, $expected_message) {
    $sut = new PollTimeCalculatorFactory();

    expect(fn () => $sut->fromString($configuration_string))
        ->toThrow($expected_exception, $expected_message);
})->with([
    'constant with negative delay' => ['constant?delay=-100', ValueError::class, 'Invalid value for option "delay". Value "-100" is less than minimum "0".'],
    'constant with numeric polling' => ['constant?poll=100', ValueError::class, 'Invalid value for option "poll". Value "100" is not of type "bool".'],
    'constant with string polling' => ['constant?poll=nope', ValueError::class, 'Invalid value for option "poll". Value "nope" is not of type "bool".'],

    'dynamic with negative initial_delay' => ['dynamic?initial_delay=-1000', ValueError::class, 'Invalid value for option "initial_delay". Value "-1000" is less than minimum "1".'],
    'dynamic with negative max_delay' => ['dynamic?max_delay=-60000', ValueError::class, 'Invalid value for option "max_delay". Value "-60000" is less than minimum "1".'],
    'dynamic with negative multiplier' => ['dynamic?multiplier=-1', ValueError::class, 'Invalid value for option "multiplier". Value "-1" is less than minimum "1".'],
    'dynamic with negative backoff' => ['dynamic?backoff=-1', ValueError::class, 'Invalid value for option "backoff". Value "-1" is less than minimum "0".'],
    'dynamic with negative warmup' => ['dynamic?warmup=-1', ValueError::class, 'Invalid value for option "warmup". Value "-1" is less than minimum "0".'],

    'dynamic with string initial_delay' => ['dynamic?initial_delay=abcd', ValueError::class, 'Invalid value for option "initial_delay". Value "abcd" is not of type "int".'],
    'dynamic with string max_delay' => ['dynamic?max_delay=abcd', ValueError::class, 'Invalid value for option "max_delay". Value "abcd" is not of type "int".'],
    'dynamic with string multiplier' => ['dynamic?multiplier=abcd', ValueError::class, 'Invalid value for option "multiplier". Value "abcd" is not of type "float".'],
    'dynamic with string backoff' => ['dynamic?backoff=abcd', ValueError::class, 'Invalid value for option "backoff". Value "abcd" is not of type "float".'],
    'dynamic with string warmup' => ['dynamic?warmup=abcd', ValueError::class, 'Invalid value for option "warmup". Value "abcd" is not of type "float".'],
]);
