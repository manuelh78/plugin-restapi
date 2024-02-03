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

final class Http
{
    public const DATA_JSON = 'application/json';
    public const DATA_XFORM = 'application/x-www-form-urlencoded';
    public const DATA_MULTIPART = 'multipart/form-data';

    public static function str2hex($string)
    {
        return \implode('', \unpack('H*', $string));
    }

    public static function hex2str($hex)
    {
        return \pack('H*', $hex);
    }

    public static function request($url, $method, $postdata = [], $contentType = self::DATA_XFORM, $token = null)
    {
        if ('application/json' === $contentType) {
            $postdata = \json_encode($postdata);
        } else {
            $postdata = \http_build_query($postdata);
        }

        $header = 'Content-Type: ' . $contentType;

        if ($token) {
            $header .= "\r\nAuthorization: Bearer {$token}";
        }
        $opts = [
            'http' => [
                'method' => $method,
                'header' => $header,
                'content' => $postdata,
                'ignore_errors' => true
            ]
        ];

        $context = \stream_context_create($opts);

        return \file_get_contents($url, false, $context);
    }
}
