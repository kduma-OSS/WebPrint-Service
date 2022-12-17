<?php

namespace App\Printers\Protocols;

use App\Api\JobModel;

interface PrinterProtocolInterface
{
    /**
     * @return string[]
     */
    public function supportedTypes(): array;

    public function print(JobModel $job): void;
}
