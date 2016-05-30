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

namespace Traq\Installer;

use Exception;
use Avalon\AppKernel;
use Avalon\Templating\View;

/**
 * Traq installation kernel.
 *
 * @package Traq\Installer
 */
class Kernel extends AppKernel
{
    public function __construct()
    {
        $this->configDir = __DIR__ . '/config';
        parent::__construct();
        View::loadFunctions();

        class_alias("Avalon\\Templating\\View", "View");
        class_alias("Avalon\\Http\\Request", "Request");
        class_alias("Avalon\\Helpers\\HTML", "HTML");

        $traqDir = dirname(dirname(dirname(dirname(__DIR__)))) . '/src/'; // I should just code for PHP 7.0+

        require $traqDir . "/version.php";
        require $traqDir . "/common.php";

        define('PREFIX', '');
    }

    /**
     * Loads the applications configuration.
     */
    protected function loadConfiguration()
    {
        // We don't need the configuration during installation.
        return false;
    }
}
