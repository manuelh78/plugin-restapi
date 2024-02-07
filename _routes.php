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

use GaletteRESTAPI\Controllers\LoginController;
use GaletteRESTAPI\Controllers\MemberController;
use GaletteRESTAPI\Controllers\MembersController;
use GaletteRESTAPI\Controllers\NewsletterController;
use GaletteRESTAPI\Middlewares\HeaderAccessControlMiddleware;
use GaletteRESTAPI\Middlewares\JsonExceptionMiddleware;
use GaletteRESTAPI\Middlewares\JwtLoginMiddleware;
use Slim\Routing\RouteCollectorProxy;
use Tuupola\Middleware\JwtAuthentication;

// Include specific classes
require_once 'vendor/autoload.php';

// Constants and classes from plugin
require_once $module['root'] . '/_config.inc.php';

require_once '_dependencies.php';

$app->get('/[home]', [LoginController::class, 'home'])->setName(RESTAPI_PREFIX . '_home');

// free routes, no token
$app->group('', function (RouteCollectorProxy $app): void {
    $app->post('/api/login', [LoginController::class, 'login'])->setName(RESTAPI_PREFIX . '_login');

    // call by javascript :
    $app->post('/api/member/find', [MemberController::class, 'find'])->setName(RESTAPI_PREFIX . '_member_find');
    $app->post('/api/member/canlogin', [MemberController::class, 'canlogin'])->setName(RESTAPI_PREFIX . '_member_canlogin');
    $app->post('/api/member/passwordlost', [MemberController::class, 'passwordlost'])->setName(RESTAPI_PREFIX . '_member_password_lost');

    // call by submit form :
    $app->post('/api/newsletter', [NewsletterController::class, 'add'])->setName(RESTAPI_PREFIX . '_newsletter_add');

    // call by email link :
    $app->get('/api/newsletter/confirm_add/{codeValid:[0-9a-z]+}', [NewsletterController::class, 'confirm_add'])->setName(RESTAPI_PREFIX . '_newsletter_confirm_add');
    $app->get('/api/newsletter/confirm_remove/{codeValid:[0-9a-z]+}', [NewsletterController::class, 'confirm_remove'])->setName(RESTAPI_PREFIX . '_newsletter_confirm_remove');
})
    ->add(HeaderAccessControlMiddleware::class)
    ->add(JsonExceptionMiddleware::class);

// These routes require a token:
$app->group('/api', function (RouteCollectorProxy $app): void {
    $app->get('/whoami', [LoginController::class, 'whoami'])->setName(RESTAPI_PREFIX . '_whoami');

    $app->group('/member', function (RouteCollectorProxy $app): void {
        $app->get('/{uid:[0-9]+}', [MemberController::class, 'get'])->setName(RESTAPI_PREFIX . '_member_get');
        $app->post('', [MemberController::class, 'post'])->setName(RESTAPI_PREFIX . '_member_post');
        $app->map(['PUT', 'PATCH'], '/{uid:[0-9]+}', [MemberController::class, 'put'])->setName(RESTAPI_PREFIX . '_member_put');
        $app->delete('/{uid:[0-9]+}', [MemberController::class, 'delete'])->setName(RESTAPI_PREFIX . '_member_delete');
        // Send a mail to a member
        $app->post('/{uid:[0-9]+}/mail', [MemberController::class, 'mail'])->setName(RESTAPI_PREFIX . '_member_mail');
    });

    $app->delete('/newsletter', [NewsletterController::class, 'remove'])->setName(RESTAPI_PREFIX . '_email_remove');
    // Send a mail to staff members
    $app->post('/mail/{uid:[a-z]+}', [MemberController::class, 'mail'])->setName(RESTAPI_PREFIX . '_mail');

    $app->post('/members/emails[/{source}]', [MembersController::class, 'emails'])->setName(RESTAPI_PREFIX . '_members_emails');
})
    ->add(JwtLoginMiddleware::class)
    ->add(JwtAuthentication::class)
    ->add(HeaderAccessControlMiddleware::class)
    ->add(JsonExceptionMiddleware::class);
