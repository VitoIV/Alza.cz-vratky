<?php

namespace App\Support;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $driver = Config::get('database.driver', 'pgsql');
            $host = Config::get('database.host', '127.0.0.1');
            $port = Config::get('database.port', 5432);
            $database = Config::get('database.database', 'returns');
            $username = Config::get('database.username', 'postgres');
            $password = Config::get('database.password', '');

            if ($driver !== 'pgsql') {
                throw new \RuntimeException('Unsupported database driver: '.$driver);
            }

            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);

            try {
                self::$pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo 'Database connection failed: '.htmlspecialchars($e->getMessage());
                exit;
            }
        }

        return self::$pdo;
    }
}
