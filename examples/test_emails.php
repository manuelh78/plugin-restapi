
<?php
require 'debug.php';

require '../vendor/autoload.php';

require 'api.php';

require 'config.php';

$token = '';

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

test('Get emails member galette', http(
    $urlAPIREST . '/api/members/emails',
    'POST',
    null,
    $token
));

test('Get emails, member + free newsletter', http(
    $urlAPIREST . '/api/members/emails',
    'POST',
    [
        'sources' => ['galette', 'basic']
    ],
    $token
));
