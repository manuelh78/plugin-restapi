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

// Newsletter sans être inscrit dans galette
final class Basic implements NewsletterInterface
{
    private $table_prefix;
    private $usertable_prefix;
    private $zdb;

    public function __construct($zdb, $config)
    {
        $this->zdb = $zdb;
    }

    public function getLabel(): string
    {
        return _T('Newsletter');
    }

    public function subscribe($email, $userdata = null): int
    {
        try {
            // table newsletter ?
            $select = $this->zdb->sql->select('newsletter');
            $select->where(['email' => $email]);
            $ret = $this->zdb->execute($select);
        } catch (\Exception $e) {
            throw new \Exception(_T("Une erreur est survenue lors de l'interrogation de la table des non inscrits."));
        }

        if ($ret->count() > 0) {
            throw new \Exception(_T('Vous êtes déjà inscrit.'));
        }

        try {
            // insert email
            $insert = $this->zdb->sql->insert('newsletter');
            $insert->values(['email' => $email, 'valid' => 0, 'userdata' => $userdata, 'date' => new \Laminas\Db\Sql\Expression('NOW()')]);

            // $sql = $this->zdb->sql->getSqlStringForSqlObject($insert);

            $add = $this->zdb->execute($insert);
            $newId = $this->zdb->driver->getLastGeneratedValue();
        } catch (\Exception $e) {
            throw new \Exception(_T("Une erreur est survenue lors de l'ajout de l'adresse dans la table des non inscrits."));
        }

        return (int) $newId;
    }

    public function confirm($email /* ,$nid */): bool
    {
        $select = $this->zdb->sql->select('newsletter');
        $select->where(['valid' => 1, /* 'id' => $nid, */ 'email' => $email]);
        $find = $this->zdb->execute($select);

        if ($find->count() > 0) {
            throw new \Exception(_T('Votre adresse a déjà été validée !'));
        }

        // update 'valid' column for this email&id
        $update = $this->zdb->sql->update('newsletter');
        $update->set(['valid' => 1])->where([/* 'id' => $nid, */ 'email' => $email]);

        // $sql = $this->zdb->sql->getSqlStringForSqlObject($update);

        $changed = $this->zdb->execute($update);

        if ($changed->count() < 1) {
            throw new \Exception(_T("Validation de l'adresse impossible."));
        }

        return true;
    }

    public function unsubscribe($email): bool
    {
        $delete = $this->zdb->sql->delete('newsletter');
        $delete->where(['email' => $email]);
        $changed = $this->zdb->execute($delete);

        return \count($changed) > 0;
    }

    public function getEmails(): array
    {
        $select = $this->zdb->sql->select('newsletter');
        $select->columns(['email']);
        $select->where(['valid' => 1]);

        $ret = [];

        foreach ($this->zdb->execute($select) as $r) {
            $ret[] = $r->email;
        }

        return $ret;
    }

    public function have($email): bool
    {
        $select = $this->zdb->sql->select('newsletter');
        $select->where(['email' => $email]);
        $find = $this->zdb->execute($select);

        return $find->count() > 0;
    }
}
