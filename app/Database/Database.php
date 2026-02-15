<?php

namespace App\Database;

use App\Config\Paths;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Database
{
    private static ?Capsule $capsule = null;

    private static function init(string $mode = 'dev'): void
    {
        if (self::$capsule !== null) {
            return;
        }

        $capsule = new Capsule();

        $dbFile = Paths::getDatabasePath($mode);

        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => $dbFile,
            'prefix' => '',
        ]);

        $capsule->setEventDispatcher(new Dispatcher(new Container()));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $dir = dirname($dbFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($dbFile)) {
            touch($dbFile);
        }

        // Apply migrations based on the mode
        $schema = require 'migrations.php';
        foreach ($schema ?? [] as $sql) {
            $capsule::schema()->getConnection()->getPdo()->exec($sql);
        }

        self::$capsule = $capsule;
    }

    public static function get(string $mode): Capsule
    {
        if (self::$capsule === null) {
            self::init($mode);
        }
        return self::$capsule;
    }
}
