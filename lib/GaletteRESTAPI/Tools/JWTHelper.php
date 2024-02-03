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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JWTHelper
{
    public function __construct($pathFileKey = RESTAPI_CONFIGPATH)
    {
        $issuedAt = \time();
        $expirationTime = $issuedAt + 60;  // jwt valid for 60 seconds from the issued time

        $this->payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime
        ];
        $this->alg = 'HS256';
        $this->pathFileKey = $pathFileKey;
    }

    public function encode($datas)
    {
        $payload = \array_merge($this->payload, $datas);

        return \Firebase\JWT\JWT::encode($payload, self::getPrivateKey($this->pathFileKey), $this->alg);
    }

    public function decode(string $jwt)
    {
        return \Firebase\JWT\JWT::decode($jwt, new Key(self::getPrivateKey($this->pathFileKey), $this->alg));
    }

    public static function getPrivateKey($pathFileKey = RESTAPI_CONFIGPATH)
    {
        return \file_get_contents($pathFileKey . '/private.key');
    }
}
