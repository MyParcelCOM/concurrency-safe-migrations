<?php

declare(strict_types=1);

namespace MyParcelCom\ConcurrencySafeMigrations\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;
use function env;

class Migrate extends Command
{
    protected $signature = <<<'SIGNATURE'
        migrate:safe 
            {--lock-ttl=60 : Lock time-to-live (in seconds)}
            {--cache-store= : Which cache store to use for the lock}
            {--key-id= : Lock cache key ID. If omitted a key will be composed based on APP_ENV and APP_NAME}
            {--database= : The database connection to use}
            {--force : Force the operation to run when in production}
            {--path=* : The path(s) to the migrations files to be executed}
            {--realpath : Indicate any provided migration file paths are pre-resolved absolute paths}
            {--schema-path= : The path to a schema dump file}
            {--pretend : Dump the SQL queries that would be run}
            {--seed : Indicates if the seed task should be re-run}
            {--step : Force the migrations to be run so they can be rolled back individually}
    SIGNATURE;


    protected $description = <<<'DESC'
        Runs `php artisan migrate` command but it creates a lock so other invocations would not be possible
    DESC;

    public function handle(): void
    {
        $lock = $this->lock();

        $result = $lock->get($this->safeMigrate($lock));

        if (!$result) {
            $this->info('Migration is being ran by another process, skipping...');
        }
    }

    private function lock(): Lock
    {
        $cache = $this->hasOption('cache-store') ? Cache::store($this->option('cache-store')) : Cache::store();

        if (!$cache instanceof LockProvider) {
            throw new InvalidCacheStoreException('The selected cache store does not provide atomic locks');
        }

        $key = $this->hasOption('key-id') ?
            $this->option('key-id') :
            Str::snake('migration_run_' . env('APP_NAME') . '_' . env('APP_ENV'));

        $ttl = (int) $this->option('lock-ttl');

        return $cache->lock($key, $ttl);
    }

    private function safeMigrate(Lock $lock): Closure
    {
        return function () use ($lock) {
            try {
                $this->call('migrate', array_filter([
                    '--database'    => $this->hasOption('database') ? $this->option('database') : null,
                    '--force'       => $this->hasOption('force') ? $this->option('force') : null,
                    '--path'        => $this->hasOption('path') ? $this->option('path') : null,
                    '--realpath'    => $this->hasOption('realpath') ? $this->option('realpath') : null,
                    '--schema-path' => $this->hasOption('schema-path') ? $this->option('schema-path') : null,
                    '--pretend'     => $this->hasOption('pretend') ? $this->option('pretend') : null,
                    '--seed'        => $this->hasOption('seed') ? $this->option('seed') : null,
                    '--step'        => $this->hasOption('step') ? $this->option('step') : null,
                ]));
            } catch (Throwable $e) {
                $lock->release();
                throw $e;
            }

            return true;
        };
    }
}
