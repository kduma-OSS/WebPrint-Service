<?php


namespace App\Api;


use App\Api\Exceptions\ApiErrorException;
use App\Api\Exceptions\JobLockedException;
use App\Api\Exceptions\JobNotFoundException;

interface WebPrintHostInterface
{
    /**
     * @return string[]
     *
     * @throws ApiErrorException
     */
    public function checkForNewJobs(): array;

    /**
     * @param string $id Job ID
     *
     * @return JobModel
     *
     * @throws JobNotFoundException
     * @throws JobLockedException
     * @throws ApiErrorException
     */
    public function getJob(string $id): JobModel;

    /**
     * @param string $id Job ID
     *
     * @return void
     *
     * @throws ApiErrorException
     */
    public function markJobAsDone(string $id): void;

    /**
     * @param string $id Job ID
     * @param string $error Error ID
     *
     * @return void
     *
     * @throws ApiErrorException
     */
    public function markJobAsFailed(string $id, string $error): void;
}
