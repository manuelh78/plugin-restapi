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

use Analog\Analog;
use Galette\Core\GaletteMail;
use Galette\Core\History;
use Galette\Core\Preferences;
use Galette\Repository\Members;

final class GaletteMailNotify extends GaletteMail
{
    private $preferences;
    private $history;

    public function __construct(Preferences $preferences, ?History $history = null)
    {
        $this->preferences = $preferences;
        $this->history = $history;
        parent::__construct($preferences);
    }

    public function notify($title, $recipients, $body)
    {
        $this->setSubject($this->preferences->pref_email_nom . " - {$title}");
        $this->setSender($this->preferences->pref_email_nom, $this->preferences->pref_email_newadh);
        $this->setRecipients($recipients);

        $message = "<body>{$body}</body>";

        $this->isHTML(true);
        $this->setMessage($message);

        $sent = $this->send();

        if (!$sent) {
            if ($this->history) {
                $txt = \preg_replace(
                    ['/%title/'],
                    [$title],
                    _T('GaletteMailNotify::notify() has failed: %title.')
                );
                $this->history->add($txt);
            }

            //Mails are disabled... We log (not safe, but)...
            Analog::log(
                'GaletteMailNotify::notify() has failed. Here was the data: ' .
                "\n" . \print_r([$title, $body], true),
                Analog::ERROR
            );
        }

        return $sent;
    }

    // add a file with a real path /var/www....
    public function addAttachment($att): void
    {
        $this->attachments[] = new GaletteMail_SFile($att);
    }

    //liste des membres du staff voulant être notifiés; ajouter #ADH_NOTIFY# dans la fiche
    public function getMailsStaff()
    {
        $ret = [];
        //if (1) return ["XXXdev@ik.me" => "M. X Manuel"];

        //FIXME : use a galette group
        $m = new Members();
        $staffMembers = $m->getStaffMembersList(true);

        foreach ($staffMembers as $member) {
            // var_dump($member);
            if (
                \preg_match('/@/', $member->email)
                && \preg_match('/#ADH_NOTIFY#/', $member->others_infos_admin)
                ) {
                $ret[$member->email] = $member->sfullname;
            }
            //echo $member->others_infos_admin;
        }

        return $ret;
    }

    public function error($title, $message): void
    {
        $this->setSubject($title);

        $recipients = [];

        foreach ($this->preferences->vpref_email_newadh as $pref_email) {
            $recipients[$pref_email] = $pref_email;
        }
        $this->setRecipients($recipients);

        $this->setMessage($message);
        $sent = $this->send();

        if (!$sent) {
            if ($this->history) {
                $txt = \preg_replace(
                    ['/%title/'],
                    [$title],
                    _T('GaletteMailNotify::error() has failed: %title.')
                );
                $this->history->add($txt);
            }
            //Mails are disabled... We log (not safe, but)...
            Analog::log(
                'GaletteMailNotify::error() has failed. Here was the data: ' .
                "\n" . \print_r([$title, $message], true),
                Analog::ERROR
            );
        }
    }
}

final class GaletteMail_SFile
{
    private $filepath;

    public function __construct(string $path)
    {
        $this->filepath = $path;
        echo "file path='" . $this->filepath . "'<br>";
    }

    public function getDestDir()
    {
        return '';
    }

    public function getFileName()
    {
        return $this->filepath;
    }
}
