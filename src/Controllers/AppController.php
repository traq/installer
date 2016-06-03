<?php
/*!
 * Traq
 * Copyright (C) 2009-2016 Jack P.
 * Copyright (C) 2012-2016 Traq.io
 * https://github.com/nirix
 * https://traq.io
 *
 * This file is part of Traq.
 *
 * Traq is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 only.
 *
 * Traq is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Traq. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Traq\Installer\Controllers;

use PDOException;
use Doctrine\DBAL\DBALException;
use Avalon\Http\Controller;
use Avalon\Http\Request;
use Avalon\Database\ConnectionManager;

/**
 * @author Jack P.
 * @since 4.0.0
 */
class AppController extends Controller
{
    protected $docRoot;

    public function __construct()
    {
        // parent::__construct();

        session_start();

        $this->docRoot = dirname(dirname(dirname(dirname(dirname(__DIR__)))));

        $this->set("installStep", function ($routeName) {
            return Request::$basePath . '/index.php' . $this->generatePath($routeName);
        });

        $this->set("drivers", [
            'pdo_mysql'  => "MySQL",
            'pdo_pgsql'  => "PostgreSQL",
            // 'pdo_sqlite' => "SQLite",
            // 'pdo_sqlsrv' => "SQL Server",
            // 'pdo_oci'    => "Oracle"
        ]);

        $this->before('*', function () {
            $configDir = $this->docRoot . '/config';
            $this->title("Configuration File Exists");
            if (file_exists($configDir . '/config.php')) {
                return $this->render("config_exists.phtml");
            }
        });
    }

    /**
     * Set page title.
     *
     * @param string $title
     */
    protected function title($title)
    {
        $this->set("stepTitle", $title);
    }

    /**
     * Load migrations.
     */
    protected function loadMigrations()
    {
        $migrationsDir = $this->docRoot . '/src/Database/Migrations';
        foreach (scandir($migrationsDir) as $file) {
            if ($file !== '.' && $file !== '..') {
                require "{$migrationsDir}/{$file}";
            }
        }
    }

    /**
     * Check the database form fields and connection.
     *
     * @access protected
     */
    protected function checkDatabaseInformation()
    {
        $this->title("Database Information");

        $errors = [];
        $driver = Request::$post->get('driver');

        // Check fields
        if ($driver == "pdo_pgsql" || $driver == "pdo_mysql") {
            if (!Request::$post->get('host')) {
                $errors[] = "Server is required";
            }

            if (!Request::$post->get('user')) {
                $errors[] = "Username is required";
            }

            if (!Request::$post->get('dbname')) {
                $errors[] = "Database name is required";
            }
        } elseif ($driver == "pdo_sqlite") {
            if (!Request::$post->get('path')) {
                $errors[] = "Database path is required";
            }
        }

        // Check connection
        if (!count($errors)) {
            $info = [
                'driver' => $driver,
            ];

            switch ($driver) {
                case "pdo_pgsql":
                case "pdo_mysql":
                    $info = $info + [
                        'host'     => Request::$post->get('host'),
                        'user'     => Request::$post->get('user'),
                        'password' => Request::$post->get('password'),
                        'dbname'   => Request::$post->get('dbname')
                    ];
                    break;

                case "pdo_sqlite":
                    $info['path'] = Request::$post->get('path');
                    break;
            }

            try {
                // Lets try to do a few things with the database to confirm a connection.
                $db = ConnectionManager::create($info);
                $sm = $db->getSchemaManager();
                $sm->listTables();
            } catch (DBALException $e) {
                $errors[] = "Unable to connect to database: " . $e->getMessage();
            }
        }

        if (count($errors)) {
            $this->title("Database Information");
            return $this->render("steps/database_information.phtml", [
                'errors' => $errors
            ]);
        }

        $_SESSION['db'] = $info;
    }

    /**
     * Check admin account information.
     *
     * @access protected
     */
    protected function checkAccountInformation()
    {
        $errors = [];

        if (!Request::$post->get('username')) {
            $errors[] = "Username is required";
        }

        if (strlen(Request::$post->get('username')) < 3) {
            $errors[] = "Username must be at least 3 characters long";
        }

        if (!Request::$post->get('password')) {
            $errors[] = "Password is required";
        }

        if (strlen(Request::$post->get('password')) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }

        if (!Request::$post->get('email')) {
            $errors[] = "Email is required";
        }

        if (count($errors)) {
            $this->title("Admin Account");
            return $this->render("steps/account_information.phtml", [
                'errors' => $errors
            ]);
        }

        $_SESSION['admin'] = [
            'username'         => Request::$post->get('username'),
            'password'         => Request::$post->get('password'),
            'confirm_password' => Request::$post->get('password'),
            'email'            => Request::$post->get('email')
        ];
    }
}
