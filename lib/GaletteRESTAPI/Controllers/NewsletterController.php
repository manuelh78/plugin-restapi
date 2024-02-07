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

namespace GaletteRESTAPI\Controllers;

use Defuse\Crypto\Crypto;
use Galette\Controllers\AbstractPluginController;
use GaletteRESTAPI\Tools\GaletteMailNotify;
use GaletteRESTAPI\Tools\JsonResponse;
use GaletteRESTAPI\Tools\MemberHelper;
use GaletteRESTAPI\Tools\Security;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

final class NewsletterController extends AbstractPluginController
{
    /**
     * @Inject("Plugin Galette RESTAPI")
     */
    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->config = $container->get('config');
        $this->container = $container;
    }

    // 192.168.1.99/gestion/galette/webroot/plugins/restapi/api/newsletter/add
    public function add(Request $request, Response $response): Response
    {
        $params = (object) $request->getParsedBody();

        if (!isset($params->urlcallback)) {
            throw new \Exception(_T('urlcallback is not set.'));
        }

        $email = isset($params->email) ? \trim(\filter_var($params->email, \FILTER_VALIDATE_EMAIL)) : '';
        $userdata = isset($params->userdata) ? \filter_var($params->userdata, \FILTER_SANITIZE_STRING) : null;

        if (!$email) {
            return JsonResponse::withJson(
                $response,
                [
                    'status' => 'error',
                    'message' => _T("Le format de l'adresse n'est pas correct.")]
            )->withStatus(404);
        }

        $mf = MemberHelper::findMember(['email' => $email]);

        if (\count($mf) > 0) {
            return JsonResponse::withJson(
                $response,
                [
                    'status' => 'error',
                    'message' => _T('Cette adresse est déjà enregistrée !')
                ]
            )->withStatus(404);
        }

        // $newsBasic = new \GaletteRESTAPI\Newsletter\Basic($this->zdb, $this->config);
        $t = $this->config->get('newsletter.subscribeclass');
        $className = '\\GaletteRESTAPI\\Newsletter\\' . \ucfirst($t);
        $application = new $className($this->zdb, $this->config);

        $newId = $application->subscribe($email, $userdata);

        $codeValid = Crypto::encrypt("{$newId};{$email}", $this->container->get('cryptokey'));
        $mailNotify = new GaletteMailNotify($this->preferences, $this->history);

        if (!$mailNotify->notify(
            _T('Votre inscription à la newsletter'),
            [$email => $email],
            $this->container->get('twig')->render(
                'mail_newsletter_confirm.twig',
                [
                    'url_subscribe_confirm' => $params->urlcallback . '/' . $codeValid,
                    'email' => $email,
                    'asso_name' => $this->preferences->pref_email_nom,
                    'config' => $this->config
                ]
            )
        )) {
            return JsonResponse::withJson(
                $response,
                [
                    'status' => 'error',
                    'message' => _T("Une erreur est survenue lors de l'envoi du mail de validation. ") . $email
                ]
            )->withStatus(404);
        }

        return JsonResponse::withJson($response, [
            'status' => 'success',
            'message' => _T('Adresse email ajoutée.'),
            'email' => $email,
            'uid' => $newId,
            'codeValid' => RESTAPI_DEBUG ? $codeValid : '',
        ]);
    }

    // 192.168.1.99/gestion/galette/webroot/plugins/restapi/api/newsletter/confirm/xxxx
    public function confirm_add(Request $request, Response $response, ?string $codeValid): Response
    {
        $params = (object) $request->getParsedBody();

        if (!isset($codeValid)) {
            return JsonResponse::withJson(
                $response,
                [
                    'status' => 'error',
                    'message' => _T("L'adresse est invalide")
                ]
            )->withStatus(404);
        }

        [$nid, $email] = \explode(';', Crypto::decrypt($codeValid, $this->container->get('cryptokey')));
        $nid = (int) $nid;
        $email = \trim($email);

        $t = $this->config->get('newsletter.subscribeclass');
        $className = '\\GaletteRESTAPI\\Newsletter\\' . \ucfirst($t);
        $application = new $className($this->zdb, $this->config);
        $application->confirm($email/* , $nid */);

        return JsonResponse::withJson($response, [
            'status' => 'success',
            'message' => 'Votre adresse email a été validée.'
        ]);
    }

    public function remove(Request $request, Response $response): Response
    {
        $params = (object) Security::sanitize_array($request->getParsedBody());

        if (!isset($params->urlcallback)) {
            throw new \Exception(_T('urlcallback is not set.'));
        }

        $email = isset($params->email) ? \trim(\filter_var($params->email, \FILTER_VALIDATE_EMAIL)) : null;

        if (!$email) {
            return JsonResponse::withJson(
                $response,
                [
                    'status' => 'error',
                    'message' => _T("L'adresse email est incorrecte.")
                ]
            )->withStatus(404);
        }

        $r = [];
        $select = $this->zdb->sql->select('galette_adherents');
        $select->where(['email_adh' => $email]);
        $find = $this->zdb->execute($select);

        if (\count($find) > 0) {
            $r[] = _T('Fiche adherent');
        }

        // Autres
        foreach (\explode(',', $this->config->get('newsletter.unsubscribeclass')) as $t) {
            $bOk = false;
            $className = '\\GaletteRESTAPI\\Newsletter\\' . \ucfirst($t);
            $application = new $className($this->zdb, $this->config);

            if ($application->have($email)) {
                $r[] = $application->getLabel();
            }
        }

        if (\count($r) < 1) {
            if (\count($r) < 1) {
                return JsonResponse::withJson($response, [
                    'status' => 'success',
                    'message' => _T("Cette adresse email n'est pas utilisée."),
                    'tables' => $r
                ]);
            }
        }

        // Envoi un email de demande de confirmation
        $codeValid = Crypto::encrypt($email, $this->container->get('cryptokey'));
        $mailNotify = new GaletteMailNotify($this->preferences, $this->history);

        if (!$mailNotify->notify(
            _T('Votre désinscription de la newsletter'),
            [$email => $email],
            $this->container->get('twig')->render(
                'mail_unsubscribe_confirm.twig',
                [
                    'url_unsubscribe_confirm' => $params->urlcallback . '/' . $codeValid,
                    'email' => $email,
                    'asso_name' => $this->preferences->pref_email_nom,
                    'config' => $this->config
                ]
            )
        )) {
            return JsonResponse::withJson(
                $response,
                [
                    'status' => 'error',
                    'message' => _T("Une erreur est survenue lors de l'envoi du mail de validation. ") . $email
                ]
            )->withStatus(404);
        }

        return JsonResponse::withJson($response, [
            'status' => 'success',
            'message' => _T("Un courriel de validation a été envoyé. L'adresse email sera retirée en cliquant sur le lien de confirmation."),
            'tables' => $r
        ]);
    }

    public function confirm_remove(Request $request, Response $response, ?string $codeValid): Response
    {
        $email = Crypto::decrypt($codeValid, $this->container->get('cryptokey'));

        $r = [];
        $now = \date('d/m/Y');

        // Dans la fiche galette de l'adhérent ?
        $update = $this->zdb->sql->update('galette_adherents');
        $update->set(['activite_adh' => 0])->where(['email_adh' => $email]);
        $sql = $this->zdb->sql->getSqlStringForSqlObject($update);
        $changed = $this->zdb->execute($update);

        if (\count($changed) > 0) {
            $r[] = _T('Fiche adherent');
            // Garder une trace de l'adresse email
            $replace = $email;
            $update = $this->zdb->sql->update('galette_adherents');
            $update->set([
                'info_adh' => new \Laminas\Db\Sql\Expression("CONCAT(info_adh, '\n---\n{$now} - Desabonnement par formulaire : {$replace} \n')"),
                'email_adh' => null
            ])
                ->where(['email_adh' => $email]);
            $sql = $this->zdb->sql->getSqlStringForSqlObject($update);
            $changed = $this->zdb->execute($update);
        }

        // Autres

        foreach (\explode(',', $this->config->get('newsletter.unsubscribeclass')) as $t) {
            $bOk = false;
            $className = '\\GaletteRESTAPI\\Newsletter\\' . \ucfirst($t);
            $application = new $className($this->zdb, $this->config);
            $bOk = $application->unsubscribe($email, null, true);

            if ($bOk) {
                $r[] = $application->getLabel();
            }
        }

        return JsonResponse::withJson($response, [
            'status' => 'success',
            'message' => _T("L'adresse email a été retirée."),
            'tables' => $r
        ]);
    }
}
