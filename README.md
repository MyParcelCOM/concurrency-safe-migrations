# Concurrency-safe migrations for Laravel
[![Latest Stable Version](https://poser.pugx.org/myparcelcom/concurrency-safe-migrations/v)](//packagist.org/packages/myparcelcom/concurrency-safe-migrations) [![Total Downloads](https://poser.pugx.org/myparcelcom/concurrency-safe-migrations/downloads)](//packagist.org/packages/myparcelcom/concurrency-safe-migrations) [![Latest Unstable Version](https://poser.pugx.org/myparcelcom/concurrency-safe-migrations/v/unstable)](//packagist.org/packages/myparcelcom/concurrency-safe-migrations) [![License](https://poser.pugx.org/myparcelcom/concurrency-safe-migrations/license)](//packagist.org/packages/myparcelcom/concurrency-safe-migrations)

## Overview
This library ships the command `migrate:safe` for Laravel which executed the `migrate` artisan command in concurrency-safe manner using the Laravel atomic locks.

## Installation

First, include the `myparcelcom/concurrency-safe-migrations` package in your composer dependencies:

```shell
composer require myparcelcom/concurrency-safe-migrations
```

Simply register the MigrateSafe command with your console Kernel:
```php
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \MyParcelCom\LockedMigrations\Commands\MigrateSafe::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
    }
}
```

## Configuration
All options available with the `migrate` command can be passed to `migrate:safe` too. In addition, you can configure the atomic lock settings too:

- `--lock-ttl` - pass custom maximum lock ttl (in seconds). The `migrate:safe` will automatically release the lock once done. However, it is useful to put some ttl in failure cases where the container dies and lock is not released. Default value is 60 seconds
- `--cache-store` - select a specific Cache store. When omitted the default cache store is selected. The cache store **is required to work with atomic locks.** 
- `--key-id` - enter custom cache lock key. When omitted the `APP_NAME` and current application environments are used to compose a key

## Background
Running migrations using `migrate:safe` assures that your application will not execute migrations at the same time. This could be a problem if you want to automate running migrations for your app but for whatever reason you cannot assure that the `migrate` command will be ran only once per deployment.

An example application is deploying a Laravel application on Kubernetes using [Flux]. Flux reconciles changes from Git in declarative way. Therefore, it becomes difficult to activate migrations upon deployment using a Job.

`migrate:safe` allows developers to attach init containers to deployment pods and only one will execute the migrations.

[Flux]: https://fluxcd.io/
