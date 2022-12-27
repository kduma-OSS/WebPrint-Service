<?php

use App\PollingCalculators\DynamicPollTime;

test('DynamicPollTime', function () {
    $sut = new DynamicPollTime(1000, 100000, 2, 0.5);

    expect($sut->getDelay())->toBe(1000);

    $sut->markAttempt(0);

    expect($sut->getDelay())->toBe(2000);

    $sut->markAttempt(0);
    $sut->markAttempt(0);

    expect($sut->getDelay())->toBe(8000);

    $sut->markAttempt(2);

    expect($sut->getDelay())->toBe(2000);
});
