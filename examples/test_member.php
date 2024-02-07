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

$token = '';
$newUID = 0;

test(
    'Login failed',
    http(
        $urlAPIREST . '/api/login',
        'POST',
        [
            'login' => 'bad',
            'password' => 'bad'
        ]
    )
);

$datas = test(
    'Login OK user login:test password:testtest',
    http(
        $urlAPIREST . '/api/login',
        'POST',
        [
            'login' => $login_nick,
            'password' => $login_password,
        ]
    )
);
$token = $datas->token;

test(
    'Login whoami test invalid JWT',
    http(
        $urlAPIREST . '/api/whoami',
        'GET',
        null,
        'XXXXXXXXXX'
    )
);

test(
    'Login whoami OK',
    http(
        $urlAPIREST . '/api/whoami',
        'GET',
        null,
        $token
    )
);

$uuid = \uniqid('test_name_');
$datas = test(
    'Create member',
    http(
        $urlAPIREST . '/api/member',
        'POST',
        [
            'member' => [
                'name' => $uuid,
                'surname' => 'Sophie',
                'gender' => 2,
                'title' => 2,

                'address' => 'rue des oiseaux',
                'zipcode' => '75001',
                'town' => 'Paris',

                //            'login' => $uuid,
                'password' => 'abcdefgh1234',
                'email' => $uuid . 'test@test.fr',
                'company_name' => 'Société ABC',

                'info_field_6' => 'a text',
                //                'bad' => 'bad'
                // 'email' => 'test@test.fr',
            ]
        ],
        $token
    )
);
echo "New member UID : {$datas->uid}<br>\n";
$newUID = $datas->uid;

test(
    'Get member',
    http(
        $urlAPIREST . '/api/member/' . $newUID,
        'GET',
        null,
        $token
    )
);

test('Modify member', http(
    $urlAPIREST . '/api/member/' . $newUID,
    'PUT',
    [
        'member' => [
            'surname' => 'Anne sophie',
            'company_name' => '', // Société XYZ',
            'info_field_1' => 2,
            'info_field_6' => 'a NEW text éèàêê',
            'info_field_28' => 2,
        ]
    ],
    $token
));

test('Get member reload', http(
    $urlAPIREST . '/api/member/' . $newUID,
    'GET',
    null,
    $token
));

test('Find member by mail', http(
    $urlAPIREST . '/api/member/find',
    'POST',
    [
        'email' => $uuid . 'test@test.fr'
    ],
    $token
));

test('Find member by id & zipcode', http(
    $urlAPIREST . '/api/member/find',
    'POST',
    [
        'uid' => $newUID,
        'zipcode' => '75001'
    ],
    // $token
));

if (10) {
    test('Delete member', http(
        $urlAPIREST . '/api/member/' . $newUID,
        'DELETE',
        null,
        $token
    ));
}

$uid = $UID_testmail;
test('Mail', http(
    $urlAPIREST . '/api/member/' . $uid . '/mail',
    'POST',
    [
        'title' => 'test',
        'body' => 'TEST'
    ],
    $token
));

test('Mail staff', http(
    $urlAPIREST . '/api/mail/staff',
    'POST',
    [
        'title' => 'test',
        'body' => 'TEST'
    ],
    $token
));
