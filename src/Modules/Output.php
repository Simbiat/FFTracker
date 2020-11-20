<?php
declare(strict_types=1);
namespace FFTracker\Modules;

trait Output
{
    #Generalized function to get entity data
    public function TrackerGrab(string $id, string $type, bool $api = false): array
    {
        switch ($type) {
            case 'character':
                $data = $this->GetCharacter($id);
                break;
            case 'achievement':
                $data = $this->GetAchievement($id);
                break;
            case 'freecompany':
                $data = $this->GetCompany($id);
                break;
            case 'pvpteam':
                $data = $this->GetPVP($id);
                break;
            case 'linkshell':
            case 'crossworld_linkshell':
            case 'crossworldlinkshell':
                $data = $this->GetLinkshell($id);
                break;
        }
        #If request is from API - return JSON output directly to browser
        if ($api === true) {
            header("Content-Type: application/json; charset=utf-8");
            ob_start();
            ob_start("ob_gzhandler");
            echo json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
            ob_end_flush();
            @header('Content-Length: '.ob_get_length());
            ob_end_flush();
        } else {
            return $data;
        }
    }
    
    private function GetCharacter(string $id): array
    {
        #Get general information. Using *, but add name, because otherwise Achievement name overrides Character name and we do not want that
        $data = (new \SimbiatDB\Controller)->selectRow('SELECT *, `'.$this->dbprefix.'character`.`name` FROM `'.$this->dbprefix.'character` LEFT JOIN `usersys__gender` ON `'.$this->dbprefix.'character`.`genderid` = `usersys__gender`.`genderid` LEFT JOIN `'.$this->dbprefix.'clan` ON `'.$this->dbprefix.'character`.`clanid` = `'.$this->dbprefix.'clan`.`clanid` LEFT JOIN `'.$this->dbprefix.'guardian` ON `'.$this->dbprefix.'character`.`guardianid` = `'.$this->dbprefix.'guardian`.`guardianid` LEFT JOIN `'.$this->dbprefix.'nameday` ON `'.$this->dbprefix.'character`.`namedayid` = `'.$this->dbprefix.'nameday`.`namedayid` LEFT JOIN `'.$this->dbprefix.'city` ON `'.$this->dbprefix.'character`.`cityid` = `'.$this->dbprefix.'city`.`cityid` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'character`.`serverid` = `'.$this->dbprefix.'server`.`serverid` LEFT JOIN `'.$this->dbprefix.'grandcompany_rank` ON `'.$this->dbprefix.'character`.`gcrankid` = `'.$this->dbprefix.'grandcompany_rank`.`gcrankid` LEFT JOIN `'.$this->dbprefix.'achievement` ON `'.$this->dbprefix.'character`.`titleid` = `'.$this->dbprefix.'achievement`.`achievementid` WHERE `'.$this->dbprefix.'character`.`characterid` = :id;', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get old names. For now this is commented out due to cases of bullying, when the old names are learnt. They are still being collected, though for statistical purposes.
        #$data['oldnames'] = (new \SimbiatDB\Controller)->selectColumn('SELECT `name` FROM `'.$this->dbprefix.'character_names` WHERE `characterid`=:id AND `name`!=:name', [':id'=>$id, ':name'=>$data['name']]);
        #Get previous known incarnations (combination of gender and race/clan)
        $data['incarnations'] = (new \SimbiatDB\Controller)->selectAll('SELECT `genderid`, `'.$this->dbprefix.'clan`.`race`, `'.$this->dbprefix.'clan`.`clan` FROM `'.$this->dbprefix.'character_clans` LEFT JOIN `'.$this->dbprefix.'clan` ON `'.$this->dbprefix.'character_clans`.`clanid` = `'.$this->dbprefix.'clan`.`clanid` WHERE `'.$this->dbprefix.'character_clans`.`characterid`=:id AND (`'.$this->dbprefix.'character_clans`.`clanid`!=:clanid AND `'.$this->dbprefix.'character_clans`.`genderid`!=:genderid) ORDER BY `genderid` ASC, `race` ASC, `clan` ASC', [':id'=>$id, ':clanid'=>$data['clanid'], ':genderid'=>$data['genderid']]);
        #Get old servers
        $data['servers'] = (new \SimbiatDB\Controller)->selectAll('SELECT `'.$this->dbprefix.'server`.`datacenter`, `'.$this->dbprefix.'server`.`server` FROM `'.$this->dbprefix.'character_servers` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'server`.`serverid`=`'.$this->dbprefix.'character_servers`.`serverid` WHERE `'.$this->dbprefix.'character_servers`.`characterid`=:id AND `'.$this->dbprefix.'character_servers`.`serverid` != :serverid ORDER BY `datacenter` ASC, `server` ASC', [':id'=>$id, ':serverid'=>$data['serverid']]);
        #Get achievements
        $data['achievements'] = (new \SimbiatDB\Controller)->selectAll('SELECT `'.$this->dbprefix.'achievement`.`achievementid`, `'.$this->dbprefix.'achievement`.`category`, `'.$this->dbprefix.'achievement`.`subcategory`, `'.$this->dbprefix.'achievement`.`name`, `time`, `icon` FROM `'.$this->dbprefix.'character_achievement` LEFT JOIN `'.$this->dbprefix.'achievement` ON `'.$this->dbprefix.'character_achievement`.`achievementid`=`'.$this->dbprefix.'achievement`.`achievementid` WHERE `'.$this->dbprefix.'character_achievement`.`characterid` = :id AND `'.$this->dbprefix.'achievement`.`category` IS NOT NULL AND `'.$this->dbprefix.'achievement`.`achievementid` IS NOT NULL ORDER BY `time` DESC, `name` ASC', [':id'=>$id]);
        #Get affiliated groups' details
        $data['groups'] = (new \SimbiatDB\Controller)->selectAll(
            '(SELECT \'freecompany\' AS `type`, `'.$this->dbprefix.'freecompany_character`.`freecompanyid` AS `id`, `'.$this->dbprefix.'freecompany`.`name` as `name`, 1 AS `current`, `'.$this->dbprefix.'freecompany_character`.`join`, `'.$this->dbprefix.'freecompany_character`.`rankid`, `'.$this->dbprefix.'freecompany_rank`.`rankname` FROM `'.$this->dbprefix.'freecompany_character` LEFT JOIN `'.$this->dbprefix.'freecompany` ON `'.$this->dbprefix.'freecompany_character`.`freecompanyid`=`'.$this->dbprefix.'freecompany`.`freecompanyid` LEFT JOIN `'.$this->dbprefix.'freecompany_rank` ON `'.$this->dbprefix.'freecompany_character`.`freecompanyid`=`'.$this->dbprefix.'freecompany_rank`.`freecompanyid` AND `'.$this->dbprefix.'freecompany_character`.`rankid`=`'.$this->dbprefix.'freecompany_rank`.`rankid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'freecompany\' AS `type`, `'.$this->dbprefix.'freecompany_x_character`.`freecompanyid` AS `id`, `'.$this->dbprefix.'freecompany`.`name` as `name`, 0 AS `current`, NULL AS `join`, NULL AS `rankid`, NULL AS `rankname` FROM `'.$this->dbprefix.'freecompany_x_character` LEFT JOIN `'.$this->dbprefix.'freecompany` ON `'.$this->dbprefix.'freecompany_x_character`.`freecompanyid`=`'.$this->dbprefix.'freecompany`.`freecompanyid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'linkshell\' AS `type`, `'.$this->dbprefix.'linkshell_character`.`linkshellid` AS `id`, `'.$this->dbprefix.'linkshell`.`name` as `name`, 1 AS `current`, NULL AS `join`, `'.$this->dbprefix.'linkshell_character`.`rankid`, `'.$this->dbprefix.'linkshell_rank`.`rank` AS `rankname` FROM `'.$this->dbprefix.'linkshell_character` LEFT JOIN `'.$this->dbprefix.'linkshell` ON `'.$this->dbprefix.'linkshell_character`.`linkshellid`=`'.$this->dbprefix.'linkshell`.`linkshellid` LEFT JOIN `'.$this->dbprefix.'linkshell_rank` ON `'.$this->dbprefix.'linkshell_character`.`rankid`=`'.$this->dbprefix.'linkshell_rank`.`lsrankid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'linkshell\' AS `type`, `'.$this->dbprefix.'linkshell_x_character`.`linkshellid` AS `id`, `'.$this->dbprefix.'linkshell`.`name` as `name`, 0 AS `current`, NULL AS `join`, NULL AS `rankid`, NULL AS `rankname` FROM `'.$this->dbprefix.'linkshell_x_character` LEFT JOIN `'.$this->dbprefix.'linkshell` ON `'.$this->dbprefix.'linkshell_x_character`.`linkshellid`=`'.$this->dbprefix.'linkshell`.`linkshellid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'pvpteam\' AS `type`, `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` AS `id`, `'.$this->dbprefix.'pvpteam`.`name` as `name`, 1 AS `current`, NULL AS `join`, `'.$this->dbprefix.'pvpteam_character`.`rankid`, `'.$this->dbprefix.'pvpteam_rank`.`rank` AS `rankname` FROM `'.$this->dbprefix.'pvpteam_character` LEFT JOIN `'.$this->dbprefix.'pvpteam` ON `'.$this->dbprefix.'pvpteam_character`.`pvpteamid`=`'.$this->dbprefix.'pvpteam`.`pvpteamid` LEFT JOIN `'.$this->dbprefix.'pvpteam_rank` ON `'.$this->dbprefix.'pvpteam_character`.`rankid`=`'.$this->dbprefix.'pvpteam_rank`.`pvprankid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'pvpteam\' AS `type`, `'.$this->dbprefix.'pvpteam_x_character`.`pvpteamid` AS `id`, `'.$this->dbprefix.'pvpteam`.`name` as `name`, 0 AS `current`, NULL AS `join`, NULL AS `rankid`, NULL AS `rankname` FROM `'.$this->dbprefix.'pvpteam_x_character` LEFT JOIN `'.$this->dbprefix.'pvpteam` ON `'.$this->dbprefix.'pvpteam_x_character`.`pvpteamid`=`'.$this->dbprefix.'pvpteam`.`pvpteamid` WHERE `characterid`=:id)
            ORDER BY `name` ASC;',
            [':id'=>$id]
        );
        #Clean up the data from unnecessary (technical) clutter
        unset($data['clanid'], $data['namedayid'], $data['guardianid'], $data['cityid'], $data['gcrankid'], $data['achievementid'], $data['category'], $data['subcategory'], $data['howto'], $data['points'], $data['icon'], $data['item'], $data['itemicon'], $data['itemid'], $data['serverid']);
        #In case the entry is old enough (at least 1 day old) and register it for update
        if (empty($data['deleted']) && (time() - strtotime($data['updated'])) >= 86400) {
            $this->CronAdd($id, 'character');
        }
        return $data;
    }
    
    private function GetCompany(string $id): array
    {
        #Get general information
        $data = (new \SimbiatDB\Controller)->selectRow('SELECT * FROM `'.$this->dbprefix.'freecompany` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'freecompany`.`serverid`=`'.$this->dbprefix.'server`.`serverid` LEFT JOIN `'.$this->dbprefix.'grandcompany_rank` ON `'.$this->dbprefix.'freecompany`.`grandcompanyid`=`'.$this->dbprefix.'grandcompany_rank`.`gcrankid` LEFT JOIN `'.$this->dbprefix.'timeactive` ON `'.$this->dbprefix.'freecompany`.`activeid`=`'.$this->dbprefix.'timeactive`.`activeid` LEFT JOIN `'.$this->dbprefix.'estate` ON `'.$this->dbprefix.'freecompany`.`estateid`=`'.$this->dbprefix.'estate`.`estateid` LEFT JOIN `'.$this->dbprefix.'city` ON `'.$this->dbprefix.'estate`.`cityid`=`'.$this->dbprefix.'city`.`cityid` WHERE `freecompanyid`=:id', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get old names
        $data['oldnames'] = (new \SimbiatDB\Controller)->selectColumn('SELECT `name` FROM `'.$this->dbprefix.'freecompany_names` WHERE `freecompanyid`=:id AND `name`!=:name', [':id'=>$id, ':name'=>$data['name']]);
        #Get members
        $data['members'] = (new \SimbiatDB\Controller)->selectAll('SELECT `'.$this->dbprefix.'character`.`characterid`, `join`, `'.$this->dbprefix.'freecompany_rank`.`rankid`, `rankname` `name`, `avatar` FROM `'.$this->dbprefix.'freecompany_character` LEFT JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'freecompany_character`.`characterid`=`'.$this->dbprefix.'character`.`characterid` LEFT JOIN `'.$this->dbprefix.'freecompany_rank` ON `'.$this->dbprefix.'freecompany_rank`.`rankid`=`'.$this->dbprefix.'freecompany_character`.`rankid` AND `'.$this->dbprefix.'freecompany_rank`.`freecompanyid`=`'.$this->dbprefix.'freecompany_character`.`freecompanyid` WHERE `'.$this->dbprefix.'freecompany_character`.`freecompanyid`=:id ORDER BY `'.$this->dbprefix.'character`.`name` ASC', [':id'=>$id]);
        #History of ranks. Ensuring that we get only the freshest 100 entries sorted from latest to newest
        $data['ranks_history'] = (new \SimbiatDB\Controller)->selectAll('SELECT * FROM (SELECT `date`, `weekly`, `monthly`, `members` FROM `'.$this->dbprefix.'freecompany_ranking` WHERE `freecompanyid`=:id ORDER BY `date` DESC LIMIT 100) `lastranks` ORDER BY `date` ASC', [':id'=>$id]);
        #Clean up the data from unnecessary (technical) clutter
        unset($data['grandcompanyid'], $data['estateid'], $data['gcrankid'], $data['gc_rank'], $data['gc_icon'], $data['activeid'], $data['cityid'], $data['left'], $data['top'], $data['cityicon']);
        #In case the entry is old enough (at least 1 day old) and register it for update
        if (empty($data['deleted']) && (time() - strtotime($data['updated'])) >= 86400) {
            $this->CronAdd($id, 'freecompany');
        }
        return $data;
    }
    
    private function GetLinkshell(string $id): array
    {
        #Get general information
        $data = (new \SimbiatDB\Controller)->selectRow('SELECT * FROM `'.$this->dbprefix.'linkshell` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'linkshell`.`serverid`=`'.$this->dbprefix.'server`.`serverid` WHERE `linkshellid`=:id', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get old names
        $data['oldnames']=(new \SimbiatDB\Controller)->selectColumn('SELECT `name` FROM `'.$this->dbprefix.'linkshell_names` WHERE `linkshellid`=:id AND `name`<>:name', [':id'=>$id, ':name'=>$data['name']]);
        #Get members
        $data['members'] = (new \SimbiatDB\Controller)->selectAll('SELECT `'.$this->dbprefix.'linkshell_character`.`characterid`, `'.$this->dbprefix.'character`.`name`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'linkshell_rank`.`rank` FROM `'.$this->dbprefix.'linkshell_character` LEFT JOIN `'.$this->dbprefix.'linkshell_rank` ON `'.$this->dbprefix.'linkshell_rank`.`lsrankid`=`'.$this->dbprefix.'linkshell_character`.`rankid` LEFT JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'linkshell_character`.`characterid`=`'.$this->dbprefix.'character`.`characterid` WHERE `'.$this->dbprefix.'linkshell_character`.`linkshellid`=:id ORDER BY `'.$this->dbprefix.'linkshell_character`.`rankid` ASC, `'.$this->dbprefix.'character`.`name` ASC', [':id'=>$id]); 
        #Clean up the data from unnecessary (technical) clutter
        unset($data['serverid']);
        #In case the entry is old enough (at least 1 day old) and register it for update
        if (empty($data['deleted']) && (time() - strtotime($data['updated'])) >= 86400) {
            if ($data['crossworld'] == '0') {
                $this->CronAdd($id, 'linkshell');
            } else {
                $this->CronAdd($id, 'crossworldlinkshell');
            }
        }
        return $data;     
    }
    
    private function GetPVP(string $id): array
    {
        #Get general information
        $data = (new \SimbiatDB\Controller)->selectRow('SELECT * FROM `'.$this->dbprefix.'pvpteam` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'pvpteam`.`datacenterid`=`'.$this->dbprefix.'server`.`serverid` WHERE `pvpteamid`=:id', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get old names
        $data['oldnames'] = (new \SimbiatDB\Controller)->selectColumn('SELECT `name` FROM `'.$this->dbprefix.'pvpteam_names` WHERE `pvpteamid`=:id AND `name`<>:name', [':id'=>$id, ':name'=>$data['name']]);
        #Get members
        $data['members']=(new \SimbiatDB\Controller)->selectAll('SELECT `'.$this->dbprefix.'pvpteam_character`.`characterid`, `'.$this->dbprefix.'pvpteam_character`.`matches`, `'.$this->dbprefix.'character`.`name`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'pvpteam_rank`.`rank` FROM `'.$this->dbprefix.'pvpteam_character` LEFT JOIN `'.$this->dbprefix.'pvpteam_rank` ON `'.$this->dbprefix.'pvpteam_rank`.`pvprankid`=`'.$this->dbprefix.'pvpteam_character`.`rankid` LEFT JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'pvpteam_character`.`characterid`=`'.$this->dbprefix.'character`.`characterid` WHERE `'.$this->dbprefix.'pvpteam_character`.`pvpteamid`=:id ORDER BY `'.$this->dbprefix.'pvpteam_character`.`rankid` DESC, `'.$this->dbprefix.'character`.`name` ASC', [':id'=>$id]);
        #Clean up the data from unnecessary (technical) clutter
        unset($data['datacenterid']);
        #In case the entry is old enough (at least 1 day old) and register it for update
        if (empty($data['deleted']) && (time() - strtotime($data['updated'])) >= 86400) {
            $this->CronAdd($id, 'pvpteam');
        }
        return $data;   
    }
    
    private function GetAchievement(string $id): array
    {
        #Get general information
        $data = (new \SimbiatDB\Controller)->selectRow('SELECT *, (SELECT COUNT(*) FROM `'.$this->dbprefix.'character_achievement` WHERE `'.$this->dbprefix.'character_achievement`.`achievementid` = `'.$this->dbprefix.'achievement`.`achievementid`) as `count` FROM `'.$this->dbprefix.'achievement` WHERE `'.$this->dbprefix.'achievement`.`achievementid` = :id', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get random characters with this achievement
        $data['characters'] = (new \SimbiatDB\Controller)->selectAll('SELECT * FROM (SELECT `'.$this->dbprefix.'character`.`characterid`, `'.$this->dbprefix.'character`.`name`, `'.$this->dbprefix.'character`.`avatar` FROM `'.$this->dbprefix.'character_achievement` LEFT JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'character`.`characterid` = `'.$this->dbprefix.'character_achievement`.`characterid` WHERE `'.$this->dbprefix.'character_achievement`.`achievementid` = :id ORDER BY rand() LIMIT 10) t ORDER BY `name`', [':id'=>$id]);
        return $data;   
    }
    
    private function ffMetaGen(string $type, array $twigparameters, array $tempcontent): array
    {
        switch ($type) {
            case 'freecompany':
                $twigparameters['h1'] = $tempcontent['general']['name'];
                $twigparameters['title'] = $tempcontent['general']['name'].' on '.$GLOBALS['siteconfig']['site_name'];
                $twigparameters['ogdesc'] = $tempcontent['general']['name'].' in Final Fantasy XIV Tracker by '.$twigparameters['site_name'];
                $twigparameters['keywords'] .= ', '.$tempcontent['general']['name'].', '.$tempcontent['general']['server'].', '.$tempcontent['general']['gc_name'].', '.$tempcontent['general']['tag'].", ".$tempcontent['general']['area'].', '.implode(', ', array_column($tempcontent['members'], 'name'));
                $twigparameters['ogextra'] = '<link rel="alternate" type="application/json" href="'.$twigparameters['domain'].'/api/fftracker/'.$type.'/'.$tempcontent['general']['freecompanyid'].'/">';
                break;
            case 'linkshell':
                $twigparameters['h1'] = $tempcontent['general']['name'];
                $twigparameters['title'] = $tempcontent['general']['name'].' on '.$GLOBALS['siteconfig']['site_name'];
                $twigparameters['keywords'] .= ', '.$tempcontent['general']['name'].', '.$tempcontent['general']['server'].', '.implode(', ', array_column($tempcontent['members'], 'name'));
                $twigparameters['ogextra'] = '<link rel="alternate" type="application/json" href="'.$twigparameters['domain'].'/api/fftracker/'.$type.'/'.$tempcontent['general']['linkshellid'].'/">';
                break;
            case 'pvpteam':
                $twigparameters['h1'] = $tempcontent['general']['name'];
                $twigparameters['title'] = $tempcontent['general']['name'].' on '.$GLOBALS['siteconfig']['site_name'];
                $twigparameters['keywords'] .= ', '.$tempcontent['general']['name'].', '.$tempcontent['general']['datacenter'].', '.implode(', ', array_column($tempcontent['members'], 'name'));
                $twigparameters['ogextra'] = '<link rel="alternate" type="application/json" href="'.$twigparameters['domain'].'/api/fftracker/'.$type.'/'.$tempcontent['general']['pvpteamid'].'/">';
                break;
            case 'character':
                $twigparameters['h1'] = $tempcontent['general']['name'];
                $twigparameters['title'] = $tempcontent['general']['name'].' on '.$GLOBALS['siteconfig']['site_name'];
                $twigparameters['keywords'] .= ', '.$tempcontent['general']['name'].', '.$tempcontent['general']['server'].(empty($tempcontent['general']['title']) ? '' : ', '.$tempcontent['general']['title']).', '.$tempcontent['general']['race'].', '.$tempcontent['general']['clan'].', '.$tempcontent['general']['city'].(empty($tempcontent['general']['rankname']) ? '' : ', '.$tempcontent['general']['rankname']).(empty($tempcontent['groups']['freecompany']['name']) ? '' : ', '.$tempcontent['groups']['freecompany']['name']).(empty($tempcontent['general']['gc_name']) ? '' : ', '.$tempcontent['general']['gc_name']).(empty($tempcontent['general']['gc_rank']) ? '' : ', '.$tempcontent['general']['gc_rank']);
                $profname = explode(' ', $tempcontent['general']['name']);
                $twigparameters['ogextra'] = '
                    <meta property="og:type" content="profile" />
                    <meta property="profile:first_name" content="'.htmlspecialchars($profname[0]).'" />
                    <meta property="profile:last_name" content="'.htmlspecialchars($profname[1]).'" />
                    <meta property="profile:username" content="'.htmlspecialchars($tempcontent['general']['name']).'" />
                    <meta property="profile:gender" content="'.htmlspecialchars($tempcontent['general']['gender']).'" />
                    <link rel="alternate" type="application/json" href="'.$twigparameters['domain'].'/api/fftracker/'.$type.'/'.$tempcontent['general']['characterid'].'/">';
                $twigparameters['ogtype'] = 'profile';
                break;
            case 'achievement':
                $twigparameters['h1'] = $tempcontent['general']['name'];
                $twigparameters['title'] = $tempcontent['general']['name']." on ".$GLOBALS['siteconfig']['site_name'];
                $twigparameters['keywords'] .= ", ".$tempcontent['general']['name'];
                if (!empty($tempcontent['general']['item'])) {
                    $twigparameters['keywords'] .= ", ".$tempcontent['general']['item'];
                }
                $twigparameters['ogextra'] = '<link rel="alternate" type="application/json" href="'.$twigparameters['domain'].'/api/fftracker/'.$type.'/'.$tempcontent['general']['achievementid'].'/">';
                break;
        }
        return $twigparameters;
    }
}
?>