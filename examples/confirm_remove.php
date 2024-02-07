<?php

declare(strict_types=1);

/**
 * Plugin RESTAPI for Galette Project
 *
 *  PHP version >=8.1
 *
 *  This file is part of 'Plugin RESTAPI for Galette Project'.
 *
 *  Plugin RESTAPI for Galette Project is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Plugin RESTAPI for Galette Project is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Plugin RESTAPI for Galette Project. If not, see <http://www.gnu.org/licenses/>.
 *
 *  @category Plugins
 *  @package  Plugin RESTAPI for Galette Project
 *
 *  @author    manuelh78 <manuelh78dev@ik.me>
 *  @copyright manuelh78 (c) 2024
 *  @license   http://www.gnu.org/licenses/gpl-3.0.html GPL License 3.0
 */

require 'debug.php';

require '../vendor/autoload.php';

require 'api.php';

require 'config.php';

echo '<h1>A page somewhere on your web site...</h1>';

$codeValid = \strrchr($_SERVER['REQUEST_URI'], '/');
$codeValid = \substr($codeValid, 1);

// $codeValid = "def502007f3e1a8.....78ab90cd4b4cece87bb8ac5";

$response = http(
    $urlAPIREST . '/api/newsletter/confirm_remove/' . $codeValid,
    'GET',
    null,
    'application/x-www-form-urlencoded'
);

echo '<h4>';
$datas = \json_decode($response);
echo "Status : {$datas->status}<br>\n";

if (isset($datas->message)) {
    echo "Message : {$datas->message}<br>\n";
}

if (isset($datas->tables)) {
    echo 'Find in tables SQL : ' . \implode(', ', $datas->tables) . "<br>\n";
}

echo '</h4>';

echo '<hr>';
\var_dump($response);
