<?php

namespace App\Api;

class JobModel
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $printer,
        public readonly array $options,
        public readonly string $type,
        public readonly string $file_name,
        public readonly mixed $content,
    ) {
    }
}
