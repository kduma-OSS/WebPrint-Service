<?php

use App\Api\JobModel;
use App\Printers\Protocols\CupsProtocol;

function recordingCupsProtocol(): CupsProtocol
{
    return new class extends CupsProtocol
    {
        public array $commands = [];

        public string $content = '';

        protected function getCmdOutput($cmd): string
        {
            if ($cmd === 'lpstat -a') {
                return "fixture accepting requests\n";
            }
            $this->commands[] = $cmd;
            preg_match("/'([^']+)'$/", $cmd, $match);
            $this->content = file_get_contents($match[1]);

            return 'request id is fixture-1';
        }
    };
}

test('PPD preserves explicit false and true options alongside choice values', function () {
    $protocol = recordingCupsProtocol();
    $protocol->printJob(new JobModel('job', 'Test', 'cups://fixture', [
        'PageSize' => 'A4',
        'Duplex' => 'None',
        'MirrorPrint' => false,
        'fit-to-page' => true,
    ], 'ppd', 'label.pdf', 'prepared PDF bytes'), ['host' => 'fixture']);

    expect($protocol->commands)->toHaveCount(1)
        ->and($protocol->commands[0])->toContain("-o 'PageSize=A4'", "-o 'Duplex=None'", "-o 'MirrorPrint=False'", "-o 'fit-to-page=True'")
        ->and($protocol->content)->toBe('prepared PDF bytes');
});

test('RAW keeps the raw flag and does not apply PPD options', function () {
    $protocol = recordingCupsProtocol();
    $protocol->printJob(new JobModel('job', 'Test', 'cups://fixture', [
        'MirrorPrint' => false,
    ], 'raw', 'label.zpl', '^XA^XZ'), ['host' => 'fixture']);

    expect($protocol->commands)->toHaveCount(1)
        ->and($protocol->commands[0])->toContain("-o 'raw'")->not->toContain('MirrorPrint')
        ->and($protocol->content)->toBe('^XA^XZ');
});
