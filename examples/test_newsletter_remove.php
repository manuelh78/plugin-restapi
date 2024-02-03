<?php

declare(strict_types=1);

/**
 * Plugin RESTAPI for Galette Project
 *
 *  PHP version >=7.4
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

$token = '';
$codeValid = 0;

//---

$datas = test(
    'Login OK user login:test password:testtest',
    http(
        $urlAPIREST . '/api/login',
        'POST',
        [
            'login' => 'test',
            'password' => 'testtest'
        ]
    )
);
$token = $datas->token;

test('Email remove 1', http(
    $urlAPIREST . '/api/newsletter',
    'DELETE',
    [
        'email' => $email1,
        'urlcallback' => $urlcallbackNewsletterRemove
    ],
    $token
));

$uuid = \uniqid('test_name_');
$datas = test('Create member', http(
    $urlAPIREST . '/api/member',
    'POST',
    [
        'member' => [
            'name' => $uuid,
            'surname' => 'surname',
            'login' => $uuid,
            'password' => 'abcdefgh1234',
            'info_field_6' => 'a text',
            'email' => $email2,
        ]
    ],
    $token
));
echo "New member UID : {$datas->uid}<br>\n";
$newUID = $datas->uid;

test('Email remove 2', http(
    $urlAPIREST . '/api/newsletter',
    'DELETE',
    [
        'email' => $email2,
        'urlcallback' => $urlcallbackNewsletterRemove
    ],
    $token,
));
