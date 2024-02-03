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

namespace GaletteRESTAPI\Tools;

use Monolog\Logger;

final class Debug
{
    public static $logger;

    public static function init()
    {
        self::$logger = new \Monolog\Logger('RestAPI');
        $stream = new \Monolog\Handler\StreamHandler(__DIR__ . '/../../../logs/app.log', Logger::DEBUG);
        $dateFormat = 'Y-m-d H:i:s';
        //$output = "[%datetime%] %channel% %level_name%: %message% \n"; // %context% %extra%\n";
        $output = "[%datetime%] : %message% \n"; // %context% %extra%\n";
        $formatter = new \Monolog\Formatter\LineFormatter($output, $dateFormat);
        $stream->setFormatter($formatter);
        self::$logger->pushHandler($stream);

        return self::$logger;
    }

    public static function getLogger()
    {
        return self::$logger;
    }

    public static function printVar($expression, $return = true)
    {
        $export = \print_r($expression, true);
        $patterns = [
            '/array \\(/' => '[',
            '/^([ ]*)\\)(,?)$/m' => '$1]$2',
            "/=>[ ]?\n[ ]+\\[/" => '=> [',
            "/([ ]*)(\\'[^\\']+\\') => ([\\[\\'])/" => '$1$2 => $3',
        ];
        $export = \preg_replace(\array_keys($patterns), \array_values($patterns), $export);

        if ((bool) $return) {
            return $export;
        }
        echo $export;
    }

    public static function log(string $txt): void
    {
        if (null !== self::$logger) {
            self::$logger->info($txt);
        }
    }

    public static function error(string $txt): void
    {
        if (null !== self::$logger) {
            self::$logger->error($txt);
        }
    }

    public static function logRequest($request): void
    {
        $t = 'API Call : ' . $request->getUri()->getPath();
        self::log($t);

        if ($request->getQueryParams() != null) {
            self::log('   - GET dump :' . self::printVar($request->getQueryParams()));
        }

        if ($request->getParsedBody() != null) {
            self::log('   - POST dump :' . self::printVar((array) $request->getParsedBody()));
        }
    }
}
