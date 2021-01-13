<?php


namespace App\Api;


class JobModel
{
    public function __construct(
        public string $id,
        public string $name,
        public string $printer,
        public array $options,
        public string $type,
        public string $file_name,
        public mixed $content,
    )
    {}
}
