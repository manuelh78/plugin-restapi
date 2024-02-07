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

namespace GaletteRESTAPI\Tools;

final class Security
{
    public static function sanitize_array($postValues): ?array
    {
        if (false === (null === $postValues)) {
            foreach ($postValues as $k => &$pv) {
                $pv = self::sanitizeFilterString($pv, []);
            }

            return $postValues;
        }

        return null;
    }

    public static function sanitize_var(&$text)
    {
        // \filter_var($text, \FILTER_SANITIZE_STRING) . 'x';
        if (\is_string($text)) {
            $text = self::sanitizeFilterString($text);
        }

        return $text;
    }

    public static function generate_string(
        $strength = 4,
        $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
    ) {
        $input_length = \strlen($input);
        $random_string = '';

        for ($i = 0; $i < $strength; ++$i) {
            $random_character = $input[\mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }

        return $random_string;
    }

    private static function sanitizeFilterString($value, array $flags = []): string
    {
        $noQuotes = \in_array(\FILTER_FLAG_NO_ENCODE_QUOTES, $flags, true);
        $options = ($noQuotes ? \ENT_NOQUOTES : \ENT_QUOTES) | \ENT_SUBSTITUTE;
        $optionsDecode = ($noQuotes ? \ENT_QUOTES : \ENT_NOQUOTES) | \ENT_SUBSTITUTE;

        // Strip the tags
        $value = \strip_tags($value);

        // Run the replacement for FILTER_SANITIZE_STRING
        $value = \htmlspecialchars($value, $options);

        // Fix that HTML entities are converted to entity numbers instead of entity name (e.g. ' -> &#34; and not ' -> &quote;)
        // https://stackoverflow.com/questions/64083440/use-php-htmlentities-to-convert-special-characters-to-their-entity-number-rather
        $value = \str_replace(['&quot;', '&#039;'], ['&#34;', '&#39;'], $value);

        // Decode all entities
        return \html_entity_decode($value, $optionsDecode);
    }
}
