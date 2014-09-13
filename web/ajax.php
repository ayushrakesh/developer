<?php

/**
 * File: ajax.php.
 * Author: Ulrich Block
 * Date: 03.10.12
 * Time: 17:09
 * Contact: <ulrich.block@easy-wi.com>
 *
 * This file is part of Easy-WI.
 *
 * Easy-WI is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Easy-WI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Easy-WI.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Diese Datei ist Teil von Easy-WI.
 *
 * Easy-WI ist Freie Software: Sie koennen es unter den Bedingungen
 * der GNU General Public License, wie von der Free Software Foundation,
 * Version 3 der Lizenz oder (nach Ihrer Wahl) jeder spaeteren
 * veroeffentlichten Version, weiterverbreiten und/oder modifizieren.
 *
 * Easy-WI wird in der Hoffnung, dass es nuetzlich sein wird, aber
 * OHNE JEDE GEWAEHELEISTUNG, bereitgestellt; sogar ohne die implizite
 * Gewaehrleistung der MARKTFAEHIGKEIT oder EIGNUNG FUER EINEN BESTIMMTEN ZWECK.
 * Siehe die GNU General Public License fuer weitere Details.
 *
 * Sie sollten eine Kopie der GNU General Public License zusammen mit diesem
 * Programm erhalten haben. Wenn nicht, siehe <http://www.gnu.org/licenses/>.
 */

define('EASYWIDIR', dirname(__FILE__));

if (is_dir(EASYWIDIR . '/install')) {
    die('Please remove the "install" folder');
}

include(EASYWIDIR . '/stuff/methods/functions.php');
include(EASYWIDIR . '/stuff/methods/class_validator.php');
include(EASYWIDIR . '/stuff/methods/vorlage.php');
include(EASYWIDIR . '/stuff/config.php');
include(EASYWIDIR . '/stuff/settings.php');

if (!isset($admin_id) and !isset($user_id)) {
    redirect('login.php');
} else if (isset($admin_id)) {
    $pa = User_Permissions($admin_id);
} else if (isset($user_id)) {
    $pa = User_Permissions($user_id);
}

if ($ui->smallletters('w', 9, 'get') == 'datatable') {

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 1 Jan 1900 00:00:00 GMT');
    header('Content-type: application/json');

    $array = array('iTotalRecords' => 0, 'iTotalDisplayRecords' => 0, 'aaData' => array());

    $iDisplayStart = ($ui->isinteger('iDisplayStart', 'get')) ? $ui->isinteger('iDisplayStart', 'get') : 0;
    $iDisplayLength = ($ui->isinteger('iDisplayLength', 'get')) ? $ui->isinteger('iDisplayLength', 'get') : 10;
    $iSortCol = ($ui->isinteger('iSortCol_0', 'get')) ? $ui->isinteger('iSortCol_0', 'get') : 0;
    $sSortDir = ($ui->smallletters('sSortDir_0', 4, 'get') == 'desc') ? 'DESC' : 'ASC';
    $sSearch = (strlen($ui->escaped('sSearch', 'get')) > 0) ? $ui->escaped('sSearch', 'get') : false;

    if ($ui->smallletters('d', 7, 'get') == 'userlog' and isset($user_id) and $pa['log']) {

        $sprache = getlanguagefile('logs', $user_language, $reseller_id);
        $gssprache = getlanguagefile('gserver', $user_language, $reseller_id);

        $placeholders = array('%%', '%ad%', '%add%', '%dl%', '%del%', '%md%', '%mod%', '%start%', '%restart%', '%stop%', '%upd%', '%fail%', '%ok%', '%psw%', '%cfg%', '%import%', '%reinstall%', '%backup%', '%use%');
        $replace = array('', $gsprache->add, $gsprache->add, $gsprache->del, $gsprache->del, $gsprache->mod, $gsprache->mod, $gsprache->start, $gsprache->start, $gsprache->stop, $gsprache->update,'','', $gssprache->password, $gssprache->config, $gsprache->import, $gssprache->reinstall, $gsprache->backup, $gsprache->use);
        $placeholders2 = array('%voserver%', '%gserver%', '%user%', '%fastdl%', '%master%', '%user%', '%root%', '%addon%', '%settings%', '%vserver%', '%ticket_subject%', '%reseller%', '%virtual%', '%eac%', '%resync%', '%virtualimage%', '%template%', '%voserver%', '%emailsettings%', '%dns%', '%tsdns%', '%pmode%', '%file%');
        $replace2 = array($gsprache->voiceserver, $gsprache->gameserver, $gsprache->user, $gsprache->fastdownload, $gsprache->master, $gsprache->user, $gsprache->root, $gsprache->addon2, $gsprache->settings, $gsprache->virtual, $gsprache->support, $gsprache->reseller, $gsprache->hostsystem,'Easy Anti Cheat', $gssprache->resync, $gsprache->virtual . ' ' . $gsprache->template, $gsprache->template, $gsprache->voiceserver,'E-Mail '.$gsprache->settings, 'TSDNS', 'TSDNS', $gssprache->protect, $gsprache->file);

        if ($sSearch) {
            $sSearch = str_replace($replace, $placeholders, str_replace($replace2, $placeholders2, $sSearch));
        }

        $query = $sql->prepare("SELECT COUNT(1) AS `amount` FROM `userlog` WHERE `usertype`='user' AND `userid`=? AND `resellerid`=?");
        $query->execute(array($user_id, $reseller_id));
        $array['iTotalRecords'] = $query->fetchColumn();

        if ($sSearch) {
            $query = $sql->prepare("SELECT COUNT(1) AS `amount` FROM `userlog` AS l LEFT JOIN `userdata` AS s ON s.`id`=l.`subuser` AND l.`subuser`!=0 WHERE l.`usertype`='user' AND l.`userid`=:userid AND l.`resellerid`=:resellerid AND (`username` LIKE :search OR `cname` LIKE :search OR `ip` LIKE :search OR `logdate` LIKE :search OR `useraction` LIKE :search)");
            $query->execute(array(':search' => '%' . $sSearch . '%', ':userid' => $user_id, ':resellerid' => $reseller_id));
            $array['iTotalDisplayRecords'] = $query->fetchColumn();
        } else {
            $array['iTotalDisplayRecords'] = $array['iTotalRecords'];
        }

        $orderFields = array(0 => '`logdate`', 1 => array('`username`', '`cname`'), 2 => '`useraction`', 3 => '`ip`');

        if (isset($orderFields[$iSortCol]) and is_array($orderFields[$iSortCol])) {
            $orderBy = implode(' ' . $sSortDir . ', ', $orderFields[$iSortCol]) . ' ' . $sSortDir;
        } else if (isset($orderFields[$iSortCol]) and !is_array($orderFields[$iSortCol])) {
            $orderBy = $orderFields[$iSortCol] . ' ' . $sSortDir;
        } else {
            $orderBy = '`logdate` DESC';
        }

        if ($sSearch) {
            $query = $sql->prepare("SELECT `subuser`,`username`,`useraction`,`ip`,`logdate`,`cname` FROM `userlog` AS l LEFT JOIN `userdata` AS s ON s.`id`=l.`subuser` AND l.`subuser`!=0 WHERE l.`usertype` IN ('user','cron') AND l.`userid`=:userid AND l.`resellerid`=:resellerid AND (`username` LIKE :search OR `cname` LIKE :search OR `ip` LIKE :search OR `logdate` LIKE :search OR `useraction` LIKE :search) ORDER BY {$orderBy} LIMIT {$iDisplayStart},{$iDisplayLength}");
            $query->execute(array(':search' => '%' . $sSearch . '%', ':userid' => $user_id, ':resellerid' => $reseller_id));
        } else {
            $query = $sql->prepare("SELECT `subuser`,`username`,`useraction`,`ip`,`logdate`,`cname` FROM `userlog` AS l LEFT JOIN `userdata` AS s ON s.`id`=l.`subuser` AND l.`subuser`!=0 WHERE l.`usertype` IN ('user','cron') AND l.`userid`=? AND l.`resellerid`=? ORDER BY {$orderBy} LIMIT {$iDisplayStart},{$iDisplayLength}");
            $query->execute(array($user_id, $reseller_id));
        }

        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {

            if ($row['subuser'] == 0) {
                $username = $row['username'];
                $ip = $row['ip'];
            } else {
                $username = $row['cname'];
                $ip = (isanyadmin($row['subuser'])) ? 'admin' : $row['ip'];
            }

            $array['aaData'][] = array($row['logdate'], $username, str_replace($placeholders2, $replace2, str_replace($placeholders, $replace, $row['useraction'])), $ip);
        }

    // App server
    } else if ($ui->smallletters('d', 9, 'get') =='appserver' and isset($admin_id) and isset($reseller_id) and isset($resellerLockupID) and $pa['roots']) {

        if ($reseller_id == 0) {
            $query = $sql->prepare("SELECT COUNT(`id`) AS `amount` FROM `rserverdata`");
            $query->execute();
        } else {
            $query = $sql->prepare("SELECT COUNT(`id`) AS `amount` FROM `rserverdata` AS r WHERE `resellerid`=:reseller_id OR EXISTS (SELECT 1 FROM `userdata` WHERE `resellerid`=:reseller_id AND `id`=r.`resellerid`)");
            $query->execute(array(':reseller_id' => $resellerLockupID));
        }

        $array['iTotalRecords'] = $query->fetchColumn();

        if ($sSearch) {
            if ($reseller_id == 0) {
                $query = $sql->prepare("SELECT COUNT(`id`) AS `amount` FROM `rserverdata` WHERE `id` LIKE :search OR `ip` LIKE :search OR `description` LIKE :search");
                $query->execute(array(':search' => '%' . $sSearch . '%'));
            } else {
                $query = $sql->prepare("SELECT COUNT(`id`) AS `amount` FROM `rserverdata` AS r WHERE (`resellerid`=:reseller_id OR EXISTS (SELECT 1 FROM `userdata` WHERE `resellerid`=:reseller_id AND `id`=r.`resellerid`)) AND (`id` LIKE :search OR `ip` LIKE :search OR `description` LIKE :search)");
                $query->execute(array(':search' => '%' . $sSearch . '%',':reseller_id' => $resellerLockupID));
            }
            $array['iTotalDisplayRecords'] = $query->fetchColumn();
        } else {
            $array['iTotalDisplayRecords'] = $array['iTotalRecords'];
        }

        $orderFields = array(0 => 'r.`ip`', 1 => 'r.`id`', 2 => 'r.`active`', 3 => 'r.`os`', 4 => '`gameserver_amount`', 5 => '`gameserver_ram`', 6 => 'r.`description`');

        if (isset($orderFields[$iSortCol]) and is_array($orderFields[$iSortCol])) {
            $orderBy = implode(' ' . $sSortDir . ', ', $orderFields[$iSortCol]) . ' ' . $sSortDir;
        } else if (isset($orderFields[$iSortCol]) and !is_array($orderFields[$iSortCol])) {
            $orderBy = $orderFields[$iSortCol] . ' ' . $sSortDir;
        } else {
            $orderBy = '`id` ASC';
        }

        if ($sSearch) {
            if ($reseller_id == 0) {
                $query = $sql->prepare("SELECT r.`id`,r.`ip`,r.`active`,r.`description`,r.`maxserver`,r.`ram`,r.`os`,(SELECT COUNT(1) AS `amount` FROM `gsswitch` g WHERE g.`rootID`=r.`id`) AS `gameserver_amount`,(SELECT SUM(`maxram`) AS `amount` FROM `gsswitch` g WHERE g.`rootID`=r.`id`) AS `gameserver_ram` FROM `rserverdata` r WHERE r.`id` LIKE :search OR r.`ip` LIKE :search OR r.`description` LIKE :search ORDER BY $orderBy LIMIT {$iDisplayStart},{$iDisplayLength}");
                $query->execute(array(':search' => '%' . $sSearch . '%'));
            } else {
                $query = $sql->prepare("SELECT r.`id`,r.`ip`,r.`active`,r.`description`,r.`maxserver`,r.`ram`,,r.`os`,(SELECT COUNT(1) AS `amount` FROM `gsswitch` g WHERE g.`rootID`=r.`id`) AS `gameserver_amount`,(SELECT SUM(`maxram`) AS `amount` FROM `gsswitch` g WHERE g.`rootID`=r.`id`) AS `gameserver_ram` FROM `rserverdata` r WHERE (r.`resellerid`=:reseller_id OR EXISTS (SELECT 1 FROM `userdata` WHERE `resellerid`=:reseller_id AND `id`=r.`resellerid`)) AND (r.`id` LIKE :search OR r.`ip` LIKE :search OR r.`description` LIKE :search) ORDER BY $orderBy LIMIT {$iDisplayStart},{$iDisplayLength}");
                $query->execute(array(':search' => '%' . $sSearch . '%', ':reseller_id' => $resellerLockupID));
            }
        } else {
            if ($reseller_id == 0) {
                $query = $sql->prepare("SELECT r.`id`,r.`ip`,r.`active`,r.`description`,r.`maxserver`,r.`ram`,r.`os`,(SELECT COUNT(1) AS `amount` FROM `gsswitch` g WHERE g.`rootID`=r.`id`) AS `gameserver_amount`,(SELECT SUM(`maxram`) AS `amount` FROM `gsswitch` g WHERE g.`rootID`=r.`id`) AS `gameserver_ram` FROM `rserverdata` r ORDER BY $orderBy LIMIT {$iDisplayStart},{$iDisplayLength}");
                $query->execute();
            } else {
                $query = $sql->prepare("SELECT r.`id`,r.`ip`,r.`active`,r.`description`,r.`maxserver`,r.`ram`,r.`os`,(SELECT COUNT(1) AS `amount` FROM `gsswitch` g WHERE g.`rootID`=r.`id`) AS `gameserver_amount`,(SELECT SUM(`maxram`) AS `amount` FROM `gsswitch` g WHERE g.`rootID`=r.`id`) AS `gameserver_ram` FROM `rserverdata` r WHERE r.`resellerid`=:reseller_id OR EXISTS (SELECT 1 FROM `userdata` WHERE `resellerid`=:reseller_id AND `id`=r.`resellerid`) ORDER BY $orderBy LIMIT {$iDisplayStart},{$iDisplayLength}");
                $query->execute(array(':reseller_id' => $resellerLockupID));
            }
        }

        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $array['aaData'][] = array($row['ip'], $row['id'], ($row['active'] == 'Y') ? (string) $gsprache->yes : (string) $gsprache->no, ($row['os'] == 'L') ? 'Linux' : 'Windows', (int) $row['gameserver_amount'] . '/' . (int) $row['maxserver'], (int) $row['gameserver_ram'] . '/' . (int) $row['ram'], $row['description'], returnButton($template_to_use, 'ajax_admin_buttons_ri.tpl', 'ro', 'ri', $row['id'], $gsprache->reinstall), returnButton($template_to_use, 'ajax_admin_buttons_dl.tpl', 'ro', 'dl', $row['id'], $gsprache->del), returnButton($template_to_use, 'ajax_admin_buttons_md.tpl', 'ro', 'md', $row['id'], $gsprache->mod), );
        }

    // Code wise it seems odd, but this way we can get plausible userIDs for following queries up front
    } else {

        // When searching a table combination that should provide a server and user´s loginname, firstname, lastname. First do a search at usertable and get IDs.
        // This IDs should be used for doing a `ID` IN (implode(',', $foundIDs)) as it will be faster.
    }


    die(json_encode($array));

} else if (isset($admin_id) and $pa['dedicatedServer'] and $ui->smallletters('d', 7, 'get') == 'freeips' and $reseller_id == 0) {

    if ($ui->id('userID', 10, 'get')) {

        $query = $sql->prepare("SELECT `resellerid` FROM `userdata` WHERE `id`=? LIMIT 1");
        $query->execute(array($ui->id('userID', 10, 'get')));

        $ipsAvailable = freeips(($query->fetchColumn()));

    } else {
        $ipsAvailable = array();
    }

    require_once IncludeTemplate($template_to_use,'ajax_admin_roots_ips.tpl', 'ajax');

    die;

} else if (isset($admin_id) and $pa['fastdl'] and $ui->smallletters('d', 8, 'get') == 'webmaster' and $ui->id('id', 10, 'get')) {

    $sprache = getlanguagefile('web', $user_language, $resellerLockupID);

    $maxVhost = 0;
    $maxHDD = 0;
    $webVhosts = 0;
    $leftHDD = 0;
    $totalHDD = 0;
    $totalVhosts = 0;
    $quotaActive = 'N';
    $dns = '';

    $query = $sql->prepare("SELECT m.`vhostTemplate`,m.`maxVhost`,m.`maxHDD`,m.`quotaActive`,m.`defaultdns`,(SELECT COUNT(v.`webVhostID`) AS `a` FROM `webVhost` AS v WHERE v.`webMasterID`=m.`webMasterID`) AS `totalVhosts`,(SELECT SUM(v.`hdd`) AS `a` FROM `webVhost` AS v WHERE v.`webMasterID`=m.`webMasterID`) AS `totalHDD` FROM `webMaster` AS m WHERE m.`webMasterID`=? AND m.`resellerID`=? LIMIT 1");
    $query->execute(array($ui->id('id', 10, 'get'), $resellerLockupID));
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $vhostTemplate = $row['vhostTemplate'];
        $maxVhost = (int) $row['maxVhost'];
        $maxHDD = (int) $row['maxHDD'];
        $totalVhosts = (int) $row['totalVhosts'];
        $leftHDD = (int) $row['maxHDD'] - $row['totalHDD'];
        $quotaActive = $row['quotaActive'];
        $dns = $row['defaultdns'];
    }

    require_once IncludeTemplate($template_to_use,'ajax_admin_web_master.tpl', 'ajax');

    die;

} else if (isset($admin_id) and $pa['voiceserverStats'] and $ui->smallletters('d', 15, 'get') == 'adminvoicestats' and $ui->st('w', 'get')) {

    $data = array();

    if ($ui->st('w', 'get') == 'us') {
        $query = $sql->prepare("SELECT u.`id`,u.`cname`,u.`vname`,u.`name` FROM `userdata` u INNER JOIN `voice_server` v ON u.`id`=v.`userid` AND v.`active`='Y' WHERE u.`resellerid`=? GROUP BY u.`id`");
        $query->execute(array($resellerLockupID));
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data[] = '<option value=' . $row['id'] . '>' . trim($row['cname'] . ' ' . $row['vname'] . ' ' . $row['name']) . '</option>';
        }

    } else if ($ui->st('w', 'get') == 'se') {

        $query = $sql->prepare("SELECT v.`id`,v.`ip`,v.`port`,v.`dns`,m.`usedns` FROM `voice_server` v INNER JOIN `voice_masterserver` m ON v.`masterserver`=m.`id` WHERE v.`resellerid`=? ORDER BY v.`ip`,v.`port`");
        $query->execute(array($resellerLockupID));
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data[] = '<option value=' . $row['id'] . '>' . $row['ip'] . ':' . $row['port'] . '</option>';
        }

    } else if ($ui->st('w', 'get') == 'ma') {

        $query = $sql->prepare("SELECT `id`,`ssh2ip` FROM `voice_masterserver` WHERE `resellerid`=? AND `active`='Y' ORDER BY `ssh2ip`");
        $query->execute(array($resellerLockupID));
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data[] = '<option value=' . $row['id'] . '>' . $row['ssh2ip'] . '</option>';
        }

    }

    require_once IncludeTemplate($template_to_use,'ajax_admin_voice_stats.tpl', 'ajax');

    die;

} else if (isset($user_id) and $pa['voiceserverStats'] and $ui->smallletters('d', 14, 'get') == 'uservoicestats' and $ui->st('w', 'get')) {

    $data = array();

    if ($ui->st('w', 'get') == 'se') {
        $query = $sql->prepare("SELECT v.`id`,v.`ip`,v.`port`,v.`dns`,m.`usedns` FROM `voice_server` v INNER JOIN `voice_masterserver` m ON v.`masterserver`=m.`id` WHERE v.`userid`=? AND v.`resellerid`=? AND v.`active`='Y' AND m.`active`='Y' ORDER BY v.`ip`,v.`port`");
        $query->execute(array($user_id, $resellerLockupID));
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $data[] = '<option value=' . $row['id'] . '>' . $row['ip'] . ':' . $row['port'] . '</option>';
        }
    }

    require_once IncludeTemplate($template_to_use,'ajax_userpanel_voice_stats.tpl', 'ajax');

    die;

} else if (isset($user_id) and $pa['usertickets'] and $ui->w('d', 20, 'get') == 'userTicketCategories' and $ui->id('topicName', 10, 'get')) {

    $table = array();

    $query = $sql->prepare("SELECT * FROM `ticket_topics` WHERE `maintopic`=? AND `maintopic`!=`id` AND `resellerid`=? ORDER BY `id`");
    $query2 = $sql->prepare("SELECT `text` FROM `translations` WHERE `type`='ti' AND `lang`=? AND `transID`=? AND `resellerID`=? LIMIT 1");

    $query->execute(array($ui->id('topicName', 10, 'get'), $reseller_id));
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {

        $query2->execute(array($user_language, $row['id'], $reseller_id));
        $topic = $query2->fetchColumn();

        if (empty($topic)) {

            $query2->execute(array($default_language, $row['id'], $reseller_id));
            $topic = $query2->fetchColumn();

            if (empty($topic)) {
                $topic = $row['topic'];
            }
        }

        $table[$row['id']] = $topic;
    }

    require_once IncludeTemplate($template_to_use,'ajax_userpanel_ticket_category.tpl', 'ajax');

    die;

} else if (isset($user_id) and $pa['voiceserverStats'] and $ui->w('d', 14, 'get') == 'voiceUserStats') {

    $usageArray = array();

    $dateRange = ($ui->escaped('dateRange', 'get')) ? $ui->escaped('dateRange', 'get') : date('m/d/Y', strtotime("-6 days")) . ' - ' . date('m/d/Y');
    $accuracy = (in_array($ui->st('accuracy', 'get'), array('da', 'mo'))) ? $ui->st('accuracy', 'get') : 'da';

    @list($startDate, $endDate) = explode('-', str_replace(' ', '', $dateRange));
    @list($startMonth, $startDay, $startYear) = explode('/', $startDate);
    @list($endMonth, $endDay, $endYear) = explode('/', $endDate);

    if ($endYear > 2000 and $startYear > 2000) {

        $menuStart = round((strtotime("{$endYear}-{$endMonth}-{$endDay}") - strtotime("{$startYear}-{$startMonth}-{$startDay}")) / 86400);

        $extractOrNormal = ($accuracy == 'mo') ? "CONCAT(EXTRACT(YEAR FROM `date`),'-',EXTRACT(MONTH FROM `date`))" : '`date`';

        $startDateFormatted = date('Y-m-d', strtotime($startYear . '-' . $startMonth . '-' . $startDay));
        $endDateFormatted = date('Y-m-d', strtotime($endYear . '-' . $endMonth . '-' . $endDay));

        if ($ui->id('serverID', 10, 'get')) {
            $query = $sql->prepare("SELECT $extractOrNormal AS `groupedDate`,SUM(`used`)/COUNT(`sid`) AS `averageused`,SUM(`traffic`)/1024 as `fileTrafficMB` FROM `voice_server_stats` WHERE `sid`=? AND `uid`=? AND `resellerid`=? AND `date` BETWEEN ? AND ? GROUP BY `groupedDate` ORDER BY `groupedDate`");
            $query->execute(array($ui->id('serverID', 10, 'get'), $user_id, $reseller_id, $startDateFormatted, $endDateFormatted));
        } else {
            $query = $sql->prepare("SELECT $extractOrNormal AS `groupedDate`,SUM(`used`)/COUNT(`sid`) AS `averageused`,SUM(`traffic`)/1024 as `fileTrafficMB` FROM `voice_server_stats` WHERE `uid`=? AND `resellerid`=? AND `date` BETWEEN ? AND ? GROUP BY `groupedDate` ORDER BY `groupedDate`");
            $query->execute(array($user_id, $reseller_id, $startDateFormatted, $endDateFormatted));
        }

        while($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $usageArray[] = array('y' => $row['groupedDate'], 'slots' => ceil($row['averageused']), 'traffic' => ceil($row['fileTrafficMB']));
        }
    }

    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 1 Jan 1900 00:00:00 GMT');
    header('Content-type: application/json');

    die(json_encode($usageArray));

} else if (isset($user_id) and ($pa['gserver'] or $pa['restart']) and $ui->username('mapgroup', 50, 'get')) {

    $sprache = getlanguagefile('gserver', $user_language, $reseller_id);

    $query = $sql->prepare("SELECT `mapGroup` FROM `servertypes` WHERE `shorten`=? AND `resellerid`=? LIMIT 1");
    $query->execute(array($ui->username('mapgroup', 50, 'get'), $reseller_id));
    $mapGroup = $query->fetchColumn();

    if ($mapGroup != null) {
        require_once IncludeTemplate($template_to_use,'ajax_userpanel_mapgroup.tpl', 'ajax');
    }

    die;
}

die('No Access:'.$ui->smallletters('d', 200, 'get'));