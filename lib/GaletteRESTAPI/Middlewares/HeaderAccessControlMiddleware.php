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

namespace GaletteRESTAPI\Middlewares;

use GaletteRESTAPI\Tools\Debug;
use Psr\Container\ContainerInterface;

final class HeaderAccessControlMiddleware
{
    private $router;

    public function __construct(ContainerInterface $container)
    {
        $this->router = $container->get('router');
    }

    public function __invoke($request, $response, $next)
    {
        Debug::logRequest($request);

        $route = $request->getAttribute('route');

        $methods = [];

        if (!empty($route)) {
            $pattern = $route->getPattern();

            foreach ($this->router->getRoutes() as $route) {
                if ($route->getPattern() === $pattern) {
                    $methods = \array_merge_recursive($methods, $route->getMethods());
                }
            }
            //Methods holds all of the HTTP Verbs that a particular route handles.
        } else {
            $methods[] = $request->getMethod();
        }

        $response = $next($request, $response);

        return $response->withHeader('Access-Control-Allow-Methods', \implode(',', $methods))
            ->withHeader('Access-Control-Allow-Origin', '*')
            //->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization');
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization')
            ->withHeader('Access-Control-Max-Age', '60');
    }
}
