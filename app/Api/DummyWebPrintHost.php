<?php

namespace App\Api;

class DummyWebPrintHost implements WebPrintHostInterface
{
    public function checkForNewJobs(bool $long_polling = true): array
    {
        static $count = 0;

        if ($count++ == 0) {
            return ['fake_id'];
        }

        return [];
    }

    public function getJob(string $id): JobModel
    {
        return new JobModel(
            $id,
            'Test Job',
            'cups://TM_T88V',
            [],
            'ppd',
            'test.txt',
            date('Y-m-d H:i:s')
        );
    }

    public function markJobAsDone(string $id): void
    {
        // TODO: Implement markJobAsDone() method.
    }

    /**
     * {@inheritDoc}
     */
    public function markJobAsFailed(string $id, string $error): void
    {
        // TODO: Implement markJobAsFailed() method.
    }
}
