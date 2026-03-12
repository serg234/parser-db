<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider; // <-- важно

use App\Logging\LoggerInterface;
use App\Logging\FileLogger;
use App\Logging\DatabaseLogger;
use App\Logging\CompositeLogger;

class LoggingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    const PATH_TO_FILE_LOGGER = null;
    const DB_TABLE_LOGS = null;

    public function register()
    {
        \Log::debug('—> LoggingServiceProvider::register() called');

        // Регистрируем каждый конкретный логгер
        $this->app->singleton(FileLogger::class, function($app) {
            return new FileLogger(self::PATH_TO_FILE_LOGGER);
        });

        $this->app->singleton(DatabaseLogger::class, function($app) {
            return new DatabaseLogger(self::DB_TABLE_LOGS);
        });

        // Биндим общий интерфейс на композитный логгер
        $this->app->singleton(LoggerInterface::class, function($app) {
            return new CompositeLogger(
                $app->make(FileLogger::class),
                $app->make(DatabaseLogger::class)
            );
        });
    }

    public function provides()
    {
        return [LoggerInterface::class, FileLogger::class, DatabaseLogger::class];

    }
}
