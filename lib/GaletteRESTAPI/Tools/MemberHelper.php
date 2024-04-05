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

use Galette\DynamicFields\DynamicField;
use Galette\Filters\AdvancedMembersList;
use Galette\Repository\Members;
use Psr\Http\Message\ServerRequestInterface;

require 'mb.php';

final class MemberHelper
{
    private static $dynFieldNamePattern = 'info_field_';

    public static function getAvailableFields($members_fields): array
    {
        $forbidden = [
            'admin', 'staff', 'due_free', 'appears_in_list', 'active',
            'row_classes', 'oldness', 'duplicate', 'groups', 'managed_groups'
        ];

        if (!\defined('GALETTE_TESTS')) {
            $forbidden[] = 'password'; // keep that for tests only
        }

        $fields = [];

        foreach ($members_fields as $mf) {
            $propname = $mf['propname'];

            if (\in_array($propname, $forbidden, true)) {
                continue;
            }

            $fields[] = $propname;
        }

        return $fields;
    }

    public static function getDbFieldName($members_fields, $name): ?string
    {
        foreach ($members_fields as $k => $mf) {
            $propname = $mf['propname'];

            if ($propname === $name) {
                return $k;
            }
        }

        return null;
    }

    public static function getAvailableDynamicFields($member): array
    {
        $fields = [];
        $dynFields = $member->getDynamicFields();

        foreach ($dynFields->getFields() as $idx => $dynField) {
            $dynFieldName = self::$dynFieldNamePattern . $idx;
            $fields[] = $dynFieldName;
        }

        return $fields;
    }

    public static function getDynamicFieldsValues($member): array
    {
        $values = [];
        $dynFields = $member->getDynamicFields();

        foreach ($dynFields->getFields() as $idx => $dynField) {
            $dynFieldName = self::$dynFieldNamePattern . $idx;
            $fieldValues = $dynFields->getValues((int) $idx);
            $values[$dynFieldName] = $fieldValues[0]['field_val'];
        }

        return $values;
    }

    // SESSION n'est pas utilisable
    public static function getLoggedMember(): ?\Galette\Core\Login
    {
        global $login;

        return $login;
    }

    public static function loginJwtMember(ServerRequestInterface $request, \Galette\Core\Login &$login)
    {
        $jwt = $request->getAttribute('jwt_data');

        if ($jwt) {
            $jwt = (object) $jwt;

            if ($login->logIn($jwt->nick, $jwt->password)) {
                return true;
            }
        }

        return false;
    }

    public static function getNormName($member): string
    {
        $name = $member->name;
        $surname = \ucwords(\mb_strtolower($member->surname));

        return $surname . ' ' . \mb_strtoupper($name);
    }

    public static function getMemberFormattedInfos($member): array
    {
        return [
            'email' => $member->email,
            'title' => $member->stitle,
            'titleid' => isset($member->title) ? $member->title->id : 0,

            'name' => \mb_strtoupper($member->name),
            'surname' => /* \mb_ */ \ucwords(\mb_strtolower($member->surname)),
            'company_name' => \mb_ucfirst($member->company_name),

            // 'nickname' => mb_ucfirst($member->nickname),
            'sfullname' => $member->sfullname,
            'town' => \mb_strtoupper($member->getTown()),
            'zipcode' => \trim($member->getZipcode()),
            'country' => \mb_ucfirst($member->country),

            'address' => $member->getAddress(),
            'address2' => '', // $member->getAddressContinuation(),
        ];
    }

    public static function findMember(array $args)
    {
        $filters = new AdvancedMembersList();
        $adml = new AdvancedMembersList();
        $free_search = [];

        if (isset($args['email']) && '' != $args['email']) {
            $free_search[] = [
                'idx' => '1',
                'type' => DynamicField::TEXT,
                'field' => 'email_adh',
                'search' => \mb_strtolower($args['email']),
                'log_op' => AdvancedMembersList::OP_AND,
                'qry_op' => AdvancedMembersList::OP_EQUALS
            ];
        } elseif (isset($args['uid']) && $args['zipcode']) {
            $free_search[] = [
                'idx' => '1',
                'type' => DynamicField::TEXT,
                'field' => 'id_adh',
                'search' => $args['uid'],
                'log_op' => AdvancedMembersList::OP_AND,
                'qry_op' => AdvancedMembersList::OP_EQUALS
            ];
            $free_search[] = [
                'idx' => '2',
                'type' => DynamicField::TEXT,
                'field' => 'cp_adh',
                'search' => $args['zipcode'],
                'log_op' => AdvancedMembersList::OP_AND,
                'qry_op' => AdvancedMembersList::OP_EQUALS
            ];
        } else {
            return null;
        }

        $adml->__set('free_search', $free_search);
        $members = new Members($adml);

        return $members->getList(true);
    }

    public static function getLoginDefault($newMemberDatas): string
    {
        $l = $newMemberDatas['surname'] . ($newMemberDatas['zipcode'] ?? '');
        $l = \preg_replace('#[@_\\.\\-\\s]#mius', '', $l);
        $l = \strtolower($l);
        $l .= Security::generate_string(6);

        return $l;
    }
}
