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
use Galette\Controllers\AuthController;
use Galette\Core\Login;
use Galette\Entity\Adherent;
use Galette\Filters\MembersList;
use Galette\Repository\Members;
use GaletteRESTAPI\Newsletter\Basic as NewsletterBasic;
use GaletteRESTAPI\Tools\Debug;
use GaletteRESTAPI\Tools\GaletteMailNotify;
use GaletteRESTAPI\Tools\MemberHelper;
use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

final class MemberController extends AbstractPluginController
{
    private $config;
    private $container;

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

    public function get(Request $request, Response $response, ?string $uid): Response
    {
        //$jwtToken = (object) $request->getAttribute('jwt_data');
        $member = self::newMember();

        if (!$member->load((int) $uid)) {
            Debug::log("member [{$uid}] load failed");

            return $response->withJson(
                [
                    'status' => 'error',
                    'message' => _T('member load failed')]
            )->withStatus(404);
        }

        if (!$member->canShow(MemberHelper::getLoggedMember($request, $this->login))) {
            return $response->withJson([
                'status' => 'error',
                'message' => _T("Logged user can't show this member")
            ])->withStatus(403);
        }

        $jwt = new \GaletteRESTAPI\Tools\JWTHelper();

        $fields = MemberHelper::getAvailableFields($this->members_fields);

        $mdatas = [];

        foreach ($fields as $f) {
            $v = $member->__get($f);

            if (\is_numeric($v) || \is_string($v)) {
                $mdatas[$f] = (string) $v;
            }
        }

        $dynFields = $member->getDynamicFields();
        $fields = \array_merge($fields, MemberHelper::getAvailableDynamicFields($member));
        $mdatas = \array_merge($mdatas, MemberHelper::getDynamicFieldsValues($member));

        $results = [
            'member' => $mdatas,
            'member_card' => MemberHelper::getMemberFormattedInfos($member),
            'fields' => $fields
        ];
        $results = \Defuse\Crypto\Crypto::encrypt(\json_encode($results), $this->container->get('cryptokey'));

        Debug::log("Return member [{$uid}] " . MemberHelper::getNormName($member));

        return $response->withJson(['status' => 'success', 'results' => $results]);
    }

    //Create new user
    public function post(Request $request, Response $response): Response
    {
        $newMemberDatas = $request->getParsedBodyParam('member');

        if (isset($newMemberDatas['email'])) {
            $mf = MemberHelper::findMember(['email' => \trim($newMemberDatas['email'])]);

            if (\count($mf) > 0) {
                return $response->withJson([
                    'status' => 'error',
                    'message' => _T('Email already exist'),
                    'uid' => $mf[0]->id,
                ])->withStatus(409);
            }
        }

        $member = self::newMember();

        if (!isset($newMemberDatas['login'])) {
            $newMemberDatas['login'] = MemberHelper::getLoginDefault($newMemberDatas);
        }

        if (!$member->canCreate(MemberHelper::getLoggedMember())) {
            return $response->withJson([
                'status' => 'error',
                'message' => _T("Logged user can't create this member")
            ])->withStatus(403);
        }

        $this->memberModify($member, $newMemberDatas);

        if (!$member->store()) {
            return $response->withJson(
                [
                    'status' => 'error',
                    'message' => _T('Error when storing member')]
            )->withStatus(409);
        }

        self::removeNewsletterFree($member->email);

        return $response->withJson([
            'status' => 'success',
            'message' => _T('Created with ') . \implode(', ', \array_keys($newMemberDatas)),
            'uid' => $member->id]);
    }

    public function put(Request $request, Response $response, ?string $uid): Response
    {
        $newMemberDatas = $request->getParsedBodyParam('member');
        $member = self::newMember();

        if (!$member->load((int) $uid)) {
            Debug::log("Member [{$uid}] load failed");

            return $response->withJson(
                [
                    'status' => 'error',
                    'message' => _T('Member load failed')]
            )->withStatus(404);
        }

        if (!$member->canEdit(MemberHelper::getLoggedMember())) {
            return $response->withJson([
                'status' => 'error',
                'message' => _T("Logged user can't edit this member")
            ])->withStatus(403);
        }

        if (isset($newMemberDatas['email'])) {
            $mf = MemberHelper::findMember(['email' => \trim($newMemberDatas['email'])]);

            if (\count($mf) > 0) {
                return $response->withJson([
                    'status' => 'error',
                    'message' => _T('Email already exist'),
                    'uid' => $mf[0]->id,
                ])->withStatus(409);
            }
        }
        $this->memberModify($member, $newMemberDatas);

        if (!$member->store()) {
            return $response->withJson(
                [
                    'status' => 'error',
                    'message' => _T('Error when storing member')]
            )->withStatus(409);
        }

        return $response->withJson([
            'status' => 'success',
            'message' => _T('Set ') . \implode(', ', \array_keys($newMemberDatas))
        ]);
    }

    public function delete(Request $request, Response $response, ?string $uid): Response
    {
        $member = self::newMember(); /* new Adherent($this->zdb);
            $member->enableAllDeps();
            $member->setDependencies($this->preferences, $this->members_fields, $this->history);*/

        if (!$member->load((int) $uid)) {
            Debug::log("Member [{$uid}] does not exist");

            return $response->withJson(
                [
                    'status' => 'error',
                    'message' => _T('Member load failed')]
            )->withStatus(404);
        }

        if (!$member->canEdit(MemberHelper::getLoggedMember())) {
            return $response->withJson([
                'status' => 'error',
                'message' => _T("Logged user can't remove this member")
            ])->withStatus(403);
        }

        $filters = new MembersList();
        $members = new Members($filters);
        $members->removeMembers([$uid]);

        return $response->withJson([
            'status' => 'success',
            'message' => _T('Member is deleted ') . $uid
        ]);
    }

    public function canlogin(Request $request, Response $response): Response
    {
        $params = $request->getParsedBody();

        if (isset($params['password'])) {
            $login = new Login($this->zdb, $this->i18n);
            $login->logIn($params['login'], $params['password']);

            if ($login->isLogged()) {
                return $response->withJson([
                    'status' => 'success',
                    'uid' => $login->id
                ]);
            }
        }

        return $response->withJson([
            'status' => 'error',
            'uid' => 0
        ]);
    }

    //Find user by :
    // - email
    // - id_adh+zipcode
    public function find(Request $request, Response $response): Response
    {
        $params = $request->getParsedBody();

        $r = MemberHelper::findMember($params);
        /*
                    if (!$r) {
                        return $response->withJson(
                            [
                                'status' => 'error',
                                'message' => _T('Invalid request')
                            ]
                        )->withStatus(200);
                    }*/

        if ($r && \count($r) > 0) {
            return $response->withJson([
                'status' => 'success',
                'uid' => $r[0]->id
            ]);
        }

        $rep = $response->withJson(
            [
                'status' => 'error',
                'message' => _T('Member not found')
            ]
        );
        //return error 401 if not found
        if (isset($params['error401'])) {
            $rep->withStatus(401);
        }

        return $rep;
    }

    public function passwordlost(Request $request, Response $response): Response
    {
        $params = (object) $request->getParsedBody();
        $login = '';

        if (isset($params->login)) {
            $login = $params->login;
        }

        if (isset($params->email)) {
            $login = $params->email;
        }

        $adh = new Adherent($this->zdb, $login);

        if (isset($params->error401) && !$adh->id) {
            return $response->withJson(
                [
                    'status' => 'error',
                    'message' => _T('Member not found')
                ]
            )->withStatus(401);
        }

        $ac = new AuthController($this->container);
        $r = $ac->retrievePassword($request->withParsedBody(['login' => $login]), $response);

        return $response->withJson(
            [
                'status' => 'success',
                'message' => _T('Un email vous a été envoyé si votre identifiant est correct.') . (!$adh->id ? '..' : '')
            ]
        );
    }

    //Notify a member by mail
    public function mail(Request $request, Response $response, string $uid): Response
    {
        $params = (object) $request->getParsedBody();

        if (isset($params->title, $params->body)) {
            $emails = [];
            $mailNotify = new GaletteMailNotify($this->preferences, $this->history);

            if ('staff' != $uid) {
                if ((int) $uid > 0) {
                    $member = new Adherent($this->zdb, (int) $uid);
                } else {
                    $member = new Adherent($this->zdb, $uid);
                }

                if (!$member->id) {
                    return $response->withJson(
                        [
                            'status' => 'error',
                            'message' => _T('Member not found')
                        ]
                    )->withStatus(401);
                }
                //$emails[$member->email] = $member->email;
                $emails[$member->email] = $member->sfullname;
            } else {
                $emails = $mailNotify->getMailsStaff();
            }

            if ($mailNotify->notify(
                $params->title,
                $emails,
                $params->body
            )) {
                return $response->withJson(
                    [
                        'status' => 'success',
                        'message' => _T('The mail has been sent.')
                    ]
                );
            }

            return $response->withJson(
                [
                    'status' => 'error',
                    'message' => _T('The mail was not sent.')
                ]
            )->withStatus(401);
        }

        return $response->withJson(
            [
                'status' => 'error',
                'message' => _T('Invalid request')
            ]
        )->withStatus(401);
    }

    private function newMember()
    {
        $member = new Adherent($this->zdb);
        $member->setDependencies($this->preferences, $this->members_fields, $this->history);
        $member->enableAllDeps();

        return $member;
    }

    private function memberModify($member, $newMemberDatas): void
    {
        $members_fields = $this->members_fields;

        //get all properties names
        $fields = MemberHelper::getAvailableFields($members_fields);
        $fields[] = 'password';

        //get all dynamicfields
        $fieldsDyn = MemberHelper::getAvailableDynamicFields(self::newMember());

        //check if valid names
        foreach ($newMemberDatas as $k => $v) {
            if (!\in_array($k, $fields, true) && !\in_array($k, $fieldsDyn, true)) {
                throw new \Exception(_T('Invalid field name :') . $k);
            }
        }

        foreach ($newMemberDatas as $k => $v) {
            //convert to db name
            $dbFieldName = MemberHelper::getDbFieldName($members_fields, $k);

            if ($dbFieldName) {
                $r2[$dbFieldName] = $v;

                if ('mdp_adh' == $dbFieldName) {
                    $r2['mdp_adh2'] = $v;
                }
            } else {
                $r2["{$k}_1"] = $v;
            }
        }

        $r2['is_company'] = 1; //Evite la supression de la propriété company_name
        //$t = $member->getDynamicFields()->getValues(1);

        $member->check($r2, [], []);

        /*foreach ($r2 as $k => $v) {
            $member->validate($k, $v, []);
        }*/
    }

    private function removeNewsletterFree($email): void
    {
        //si la personne est adhérente, l'adresse email ne doit pas être dans la newsletter libre (doublon)
        foreach (\explode(',', $this->config->get('newsletter.subscribeclass')) as $t) {
            switch ($t) {
                case 'Basic':
                    $newsBasic = new NewsletterBasic($this->zdb, $this->config);
                    $newsBasic->unsubscribe($email, null, true);

                    break;
            }
        }
    }
}
