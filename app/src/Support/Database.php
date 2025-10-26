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
            $driver = Config::get('database.driver', 'mysql');
            $host = Config::get('database.host', '127.0.0.1');
            $port = Config::get('database.port', 3306);
            $database = Config::get('database.database', 'returns');
            $username = Config::get('database.username', 'root');
            $password = Config::get('database.password', '');
            $charset = Config::get('database.charset', 'utf8mb4');

            if ($driver !== 'mysql') {
                throw new \RuntimeException('Unsupported database driver: ' . $driver);
            }

            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);

            try {
                self::$pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo 'Database connection failed: ' . htmlspecialchars($e->getMessage());
                exit;
            }
        }

        return self::$pdo;
    }
}
