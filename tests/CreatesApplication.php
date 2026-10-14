<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\ConfigurationUrlParser;
use Illuminate\Foundation\Application;
use RuntimeException;
use Throwable;

trait CreatesApplication
{
    private const TESTING_DATABASE_MARKER = 'pinvideo-pacs:testing';

    private static ?string $verifiedMarkerFor = null;

    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->guardAgainstCachedConfiguration($app);
        $this->guardAgainstNonTestingDatabase($app);

        return $app;
    }

    private function guardAgainstCachedConfiguration(Application $app): void
    {
        $cached = [];

        if ($app->configurationIsCached()) {
            $cached[] = 'конфиг (bootstrap/cache/config.php)';
        }

        if ($app->routesAreCached()) {
            $cached[] = 'маршруты (bootstrap/cache/routes-v7.php)';
        }

        if ($cached !== []) {
            throw new RuntimeException(
                'Обнаружен кеш: '.implode(' и ', $cached).'. Значения из phpunit.xml при этом '
                .'игнорируются, и тесты пойдут на настройках из кеша. Выполните artisan config:clear '
                .'и artisan route:clear, затем повторите прогон.'
            );
        }
    }

    private function guardAgainstNonTestingDatabase(Application $app): void
    {
        $default = $app['config']->get('database.default');
        $parser = new ConfigurationUrlParser();

        $connections = $app['config']->get('database.connections', []);

        if (isset($connections[$default])) {
            $connections = [$default => $connections[$default]] + $connections;
        }

        foreach ($connections as $name => $config) {
            if (!is_array($config)) {
                continue;
            }

            $config = $parser->parseConfiguration($config);
            $database = (string) ($config['database'] ?? '');

            if (($config['driver'] ?? null) === 'sqlite' && ($database === '' || $database === ':memory:')) {
                continue;
            }

            if (!preg_match('/_testing(_test_\d+)?$/', $database)) {
                $role = $name === $default ? 'основная база' : 'соединение другого модуля';

                throw new RuntimeException(
                    "Тесты запущены на базе «{$database}» (подключение «{$name}», {$role}). "
                    .'Ожидается база с суффиксом _testing — прогон остановлен, чтобы RefreshDatabase '
                    .'не удалил данные. Проверьте переменные окружения процесса, в том числе DATABASE_URL.'
                );
            }
        }

        $this->guardAgainstUnmarkedDatabase($app, $default);
    }

    private function guardAgainstUnmarkedDatabase(Application $app, ?string $connection): void
    {
        $config = $app['config']->get("database.connections.{$connection}", []);

        if (($config['driver'] ?? null) !== 'pgsql') {
            return;
        }

        $database = (string) ((new ConfigurationUrlParser())->parseConfiguration($config)['database'] ?? '');

        if (self::$verifiedMarkerFor === $connection.'@'.$database) {
            return;
        }

        $marked = preg_replace('/_test_\d+$/', '', $database);

        try {
            $marker = $app['db']->connection($connection)->scalar(
                "select shobj_description(oid, 'pg_database') from pg_database where datname = ?",
                [$marked]
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Не удалось проверить маркер тестовой базы «{$database}» (подключение «{$connection}»): "
                .$e->getMessage().'. Подготовьте базу командой ./setup-test-db.sh',
                0,
                $e
            );
        }

        if ($marker !== self::TESTING_DATABASE_MARKER) {
            throw new RuntimeException(
                "На базе «{$marked}» нет маркера тестовой базы, прогон остановлен. RefreshDatabase "
                .'начинает с migrate:fresh, поэтому очищать разрешено только базу, подготовленную '
                .'скриптом ./setup-test-db.sh — он проставляет маркер. Если база действительно '
                ."тестовая, выполните: TEST_DB={$marked} ./setup-test-db.sh"
            );
        }

        self::$verifiedMarkerFor = $connection.'@'.$database;
    }
}
