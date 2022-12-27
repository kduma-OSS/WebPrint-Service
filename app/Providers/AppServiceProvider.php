<?php

namespace App\Providers;

use App\Api\HttpWebPrintHost;
use App\Api\WebPrintHostInterface;
use App\PollingCalculators\PollTimeCalculatorFactory;
use App\PollingCalculators\PollTimeCalculatorInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(WebPrintHostInterface::class, function ($app) {
            return new HttpWebPrintHost();
        });

        $this->app->singleton(PollTimeCalculatorInterface::class, function (Application $app) {
            return $app
                ->make(PollTimeCalculatorFactory::class)
                ->fromString(config('poll_time_calculator.type'));
        });
    }
}
