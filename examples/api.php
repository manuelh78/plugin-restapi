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

require '../lib/GaletteRESTAPI/Tools/JWTHelper.php';

require '../lib/GaletteRESTAPI/Tools/Http.php';

use GaletteRESTAPI\Tools\Http;

$config = Noodlehaus\Config::load(\realpath('.') . '/../config/config.yml', new Noodlehaus\Parser\Yaml());
$cryptokey = Defuse\Crypto\Key::loadFromAsciiSafeString($config->get('cryptokey'));

function http($url, $method, $postdata, $token = '', $contentType = Http::DATA_JSON)
{
    echo "Route : {$url}<br>";

    return Http::request($url, $method, $postdata, $contentType, $token);
}

function test($name, $response)
{
    echo "<b>>>> Test : '{$name}' : </b><br>";
    echo 'Api return : ' . \var_export($response, true) . "\n<br>";

    if (!$response) {
        echo 'response is null<br>';
    } elseif (\is_object(\json_decode($response))) {
        $datas = \json_decode($response);
        echo "status : {$datas->status}<br>\n";

        if (isset($datas->message)) {
            echo "message : {$datas->message}<br>\n";
        }

        if (isset($datas->token)) {
            $jwt = new GaletteRESTAPI\Tools\JWTHelper('../config');

            $datas->jwt = $jwt->decode($datas->token);
            echo 'token jwt : <br>';
            \var_dump($datas->jwt);
        }

        global $cryptokey;

        if (isset($datas->results)) {
            $datas->results = (object) \json_decode(Defuse\Crypto\Crypto::decrypt($datas->results, $cryptokey));
            \var_dump($datas->results);
        }

        echo '<br><br>';

        return $datas;
    } else {
        echo "Response : '{$response}' <br><br>";

        return null;
    }
}
