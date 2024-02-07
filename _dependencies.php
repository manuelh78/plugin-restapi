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

use DI\Container;
use GaletteRESTAPI\Tools\Debug;
use GaletteRESTAPI\Tools\JWTHelper;
// use Slim\Router;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Routing\RouteParser;

if (RESTAPI_LOG) {
    Debug::init();
}

$container = $app->getContainer();
$container->get(App::class)->addBodyParsingMiddleware(); // Add Json parser body

$container->set(
    'config',
    static function (ContainerInterface $container) {
        $conf = new GaletteRESTAPI\Tools\Config(RESTAPI_CONFIGPATH . '/config.yml');

        if (!$conf->get('cryptokey')) {
            $nk = Defuse\Crypto\Key::createNewRandomKey();
            $conf->set('cryptokey', $nk->saveToAsciiSafeString());
            $conf->writeFile();
        }

        return $conf;
    }
);

$container->set(
    'cryptokey',
    static function (ContainerInterface $container) {
        return Defuse\Crypto\Key::loadFromAsciiSafeString($container->get('config')->get('cryptokey'));
    }
);

$container->set(
    Tuupola\Middleware\JwtAuthentication::class,
    static function (ContainerInterface $container) {
        return new Tuupola\Middleware\JwtAuthentication([
            'logger' => Debug::getLogger(),
            'path' => '(.*)/api', /* or ["/api", "/admin"] */
            'attribute' => 'jwt_data',
            'secret' => JWTHelper::getPrivateKey(),
            'algorithm' => ['HS256'],
            'error' => /* static phpcs */ function ($response, $arguments) {
                $data['status'] = 'error';
                $data['message'] = $arguments['message'];

                Debug::log('JwtAuthentication::error() ' . Debug::printVar($arguments));

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->getBody()->write(\json_encode($data, \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT));
            },
            'secure' => RESTAPI_DEBUG ? false : true,
            // 'ignore' => ['/api/token']
        ]);
    }
);

$container->set('request', static function () {
    $serverRequestCreator = ServerRequestCreatorFactory::create();

    return $request = $serverRequestCreator->createServerRequestFromGlobals();
});

// Set view in Container
$container->set('twig', static function (ContainerInterface $container) use ($app) {
    $loader = new Twig\Loader\FilesystemLoader(__DIR__ . '/templates');

    $view = new Twig\Environment(
        $loader,
        [
            // 'cache' => '/path/to/compilation_cache',
            'debug' => RESTAPI_DEBUG ? true : false
        ]
    );

    $uri = $container->get('request')->getUri();

    $view->addGlobal('url_root', $uri->getScheme() . '://' . $uri->getHost());
    $view->addGlobal('url_base', $uri->getScheme() . '://' . $uri->getHost() . $app->getBasePath() . '/');
    $view->addGlobal('galette_webroot', $container->get(RouteParser::class)->urlFor('slash'));

    $view->addGlobal('session', $_SESSION);

    return $view;
});
