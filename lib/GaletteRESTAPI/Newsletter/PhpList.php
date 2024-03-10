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

namespace GaletteRESTAPI\Newsletter;

// Original source : phplist project

final class PhpList implements NewsletterInterface
{
    private $tablePrefix;
    private $userTablePrefix;
    private $zdb;

    public function __construct($zdb, $config)
    {
        $this->db = $zdb->db;
        $this->tablePrefix = 'phplist_';
        $this->userTablePrefix = 'phplist_user_';
    }

    public function getLabel(): string
    {
        return _T('Newsletter phplist');
    }

    public function subscribe($email, $userdata = null): int
    {
        return 0;
    }

    public function unsubscribe($email): bool
    {
        return self::_unsubscribe($email);
    }

    public function confirm($email): bool
    {
        return false;
    }

    public function getEmails(): array
    {
        return [];
    }

    public function have($email): bool
    {
        if (self::email2id($email) != false) {
            return true;
        }

        return false;
    }

    // unsubscribe an email addy from the list
    // if no list id provided, unsubscribe the user from all lists
    private function _unsubscribe($userIdOrEmail, $listId = null)
    {
        if (\is_numeric($userIdOrEmail)) {
            $userId = $userIdOrEmail;
        } else {
            $userId = $this->email2id($userIdOrEmail);
        }

        if (!$userId) {
            return false;
        }

        // unsubscribe them
        $sql = 'DELETE FROM ' . $this->tablePrefix . "listuser WHERE userid={$userId}";

        if ($listId) {
            $sql .= " AND listid='" . \addslashes($listId) . "'";
        }

        $this->db->query($sql)->execute();

        // add a note saying we unsubscribed them manually
        $sql = 'INSERT INTO ' . $this->userTablePrefix .
        'user_history (userid,date,summary) values(' .
        "'{$userId}',now()," .
        "'{$userIdOrEmail} - Unsubscribed from list " . ($listId ?: '') . " via phpList Class')";

        $this->db->query($sql)->execute();

        // If this user dont't use another list, delete it
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->tablePrefix . "listuser WHERE userid={$userId} ";
        $results = $this->db->query($sql)->execute();
        $result = $results->current();

        if (1 > $result['count']) {
            // get the new user id
            $sql = 'DELETE FROM ' . $this->userTablePrefix . "user WHERE id='" . $userId . "' ";

            $this->db->query($sql)->execute();
        }

        return true;
    }

    private function email2id($email)
    {
        $sql = 'SELECT id FROM ' . $this->userTablePrefix . "user WHERE email='" . \addslashes($email) . "'";
        $res = $this->db->query($sql)->execute();

        if ($res->count() < 1) {
            return false;
        }

        return $res->current()['id'];
    }
}
