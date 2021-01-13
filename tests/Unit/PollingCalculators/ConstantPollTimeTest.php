<?php

use App\PollingCalculators\ConstantPollTime;

test('ConstantPollTime', function () {
    $sut = new ConstantPollTime(1000);

    expect($sut->getDelay())->toBe(1000);

    $sut->markAttempt(0);

    expect($sut->getDelay())->toBe(1000);

    $sut->markAttempt(100);

    expect($sut->getDelay())->toBe(1000);
});
