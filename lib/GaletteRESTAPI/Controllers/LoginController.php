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
use Galette\Entity\Adherent;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

final class LoginController extends AbstractPluginController
{
    /**
     * @Inject("Plugin Galette RESTAPI")
     */

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    public function home(Request $request, Response $response): Response
    {
        return $response->withJson([
            'status' => 'success',
            'message' => 'Plugin ' . RESTAPI_PREFIX . ' is ready',
            'php' => \PHP_VERSION
        ]);
    }

    public function login(Request $request, Response $response): Response
    {
        $nick = $request->getParsedBodyParam('login', '');
        $password = $request->getParsedBodyParam('password', '');

        $preferences = $this->preferences;

        if (\trim($nick) !== '' && \trim($password) !== '') {
            //Log with nick & pass
            if ($nick === $preferences->pref_admin_login) {
                $pw_superadmin = \password_verify(
                    $password,
                    $preferences->pref_admin_pass,
                );

                if (!$pw_superadmin) {
                    $pw_superadmin = (
                        \md5($password) === $preferences->pref_admin_pass
                    );
                }

                if ($pw_superadmin) {
                    $login->logAdmin($nick, $preferences);
                }
            } else {
                $this->login->logIn($nick, $password);
            }

            if ($this->login->isLogged()) {
                $this->history->add(_T('Authentication OK : '), $nick);

                $member = new Adherent($this->zdb);
                $member->load($this->login->id);

                if (!$member->isStaff() && !$member->isAdmin()) {
                    return $response->withJson([
                        'status' => 'error',
                        'message' => _T('This user is not a admin or a staff member'),
                        'uid' => $member->id
                    ])->withStatus(401);
                }

                $scope = ['all'];
                $norm_name = \GaletteRESTAPI\Tools\MemberHelper::getNormName($member);

                $jwt = new \GaletteRESTAPI\Tools\JWTHelper();
                $token = $jwt->encode([
                    'status' => 'logged',
                    'username' => $norm_name,
                    'login_uid' => $this->login->id,
                    'nick' => $nick,
                    'password' => $password,
                    'scope' => $scope
                ]);

                return $response->withJson(['status' => 'success', 'token' => $token]);
            }
        }

        $this->history->add(_T('Authentication failed : '), $nick);

        return $response->withJson(
            [
                'status' => 'error',
                'message' => _T('Authentication failed')]
        )->withStatus(401);
    }

    public function whoami(Request $request, Response $response): Response
    {
        $jwtToken = (object) $request->getAttribute('jwt_data');

        return $response->withJson([
            'status' => 'success',
            'login_uid' => $jwtToken->login_uid,
            'username' => $jwtToken->username,
        ]);
    }
}
