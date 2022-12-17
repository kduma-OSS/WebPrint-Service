<?php

use Illuminate\Support\Str;

return [
    'dir' => ! env('DEBUG_OUTPUT_DIRECTORY')
        ? getcwd()
        : (
            Str::startsWith(env('DEBUG_OUTPUT_DIRECTORY'), '/')
                ? env('DEBUG_OUTPUT_DIRECTORY')
                : getcwd().'/'.env('DEBUG_OUTPUT_DIRECTORY')
        ),
];
