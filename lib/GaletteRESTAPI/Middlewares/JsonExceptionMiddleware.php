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

namespace GaletteRESTAPI\Middlewares;

use GaletteRESTAPI\Tools\Debug;
use GaletteRESTAPI\Tools\JsonResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Response;

final class JsonExceptionMiddleware
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
            $body = (string) $response->getBody();

            if (RESTAPI_DEBUG) {
                Debug::log("   - API return :'{$body}'");
            }

            return $response;
        } catch (\Exception $exception) {
            return self::returnException($exception);
        } catch (\Throwable $exception) {
            return self::returnException($exception);
        }
    }

    public static function returnException($e)
    {
        $response = new Response();  

        Debug::error("Exception : {$e}");

        return JsonResponse::withJson(
            $response,
            [
                'status' => 'error',
                'message' => $e->getMessage(),

                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'details' => RESTAPI_DEBUG ? (string) $e : 'debug disabled',
            ]
        )->withStatus(401);
    }
}
