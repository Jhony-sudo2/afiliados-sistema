<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            app_env('DB_HOST', '127.0.0.1'),
            app_env('DB_PORT', '3306'),
            app_env('DB_DATABASE', 'sistema_territorial')
        );

        self::$pdo = new PDO(
            $dsn,
            app_env('DB_USERNAME', 'root'),
            app_env('DB_PASSWORD', ''),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return self::$pdo;
    }
}
