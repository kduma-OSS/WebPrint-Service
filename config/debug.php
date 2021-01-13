<?php

return [
    'dir' => !env('DEBUG_OUTPUT_DIRECTORY')
        ? getcwd()
        : (
            \Illuminate\Support\Str::startsWith(env('DEBUG_OUTPUT_DIRECTORY') , '/')
                ? env('DEBUG_OUTPUT_DIRECTORY')
                : getcwd() . '/' . env('DEBUG_OUTPUT_DIRECTORY')
        ),
];
