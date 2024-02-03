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

//Use composer https://github.com/hassankhan/config

namespace GaletteRESTAPI\Tools;

use GaletteRESTAPI\Tools\Debug as Debug;

final class Config extends \Noodlehaus\Config
{
    private $path;

    public function __construct($values, ?ParserInterface $parser = null, $string = false)
    {
        $this->path = $values;

        try {
            parent::__construct($values, new \Noodlehaus\Parser\Yaml(), false);
        } catch (\Exception $e) {
            Debug::log("Error load file {$this->path}");
        }
    }

    public function writeFile(): void
    {
        try {
            $this->toFile($this->path, new \Noodlehaus\Writer\Yaml());
        } catch (\Exception $e) {
            Debug::log("Error Write file {$this->path} " . $e->getMessage());
        }
    }

    public function get($name, $default = null)
    {
        $v = parent::get($name, $default);

        if (\is_string($v)) {
            return \trim($v);
        }

        return $v;
    }
}
