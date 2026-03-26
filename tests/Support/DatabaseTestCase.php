<?php

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    private static bool $schemaReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (self::isSqliteDb() && !extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('These tests require the pdo_sqlite extension (DATABASE_URL is sqlite). Install pdo_sqlite or configure MySQL in `.env.test`.');
        }
        if (self::isMysqlDb()) {
            if (!extension_loaded('pdo_mysql')) {
                $this->markTestSkipped('These tests require the pdo_mysql extension (DATABASE_URL is mysql).');
            }

            if (!self::canConnectMysql()) {
                $this->markTestSkipped('Cannot connect to MySQL for tests. Start the DB (e.g. `docker compose up -d`) and ensure `.env.test` DATABASE_URL is reachable.');
            }
        }

        if (!self::$schemaReady) {
            self::rebuildSchema();
            self::$schemaReady = true;
        }
    }

    protected function em(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine')->getManager();

        return $em;
    }

    private static function rebuildSchema(): void
    {
        if (self::isMysqlDb()) {
            self::ensureMysqlDatabaseExists();
        }

        static::bootKernel();

        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        // For SQLite tests, a fresh schema is faster and more reliable than migrations.
        $metadata = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private static function isSqliteDb(): bool
    {
        $url = (string) ($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');

        return str_starts_with($url, 'sqlite:');
    }

    private static function isMysqlDb(): bool
    {
        $url = (string) ($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');

        return str_starts_with($url, 'mysql://');
    }

    private static function canConnectMysql(): bool
    {
        $cfg = self::parseMysqlUrl();
        if (null === $cfg) {
            return false;
        }

        try {
            new \PDO(
                sprintf('mysql:host=%s;port=%d;dbname=mysql;charset=utf8mb4', $cfg['host'], $cfg['port']),
                $cfg['user'],
                $cfg['pass'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function ensureMysqlDatabaseExists(): void
    {
        $cfg = self::parseMysqlUrl();
        if (null === $cfg) {
            return;
        }

        $pdo = new \PDO(
            sprintf('mysql:host=%s;port=%d;dbname=mysql;charset=utf8mb4', $cfg['host'], $cfg['port']),
            $cfg['user'],
            $cfg['pass'],
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );

        $dbToCreate = self::effectiveMysqlDbName($cfg['db']);
        $dbName = str_replace('`', '``', $dbToCreate);
        $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', $dbName));
    }

    private static function effectiveMysqlDbName(string $baseDb): string
    {
        $env = (string) ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? '');
        if ('test' !== $env) {
            return $baseDb;
        }

        // doctrine.yaml adds: dbname_suffix: '_test%env(default::TEST_TOKEN)%'
        $token = (string) ($_ENV['TEST_TOKEN'] ?? $_SERVER['TEST_TOKEN'] ?? '');
        $suffix = '_test' . $token;

        if (str_ends_with($baseDb, $suffix)) {
            return $baseDb;
        }

        return $baseDb . $suffix;
    }

    /**
     * @return array{host: string, port: int, user: string, pass: string, db: string}|null
     */
    private static function parseMysqlUrl(): ?array
    {
        $url = (string) ($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');
        if (!str_starts_with($url, 'mysql://')) {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $host = (string) ($parts['host'] ?? '127.0.0.1');
        $port = (int) ($parts['port'] ?? 3306);
        $user = rawurldecode((string) ($parts['user'] ?? 'root'));
        $pass = rawurldecode((string) ($parts['pass'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        $db = ltrim($path, '/');

        if ('' === $db) {
            return null;
        }

        return [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'pass' => $pass,
            'db' => $db,
        ];
    }
}
