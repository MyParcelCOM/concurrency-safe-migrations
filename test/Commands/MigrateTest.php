<?php

declare(strict_types=1);

namespace Tests\Commands;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Cache;
use Mockery;
use MyParcelCom\ConcurrencySafeMigrations\Commands\Migrate;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function test_it_runs_migrations_if_locked_with_default_options(): void
    {
        $command = new Migrate();

        $lockMock = Mockery::mock(Lock::class);
        $lockMock->shouldReceive('get')->once()->andReturnTrue();

        Cache::shouldReceive('hasOption')->andReturnFalse();
        Cache::shouldReceive('store')->andReturn(
            Mockery::mock(LockProvider::class, [
                'lock' => $lockMock,
            ])
        );

        $inputMock = Mockery::mock(InputInterface::class);
        $inputMock->shouldReceive('hasOption')->withArgs(['cache-store'])->andReturnFalse();
        $inputMock->shouldReceive('hasOption')->withArgs(['key-id'])->andReturnFalse();
        $inputMock->shouldReceive('getOption')->withArgs(['lock-ttl'])->andReturn(60);
        $inputMock->shouldReceive('hasOption')->andReturnFalse();
        $inputMock->shouldReceive('bind');
        $inputMock->shouldReceive('isInteractive')->andReturnFalse();
        $inputMock->shouldReceive('hasArgument')->andReturnFalse();
        $inputMock->shouldReceive('validate');

        $outputStyleMock = Mockery::mock(OutputStyle::class);
        $outputStyleMock->shouldNotReceive('writeln');
        $containerMock = Mockery::mock(Container::class, [
            'make' => $outputStyleMock,
        ]);
        $containerMock->shouldReceive('call')->andReturnUsing(function () use ($command) {
            $command->handle();
        });


        $command->setLaravel($containerMock);
        $outputMock = Mockery::mock(OutputInterface::class);
        $command->run($inputMock, $outputMock);
    }

    public function test_it_runs_migrations_if_locked_with_custom_options(): void
    {
        $command = new Migrate();

        $lockMock = Mockery::mock(Lock::class);
        $lockMock->shouldReceive('get')->andReturnTrue();

        Cache::shouldReceive('hasOption')->andReturnFalse();
        Cache::shouldReceive('store')->andReturn(
            Mockery::mock(LockProvider::class, [
                'lock' => $lockMock,
            ])
        );

        $inputMock = Mockery::mock(InputInterface::class);
        $inputMock->shouldReceive('hasOption')->withArgs(['cache-store'])->andReturnTrue();
        $inputMock->shouldReceive('getOption')->withArgs(['cache-store'])->andReturn('example');
        $inputMock->shouldReceive('hasOption')->withArgs(['key-id'])->andReturnTrue();
        $inputMock->shouldReceive('getOption')->withArgs(['key-id'])->andReturn('my_key');
        $inputMock->shouldReceive('getOption')->withArgs(['lock-ttl'])->andReturn(360);
        $inputMock->shouldReceive('bind');
        $inputMock->shouldReceive('isInteractive')->andReturnFalse();
        $inputMock->shouldReceive('hasArgument')->andReturnFalse();
        $inputMock->shouldReceive('validate');

        $outputStyleMock = Mockery::mock(OutputStyle::class);
        $outputStyleMock->shouldNotReceive('writeln');
        $containerMock = Mockery::mock(Container::class, [
            'make' => $outputStyleMock,
        ]);
        $containerMock->shouldReceive('call')->andReturnUsing(function () use ($command) {
            $command->handle();
        });

        $outputMock = Mockery::mock(OutputInterface::class);

        $command->setLaravel($containerMock);
        $command->run($inputMock, $outputMock);
    }

    public function test_it_cannot_run_migrations_if_locked_with_default_options(): void
    {
        $command = new Migrate();

        $lockMock = Mockery::mock(Lock::class);
        $lockMock->shouldReceive('get')->andReturnFalse();

        Cache::shouldReceive('hasOption')->andReturnFalse();
        Cache::shouldReceive('store')->andReturn(
            Mockery::mock(LockProvider::class, [
                'lock' => $lockMock,
            ])
        );

        $inputMock = Mockery::mock(InputInterface::class);
        $inputMock->shouldReceive('hasOption')->withArgs(['cache-store'])->andReturnFalse();
        $inputMock->shouldReceive('hasOption')->withArgs(['key-id'])->andReturnFalse();
        $inputMock->shouldReceive('getOption')->withArgs(['lock-ttl'])->andReturn(60);
        $inputMock->shouldReceive('bind');
        $inputMock->shouldReceive('isInteractive')->andReturnFalse();
        $inputMock->shouldReceive('hasArgument')->andReturnFalse();
        $inputMock->shouldReceive('validate');

        $outputStyleMock = Mockery::mock(OutputStyle::class);
        $outputStyleMock->shouldReceive('writeln')
            ->once()
            ->withAnyArgs(['<info>Migration is being ran by another process, skipping...</info>']);
        $containerMock = Mockery::mock(Container::class, [
            'make' => $outputStyleMock,
        ]);
        $containerMock->shouldReceive('call')->andReturnUsing(function () use ($command) {
            $command->handle();
        });

        $outputMock = Mockery::mock(OutputInterface::class);

        $command->setLaravel($containerMock);
        $command->run($inputMock, $outputMock);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Cache::clearResolvedInstances();
    }


}
