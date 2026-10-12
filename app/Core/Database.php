<?php
/** Conexão única (PDO) com o MySQL, compartilhada por todos os Models. */
class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            if (!extension_loaded('pdo_mysql')) {
                throw new RuntimeException('PHP sem pdo_mysql. No XAMPP, habilite a extensão PDO MySQL no php.ini e reinicie o Apache.');
            }
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . (defined('DB_PORT') ? (int)DB_PORT : 3306) . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }
}
