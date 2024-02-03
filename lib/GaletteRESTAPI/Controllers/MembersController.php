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

namespace GaletteRESTAPI\Controllers;

use Galette\Controllers\AbstractPluginController;
use Galette\DynamicFields\DynamicField;
use Galette\Filters\AdvancedMembersList;
use Galette\Repository\Members;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

final class MembersController extends AbstractPluginController
{
    private $container;
    private $config;

    /**
     * @Inject("Plugin Galette RESTAPI")
     */
    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->container = $container;
        $this->config = $container->get('config');
    }

    public function emails(Request $request, Response $response/*, ?string $sources*/): Response
    {
        $params = (object) $request->getParsedBody();

        if (!isset($params->sources)) {
            $sources = ['galette'];
        } else {
            $sources = $params->sources;
        }

        $emails = [];

        foreach ($sources as $source) {
            switch ($source) {
                    case 'galette':
                        $filters = new AdvancedMembersList();
                        $adml = new AdvancedMembersList();
                        $free_search = [];

                        $free_search[] = [
                            'idx' => '1',
                            'type' => DynamicField::TEXT,
                            'field' => 'email_adh',
                            'search' => '',
                            'log_op' => AdvancedMembersList::OP_AND,
                            'qry_op' => AdvancedMembersList::OP_NOT_EQUALS
                        ];

                        $free_search[] = [
                            'idx' => '2',
                            'type' => DynamicField::TEXT,
                            'field' => 'activite_adh',
                            'search' => 1,
                            'log_op' => AdvancedMembersList::OP_AND,
                            'qry_op' => AdvancedMembersList::OP_EQUALS
                        ];

                        $adml->__set('free_search', $free_search);
                        $members = new Members($adml);
                        $results = $members->getMembersList(false, ['email_adh'], true, false, false, false, false);

                        foreach ($results as $r) {
                            if (\mb_strpos($r->email_adh, '@')) {
                                $emails[] = $r->email_adh;
                            }
                        }

                        break;

                    default:
                        $className = '\\GaletteRESTAPI\\Newsletter\\' . \ucfirst($source);
                        $application = new $className($this->zdb, $this->config);
                        $results = $application->getEmails();
                        $emails = \array_merge($emails, $results);

                        break;
                }
        }

        $emails = \array_unique($emails);

        /*$jwt = new \GaletteRESTAPI\Tools\JWTHelper();
        $ret = $jwt->encode(
            $ret
        );*/
        $results = \Defuse\Crypto\Crypto::encrypt(\json_encode($emails), $this->container->get('cryptokey'));

        return $response->withJson([
            'status' => 'success',
            'results' => $results
        ]);
    }
}
