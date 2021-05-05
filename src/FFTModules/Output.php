<?php
declare(strict_types=1);
namespace Simbiat\FFTModules;

trait Output
{    
    #Generalized function to get entity data
    public function TrackerGrab(string $type, string $id): array
    {
        return match($type) {
            'character' => $this->GetCharacter($id),
            'achievement' => $this->GetAchievement($id),
            'freecompany' => $this->GetCompany($id),
            'pvpteam' => $this->GetPVP($id),
            'linkshell', 'crossworld_linkshell', 'crossworldlinkshell' => $this->GetLinkshell($id),
            default => [],
        };
    }
    
    private function GetCharacter(string $id): array
    {
        $dbcon = (new \Simbiat\Database\Controller);
        #Get general information. Using *, but add name, because otherwise Achievement name overrides Character name and we do not want that
        $data = $dbcon->selectRow('SELECT *, `'.$this->dbprefix.'character`.`name`, `'.$this->dbprefix.'character`.`updated` FROM `'.$this->dbprefix.'character` LEFT JOIN `'.$this->dbprefix.'clan` ON `'.$this->dbprefix.'character`.`clanid` = `'.$this->dbprefix.'clan`.`clanid` LEFT JOIN `'.$this->dbprefix.'guardian` ON `'.$this->dbprefix.'character`.`guardianid` = `'.$this->dbprefix.'guardian`.`guardianid` LEFT JOIN `'.$this->dbprefix.'nameday` ON `'.$this->dbprefix.'character`.`namedayid` = `'.$this->dbprefix.'nameday`.`namedayid` LEFT JOIN `'.$this->dbprefix.'city` ON `'.$this->dbprefix.'character`.`cityid` = `'.$this->dbprefix.'city`.`cityid` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'character`.`serverid` = `'.$this->dbprefix.'server`.`serverid` LEFT JOIN `'.$this->dbprefix.'grandcompany_rank` ON `'.$this->dbprefix.'character`.`gcrankid` = `'.$this->dbprefix.'grandcompany_rank`.`gcrankid` LEFT JOIN `'.$this->dbprefix.'achievement` ON `'.$this->dbprefix.'character`.`titleid` = `'.$this->dbprefix.'achievement`.`achievementid` WHERE `'.$this->dbprefix.'character`.`characterid` = :id;', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get old names. For now this is commented out due to cases of bullying, when the old names are learnt. They are still being collected, though for statistical purposes.
        #$data['oldnames'] = $dbcon->selectColumn('SELECT `name` FROM `'.$this->dbprefix.'character_names` WHERE `characterid`=:id AND `name`!=:name', [':id'=>$id, ':name'=>$data['name']]);
        #Get levels
        $data['jobs'] = $dbcon->selectPair('SELECT `'.$this->dbprefix.'job`.`name` AS `job`, `level` FROM `'.$this->dbprefix.'character_jobs` INNER JOIN `'.$this->dbprefix.'job` ON `'.$this->dbprefix.'job`.`jobid`=`'.$this->dbprefix.'character_jobs`.`jobid` WHERE `characterid`=:id;', [':id'=>$id]);
        #Get previous known incarnations (combination of gender and race/clan)
        $data['incarnations'] = $dbcon->selectAll('SELECT `genderid`, `'.$this->dbprefix.'clan`.`race`, `'.$this->dbprefix.'clan`.`clan` FROM `'.$this->dbprefix.'character_clans` LEFT JOIN `'.$this->dbprefix.'clan` ON `'.$this->dbprefix.'character_clans`.`clanid` = `'.$this->dbprefix.'clan`.`clanid` WHERE `'.$this->dbprefix.'character_clans`.`characterid`=:id AND (`'.$this->dbprefix.'character_clans`.`clanid`!=:clanid AND `'.$this->dbprefix.'character_clans`.`genderid`!=:genderid) ORDER BY `genderid` ASC, `race` ASC, `clan` ASC', [':id'=>$id, ':clanid'=>$data['clanid'], ':genderid'=>$data['genderid']]);
        #Get old servers
        $data['servers'] = $dbcon->selectAll('SELECT `'.$this->dbprefix.'server`.`datacenter`, `'.$this->dbprefix.'server`.`server` FROM `'.$this->dbprefix.'character_servers` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'server`.`serverid`=`'.$this->dbprefix.'character_servers`.`serverid` WHERE `'.$this->dbprefix.'character_servers`.`characterid`=:id AND `'.$this->dbprefix.'character_servers`.`serverid` != :serverid ORDER BY `datacenter` ASC, `server` ASC', [':id'=>$id, ':serverid'=>$data['serverid']]);
        #Get achievements
        $data['achievements'] = $dbcon->selectAll('SELECT `'.$this->dbprefix.'achievement`.`achievementid`, `'.$this->dbprefix.'achievement`.`category`, `'.$this->dbprefix.'achievement`.`subcategory`, `'.$this->dbprefix.'achievement`.`name`, `time`, `icon` FROM `'.$this->dbprefix.'character_achievement` LEFT JOIN `'.$this->dbprefix.'achievement` ON `'.$this->dbprefix.'character_achievement`.`achievementid`=`'.$this->dbprefix.'achievement`.`achievementid` WHERE `'.$this->dbprefix.'character_achievement`.`characterid` = :id AND `'.$this->dbprefix.'achievement`.`category` IS NOT NULL AND `'.$this->dbprefix.'achievement`.`achievementid` IS NOT NULL ORDER BY `time` DESC, `name` ASC', [':id'=>$id]);
        #Get affiliated groups' details
        $data['groups'] = $dbcon->selectAll(
            '(SELECT \'freecompany\' AS `type`, `'.$this->dbprefix.'freecompany_character`.`freecompanyid` AS `id`, `'.$this->dbprefix.'freecompany`.`name` as `name`, 1 AS `current`, `'.$this->dbprefix.'freecompany_character`.`join`, `'.$this->dbprefix.'freecompany_character`.`rankid`, `'.$this->dbprefix.'freecompany_rank`.`rankname`, `'.$this->dbprefix.'freecompany`.`crest` AS `icon` FROM `'.$this->dbprefix.'freecompany_character` LEFT JOIN `'.$this->dbprefix.'freecompany` ON `'.$this->dbprefix.'freecompany_character`.`freecompanyid`=`'.$this->dbprefix.'freecompany`.`freecompanyid` LEFT JOIN `'.$this->dbprefix.'freecompany_rank` ON `'.$this->dbprefix.'freecompany_character`.`freecompanyid`=`'.$this->dbprefix.'freecompany_rank`.`freecompanyid` AND `'.$this->dbprefix.'freecompany_character`.`rankid`=`'.$this->dbprefix.'freecompany_rank`.`rankid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'freecompany\' AS `type`, `'.$this->dbprefix.'freecompany_x_character`.`freecompanyid` AS `id`, `'.$this->dbprefix.'freecompany`.`name` as `name`, 0 AS `current`, NULL AS `join`, NULL AS `rankid`, NULL AS `rankname`, `'.$this->dbprefix.'freecompany`.`crest` AS `icon` FROM `'.$this->dbprefix.'freecompany_x_character` LEFT JOIN `'.$this->dbprefix.'freecompany` ON `'.$this->dbprefix.'freecompany_x_character`.`freecompanyid`=`'.$this->dbprefix.'freecompany`.`freecompanyid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT IF(`crossworld`=1, \'crossworld_linkshell\', \'linkshell\') AS `type`, `'.$this->dbprefix.'linkshell_character`.`linkshellid` AS `id`, `'.$this->dbprefix.'linkshell`.`name` as `name`, 1 AS `current`, NULL AS `join`, `'.$this->dbprefix.'linkshell_character`.`rankid`, `'.$this->dbprefix.'linkshell_rank`.`rank` AS `rankname`, NULL AS `icon` FROM `'.$this->dbprefix.'linkshell_character` LEFT JOIN `'.$this->dbprefix.'linkshell` ON `'.$this->dbprefix.'linkshell_character`.`linkshellid`=`'.$this->dbprefix.'linkshell`.`linkshellid` LEFT JOIN `'.$this->dbprefix.'linkshell_rank` ON `'.$this->dbprefix.'linkshell_character`.`rankid`=`'.$this->dbprefix.'linkshell_rank`.`lsrankid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT IF(`crossworld`=1, \'crossworld_linkshell\', \'linkshell\') AS `type`, `'.$this->dbprefix.'linkshell_x_character`.`linkshellid` AS `id`, `'.$this->dbprefix.'linkshell`.`name` as `name`, 0 AS `current`, NULL AS `join`, NULL AS `rankid`, NULL AS `rankname`, NULL AS `icon` FROM `'.$this->dbprefix.'linkshell_x_character` LEFT JOIN `'.$this->dbprefix.'linkshell` ON `'.$this->dbprefix.'linkshell_x_character`.`linkshellid`=`'.$this->dbprefix.'linkshell`.`linkshellid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'pvpteam\' AS `type`, `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` AS `id`, `'.$this->dbprefix.'pvpteam`.`name` as `name`, 1 AS `current`, NULL AS `join`, `'.$this->dbprefix.'pvpteam_character`.`rankid`, `'.$this->dbprefix.'pvpteam_rank`.`rank` AS `rankname`, `'.$this->dbprefix.'pvpteam`.`crest` AS `icon` FROM `'.$this->dbprefix.'pvpteam_character` LEFT JOIN `'.$this->dbprefix.'pvpteam` ON `'.$this->dbprefix.'pvpteam_character`.`pvpteamid`=`'.$this->dbprefix.'pvpteam`.`pvpteamid` LEFT JOIN `'.$this->dbprefix.'pvpteam_rank` ON `'.$this->dbprefix.'pvpteam_character`.`rankid`=`'.$this->dbprefix.'pvpteam_rank`.`pvprankid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'pvpteam\' AS `type`, `'.$this->dbprefix.'pvpteam_x_character`.`pvpteamid` AS `id`, `'.$this->dbprefix.'pvpteam`.`name` as `name`, 0 AS `current`, NULL AS `join`, NULL AS `rankid`, NULL AS `rankname`, `'.$this->dbprefix.'pvpteam`.`crest` AS `icon` FROM `'.$this->dbprefix.'pvpteam_x_character` LEFT JOIN `'.$this->dbprefix.'pvpteam` ON `'.$this->dbprefix.'pvpteam_x_character`.`pvpteamid`=`'.$this->dbprefix.'pvpteam`.`pvpteamid` WHERE `characterid`=:id)
            ORDER BY `current` DESC, `name` ASC;',
            [':id'=>$id]
        );
        #Clean up the data from unnecessary (technical) clutter
        unset($data['clanid'], $data['namedayid'], $data['achievementid'], $data['category'], $data['subcategory'], $data['howto'], $data['points'], $data['icon'], $data['item'], $data['itemicon'], $data['itemid'], $data['serverid']);
        #In case the entry is old enough (at least 1 day old) and register it for update
        if (empty($data['deleted']) && (time() - strtotime($data['updated'])) >= 86400) {
            (new \Simbiat\Cron)->add('ffentityupdate', ['character', $id], priority: 1, message: 'Updating character with ID '.$id);
        }
        unset($dbcon);
        return $data;
    }
    
    private function GetCompany(string $id): array
    {
        $dbcon = (new \Simbiat\Database\Controller);
        #Get general information
        $data = $dbcon->selectRow('SELECT * FROM `'.$this->dbprefix.'freecompany` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'freecompany`.`serverid`=`'.$this->dbprefix.'server`.`serverid` LEFT JOIN `'.$this->dbprefix.'grandcompany_rank` ON `'.$this->dbprefix.'freecompany`.`grandcompanyid`=`'.$this->dbprefix.'grandcompany_rank`.`gcrankid` LEFT JOIN `'.$this->dbprefix.'timeactive` ON `'.$this->dbprefix.'freecompany`.`activeid`=`'.$this->dbprefix.'timeactive`.`activeid` LEFT JOIN `'.$this->dbprefix.'estate` ON `'.$this->dbprefix.'freecompany`.`estateid`=`'.$this->dbprefix.'estate`.`estateid` LEFT JOIN `'.$this->dbprefix.'city` ON `'.$this->dbprefix.'estate`.`cityid`=`'.$this->dbprefix.'city`.`cityid` WHERE `freecompanyid`=:id', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        
        #Get old names
        $data['oldnames'] = $dbcon->selectColumn('SELECT `name` FROM `'.$this->dbprefix.'freecompany_names` WHERE `freecompanyid`=:id AND `name`!=:name', [':id'=>$id, ':name'=>$data['name']]);
        #Get members
        $data['members'] = $dbcon->selectAll('SELECT `'.$this->dbprefix.'character`.`characterid`, `join`, `'.$this->dbprefix.'freecompany_rank`.`rankid`, `rankname` AS `rank`, `name`, `avatar` FROM `'.$this->dbprefix.'freecompany_character` LEFT JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'freecompany_character`.`characterid`=`'.$this->dbprefix.'character`.`characterid` LEFT JOIN `'.$this->dbprefix.'freecompany_rank` ON `'.$this->dbprefix.'freecompany_rank`.`rankid`=`'.$this->dbprefix.'freecompany_character`.`rankid` AND `'.$this->dbprefix.'freecompany_rank`.`freecompanyid`=`'.$this->dbprefix.'freecompany_character`.`freecompanyid` JOIN (SELECT `rankid`, COUNT(*) AS `total` FROM `'.$this->dbprefix.'freecompany_character` WHERE `'.$this->dbprefix.'freecompany_character`.`freecompanyid`=:id GROUP BY `rankid`) `ranklist` ON `ranklist`.`rankid` = `'.$this->dbprefix.'freecompany_character`.`rankid` WHERE `'.$this->dbprefix.'freecompany_character`.`freecompanyid`=:id ORDER BY `ranklist`.`total` ASC, `ranklist`.`rankid` ASC, `'.$this->dbprefix.'character`.`name` ASC', [':id'=>$id]);
        #History of ranks. Ensuring that we get only the freshest 100 entries sorted from latest to newest
        $data['ranks_history'] = $dbcon->selectAll('SELECT * FROM (SELECT `date`, `weekly`, `monthly`, `members` FROM `'.$this->dbprefix.'freecompany_ranking` WHERE `freecompanyid`=:id ORDER BY `date` DESC LIMIT 100) `lastranks` ORDER BY `date` ASC', [':id'=>$id]);
        #Clean up the data from unnecessary (technical) clutter
        unset($data['grandcompanyid'], $data['estateid'], $data['gcrankid'], $data['gc_rank'], $data['gc_icon'], $data['activeid'], $data['cityid'], $data['left'], $data['top'], $data['cityicon']);
        #In case the entry is old enough (at least 1 day old) and register it for update
        if (empty($data['deleted']) && (time() - strtotime($data['updated'])) >= 86400) {
            (new \Simbiat\Cron)->add('ffentityupdate', ['freecompany', $id], priority: 1, message: 'Updating free company with ID '.$id);
        }
        unset($dbcon);
        return $data;
    }
    
    private function GetLinkshell(string $id): array
    {
        $dbcon = (new \Simbiat\Database\Controller);
        #Get general information
        $data = $dbcon->selectRow('SELECT * FROM `'.$this->dbprefix.'linkshell` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'linkshell`.`serverid`=`'.$this->dbprefix.'server`.`serverid` WHERE `linkshellid`=:id', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get old names
        $data['oldnames'] = $dbcon->selectColumn('SELECT `name` FROM `'.$this->dbprefix.'linkshell_names` WHERE `linkshellid`=:id AND `name`<>:name', [':id'=>$id, ':name'=>$data['name']]);
        #Get members
        $data['members'] = $dbcon->selectAll('SELECT `'.$this->dbprefix.'linkshell_character`.`characterid`, `'.$this->dbprefix.'character`.`name`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'linkshell_rank`.`rank`, `'.$this->dbprefix.'linkshell_rank`.`lsrankid` FROM `'.$this->dbprefix.'linkshell_character` LEFT JOIN `'.$this->dbprefix.'linkshell_rank` ON `'.$this->dbprefix.'linkshell_rank`.`lsrankid`=`'.$this->dbprefix.'linkshell_character`.`rankid` LEFT JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'linkshell_character`.`characterid`=`'.$this->dbprefix.'character`.`characterid` WHERE `'.$this->dbprefix.'linkshell_character`.`linkshellid`=:id ORDER BY `'.$this->dbprefix.'linkshell_character`.`rankid` ASC, `'.$this->dbprefix.'character`.`name` ASC', [':id'=>$id]); 
        #Clean up the data from unnecessary (technical) clutter
        unset($data['serverid']);
        if ($data['crossworld']) {
            unset($data['server']);
        }
        #In case the entry is old enough (at least 1 day old) and register it for update
        if (empty($data['deleted']) && (time() - strtotime($data['updated'])) >= 86400) {
            if ($data['crossworld'] == '0') {
                (new \Simbiat\Cron)->add('ffentityupdate', ['linkshell', $id], priority: 1, message: 'Updating linkshell with ID '.$id);
            } else {
                (new \Simbiat\Cron)->add('ffentityupdate', ['crossworldlinkshell', $id], priority: 1, message: 'Updating crossworldlinkshell with ID '.$id);
            }
        }
        unset($dbcon);
        return $data;     
    }
    
    private function GetPVP(string $id): array
    {
        $dbcon = (new \Simbiat\Database\Controller);
        #Get general information
        $data = $dbcon->selectRow('SELECT * FROM `'.$this->dbprefix.'pvpteam` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'pvpteam`.`datacenterid`=`'.$this->dbprefix.'server`.`serverid` WHERE `pvpteamid`=:id', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get old names
        $data['oldnames'] = $dbcon->selectColumn('SELECT `name` FROM `'.$this->dbprefix.'pvpteam_names` WHERE `pvpteamid`=:id AND `name`<>:name', [':id'=>$id, ':name'=>$data['name']]);
        #Get members
        $data['members'] = $dbcon->selectAll('SELECT `'.$this->dbprefix.'pvpteam_character`.`characterid`, `'.$this->dbprefix.'pvpteam_character`.`matches`, `'.$this->dbprefix.'character`.`name`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'pvpteam_rank`.`rank`, `'.$this->dbprefix.'pvpteam_rank`.`pvprankid` FROM `'.$this->dbprefix.'pvpteam_character` LEFT JOIN `'.$this->dbprefix.'pvpteam_rank` ON `'.$this->dbprefix.'pvpteam_rank`.`pvprankid`=`'.$this->dbprefix.'pvpteam_character`.`rankid` LEFT JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'pvpteam_character`.`characterid`=`'.$this->dbprefix.'character`.`characterid` WHERE `'.$this->dbprefix.'pvpteam_character`.`pvpteamid`=:id ORDER BY `'.$this->dbprefix.'pvpteam_character`.`rankid` ASC, `'.$this->dbprefix.'character`.`name` ASC', [':id'=>$id]);
        #Clean up the data from unnecessary (technical) clutter
        unset($data['datacenterid'], $data['serverid'], $data['server']);
        #In case the entry is old enough (at least 1 day old) and register it for update
        if (empty($data['deleted']) && (time() - strtotime($data['updated'])) >= 86400) {
            (new \Simbiat\Cron)->add('ffentityupdate', ['pvpteam', $id], priority: 1, message: 'Updating PvP team with ID '.$id);
        }
        unset($dbcon);
        return $data;   
    }
    
    private function GetAchievement(string $id): array
    {
        $dbcon = (new \Simbiat\Database\Controller);
        #Get general information
        $data = $dbcon->selectRow('SELECT *, (SELECT COUNT(*) FROM `'.$this->dbprefix.'character_achievement` WHERE `'.$this->dbprefix.'character_achievement`.`achievementid` = `'.$this->dbprefix.'achievement`.`achievementid`) as `count` FROM `'.$this->dbprefix.'achievement` WHERE `'.$this->dbprefix.'achievement`.`achievementid` = :id', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get last characters with this achievement
        $data['characters'] = $dbcon->selectAll('SELECT * FROM (SELECT \'character\' AS `type`, `'.$this->dbprefix.'character`.`characterid` AS `id`, `'.$this->dbprefix.'character`.`name`, `'.$this->dbprefix.'character`.`avatar` AS `icon` FROM `'.$this->dbprefix.'character_achievement` LEFT JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'character`.`characterid` = `'.$this->dbprefix.'character_achievement`.`characterid` WHERE `'.$this->dbprefix.'character_achievement`.`achievementid` = :id ORDER BY `'.$this->dbprefix.'character_achievement`.`time` DESC LIMIT '.$this->maxlines.') t ORDER BY `name`', [':id'=>$id]);
        #Register for an update if old enough or category or howto or dbid are empty
        if ((empty($data['category']) || empty($data['subcategory']) || empty($data['howto']) || empty($data['dbid']) || (time() - strtotime($data['updated'])) >= 31536000) && !empty($data['characters'])) {
            (new \Simbiat\Cron)->add('ffentityupdate', ['achievement', $id, array_column($data['characters'], 'id')[0]], priority: 2, message: 'Updating achievement with ID '.$id);
        }
        unset($dbcon);
        return $data;   
    }
    
    #Function to search for entities
    public function Search(string $what = ''): array
    {
        $dbcon = (new \Simbiat\Database\Controller);
        $what = preg_replace('/(^[-+@<>()~*\'\s]*)|([-+@<>()~*\'\s]*$)/mi', '', $what);
        if ($what === '') {
            #Count entities
            $result['counts'] = $dbcon->selectPair('
                        SELECT \'characters\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'character`
                        UNION ALL
                        SELECT \'companies\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'freecompany`
                        UNION ALL
                        SELECT \'linkshells\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'linkshell`
                        UNION ALL
                        SELECT \'pvpteams\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'pvpteam`
                        UNION ALL
                        SELECT \'achievements\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'achievement`
                        ');
            $result['entities'] = $this->GetRandomEntities($this->maxlines);
        } else {
            #Prepare data for binding. Since we may be using data from user/URI we also try to sanitise it through rawurldecode
            $where_pdo = array(':id'=>[(is_int($what) ? intval($what) : $what), (is_int($what) ? 'int' : 'string')], ':name'=>'*'.rawurldecode($what).'*');
            #Count entities
            $result['counts'] = $dbcon->selectPair('
                        SELECT \'characters\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'character` WHERE `characterid` = :id OR MATCH (`name`, `biography`) AGAINST (:name IN BOOLEAN MODE)
                        UNION ALL
                        SELECT \'companies\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'freecompany` WHERE `freecompanyid` = :id OR MATCH (`name`, `tag`, `slogan`, `estate_zone`, `estate_message`) AGAINST (:name IN BOOLEAN MODE)
                        UNION ALL
                        SELECT \'linkshells\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'linkshell` WHERE `linkshellid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                        UNION ALL
                        SELECT \'pvpteams\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'pvpteam` WHERE `pvpteamid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                        UNION ALL
                        SELECT \'achievements\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'achievement` WHERE `achievementid` = :name OR MATCH (`name`, `howto`) AGAINST (:name IN BOOLEAN MODE)
            ', $where_pdo);
            #If there are actual entities matching the criteria - show $maxlines amount of them
            if (array_sum($result['counts']) > 0) {
                #Need to use a secondary SELECT, because IN BOOLEAN MODE does not sort by default and we need `relevance` column for that, but we do not want to send to client
                $result['entities'] = $dbcon->selectAll('
                        SELECT `id`, `type`, `name`, `icon` FROM (
                            SELECT `characterid` AS `id`, \'character\' as `type`, `name`, `avatar` AS `icon`, IF(`characterid` = :id, 99999, MATCH (`name`, `biography`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'character` WHERE `characterid` = :id OR MATCH (`name`, `biography`) AGAINST (:name IN BOOLEAN MODE)
                            UNION ALL
                            SELECT `freecompanyid` AS `id`, \'freecompany\' as `type`, `name`, `crest` AS `icon`, IF(`freecompanyid` = :id, 99999, MATCH (`name`, `tag`, `slogan`, `estate_zone`, `estate_message`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'freecompany` WHERE `freecompanyid` = :id OR MATCH (`name`, `tag`, `slogan`, `estate_zone`, `estate_message`) AGAINST (:name IN BOOLEAN MODE)
                            UNION ALL
                            SELECT `linkshellid` AS `id`, IF(`crossworld`=1, \'crossworld_linkshell\', \'linkshell\') as `type`, `name`, NULL AS `icon`, IF(`linkshellid` = :id, 99999, MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'linkshell` WHERE `linkshellid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                            UNION ALL
                            SELECT `pvpteamid` AS `id`, \'pvpteam\' as `type`, `name`, `crest` AS `icon`, IF(`pvpteamid` = :id, 99999, MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'pvpteam` WHERE `pvpteamid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                            UNION ALL
                            SELECT `achievementid` AS `id`, \'achievement\' as `type`, `name`, `icon`, IF(`achievementid` = :id, 99999, MATCH (`name`, `howto`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'achievement` WHERE `achievementid` = :id OR MATCH (`name`, `howto`) AGAINST (:name IN BOOLEAN MODE)
                            ORDER BY `relevance` DESC, `name` ASC LIMIT '.$this->maxlines.'
                        ) tempdata
                ', $where_pdo);
            }
        }
        unset($dbcon);
        return $result;
    }
    
    #Function to get a list of entities
    public function listEntities(string $type, int $offset = 0, int $limit = 100): array
    {
        #Sanitize type
        if (!in_array($type, ['freecompanies', 'linkshells', 'crossworldlinkshells', 'crossworld_linkshells', 'characters', 'achievements', 'pvpteams'])) {
            return [];
        } else {
            #Update type
            $type = match($type) {
                'freecompanies' => 'freecompany',
                'linkshells', 'crossworldlinkshells', 'crossworld_linkshells' => 'linkshell',
                'characters' => 'character',
                'achievements' => 'achievement',
                'pvpteams' => 'pvpteam',
            };
        }
        #Set avatar value
        $avatar = match($type) {
            'character' => '`avatar`',
            'achievement' => '`icon`',
            'freecompany', 'pvpteam' => '`crest`',
            default => 'NULL',
        };
        #Sanitize numbers
        if ($offset < 0) {
            $offset = 0;
        }
        if ($limit < 1) {
            $limit = 1;
        }
        $dbcon = (new \Simbiat\Database\Controller);
        #Forcing index, because for some reason MySQL is using filesort for this query
        $result['entities'] = $dbcon->selectAll('SELECT `'.$type.'id` AS `id`, \''.$type.'\' as `type`, `name`, '.$avatar.' AS `icon`, `updated` FROM `'.$this->dbprefix.$type.'` FORCE INDEX(`name_order`) ORDER BY `name` ASC LIMIT '.$offset.', '.$limit);
        $result['statistics'] = $dbcon->selectRow('SELECT COUNT(`'.$type.'id`) AS `count`, MAX(`updated`) AS `updated` FROM `'.$this->dbprefix.$type.'`');
        return $result;
    }
    
    public function Statistics(string $type = 'genetics', string $cachepath = '', bool $nocache = false): array
    {
        #Sanitize type
        $type = strtolower($type);
        if (!in_array($type, ['genetics', 'astrology', 'characters', 'freecompanies', 'cities', 'grandcompanies', 'servers', 'achievements', 'timelines', 'other'])) {
            $type = 'genetics';
        }
        #Sanitize cachepath
        if (empty($cachepath)) {
            $cachepath = dirname(__DIR__).'/ffstatitics.json';
        }
        #Check if cache file exists
        if (is_file($cachepath)) {
            #Read the cache
            $json = file_get_contents($cachepath);
            if ($json !== false && $json !== '') {
                $json = json_decode($json, true, 512, JSON_INVALID_UTF8_SUBSTITUTE | JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
                if ($json !== NULL) {
                    if (!is_array($json)) {
                        $json = [];
                    }
                } else {
                    $json = [];
                }
            } else {
                $json = [];
            }
        } else {
            $json = [];
        }    
        #Get Lodestone object for optimization
        $Lodestone = (new \Simbiat\LodestoneModules\Converters);
        #Get ArrayHelpers object for optimization
        $ArrayHelpers = (new \Simbiat\ArrayHelpers);
        #Get connection object for slight optimization
        $dbcon = (new \Simbiat\Database\Controller);
        switch ($type) {
            case 'genetics':
                #Get statistics by clan
                if (!$nocache && !empty($json['characters']['clans'])) {
                    $data['characters']['clans'] = $json['characters']['clans'];
                } else {
                    $data['characters']['clans'] = $ArrayHelpers->splitByKey($dbcon->countUnique($this->dbprefix.'character', 'clanid', '`'.$this->dbprefix.'character`.`deleted` IS NULL', $this->dbprefix.'clan', 'INNER', 'clanid', '`'.$this->dbprefix.'character`.`genderid`, CONCAT(`'.$this->dbprefix.'clan`.`race`, \' of \', `'.$this->dbprefix.'clan`.`clan`, \' clan\')', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']), 'genderid', ['female', 'male'], [0, 1]);
                }   
                #Clan distribution by city
                if (!$nocache && !empty($json['cities']['clans'])) {
                    $data['cities']['clans'] = $json['cities']['clans'];
                } else {
                    $data['cities']['clans'] = $ArrayHelpers->splitByKey($dbcon->SelectAll('SELECT `'.$this->dbprefix.'city`.`city`, CONCAT(`'.$this->dbprefix.'clan`.`race`, \' of \', `'.$this->dbprefix.'clan`.`clan`, \' clan\') AS `value`, COUNT(`'.$this->dbprefix.'character`.`characterid`) AS `count` FROM `'.$this->dbprefix.'character` LEFT JOIN `'.$this->dbprefix.'city` ON `'.$this->dbprefix.'character`.`cityid`=`'.$this->dbprefix.'city`.`cityid` LEFT JOIN `'.$this->dbprefix.'clan` ON `'.$this->dbprefix.'character`.`clanid`=`'.$this->dbprefix.'clan`.`clanid` GROUP BY `city`, `value` ORDER BY `count` DESC'), 'city', [$Lodestone->getCityName(2, $this->language), $Lodestone->getCityName(4, $this->language), $Lodestone->getCityName(5, $this->language)], []); 
                }  
                #Clan distribution by grand company
                if (!$nocache && !empty($json['grand_companies']['clans'])) {
                    $data['grand_companies']['clans'] = $json['grand_companies']['clans'];
                } else {
                    $data['grand_companies']['clans'] = $ArrayHelpers->splitByKey($dbcon->SelectAll('SELECT `'.$this->dbprefix.'grandcompany_rank`.`gc_name`, CONCAT(`'.$this->dbprefix.'clan`.`race`, \' of \', `'.$this->dbprefix.'clan`.`clan`, \' clan\') AS `value`, COUNT(`'.$this->dbprefix.'character`.`characterid`) AS `count` FROM `'.$this->dbprefix.'character` LEFT JOIN `'.$this->dbprefix.'clan` ON `'.$this->dbprefix.'character`.`clanid`=`'.$this->dbprefix.'clan`.`clanid` LEFT JOIN `'.$this->dbprefix.'grandcompany_rank` ON `'.$this->dbprefix.'character`.`gcrankid`=`'.$this->dbprefix.'grandcompany_rank`.`gcrankid` WHERE `'.$this->dbprefix.'character`.`deleted` IS NULL AND `'.$this->dbprefix.'grandcompany_rank`.`gc_name` IS NOT NULL GROUP BY `gc_name`, `value` ORDER BY `count` DESC'), 'gc_name', [], []);
                }
                break;
            case 'astrology':
                #Get statitics by guardian
                if (!$nocache && !empty($json['characters']['guardians'])) {
                    $data['characters']['guardians'] = $json['characters']['guardians'];
                } else {
                    $data['characters']['guardians'] = $dbcon->countUnique($this->dbprefix.'character', 'guardianid', '`'.$this->dbprefix.'character`.`deleted` IS NULL',$this->dbprefix.'guardian', 'INNER', 'guardianid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'guardian`.`guardian`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']);
                    #Add colors to guardians
                    foreach ($data['characters']['guardians'] as $key=>$guardian) {
                        $data['characters']['guardians'][$key]['color'] = $Lodestone->colorGuardians($guardian['value']);
                    }
                    #Split guardians by gender
                    $data['characters']['guardians'] = $ArrayHelpers->splitByKey($data['characters']['guardians'], 'genderid', ['female', 'male'], [0, 1]);
                }
                #Guardian distribution by city
                if (!$nocache && !empty($json['cities']['guardians'])) {
                    $data['cities']['guardians'] = $json['cities']['guardians'];
                } else {
                    $data['cities']['guardians'] = $dbcon->SelectAll('SELECT `'.$this->dbprefix.'city`.`city`, `'.$this->dbprefix.'guardian`.`guardian` AS `value`, COUNT(`'.$this->dbprefix.'character`.`characterid`) AS `count` FROM `'.$this->dbprefix.'character` LEFT JOIN `'.$this->dbprefix.'city` ON `'.$this->dbprefix.'character`.`cityid`=`'.$this->dbprefix.'city`.`cityid` LEFT JOIN `'.$this->dbprefix.'guardian` ON `'.$this->dbprefix.'character`.`guardianid`=`'.$this->dbprefix.'guardian`.`guardianid` GROUP BY `city`, `value` ORDER BY `count` DESC');
                    #Add colors to guardians
                    foreach ($data['cities']['guardians'] as $key=>$guardian) {
                        $data['cities']['guardians'][$key]['color'] = $Lodestone->colorGuardians($guardian['value']);
                    }
                    $data['cities']['guardians'] = $ArrayHelpers->splitByKey($data['cities']['guardians'], 'city', [], []);
                }
                #Guardians distribution by grand company
                if (!$nocache && !empty($json['grand_companies']['guardians'])) {
                    $data['grand_companies']['guardians'] = $json['grand_companies']['guardians'];
                } else {
                    $data['grand_companies']['guardians'] = $dbcon->SelectAll('SELECT `'.$this->dbprefix.'grandcompany_rank`.`gc_name`, `'.$this->dbprefix.'guardian`.`guardian` AS `value`, COUNT(`'.$this->dbprefix.'character`.`characterid`) AS `count` FROM `'.$this->dbprefix.'character` LEFT JOIN `'.$this->dbprefix.'guardian` ON `'.$this->dbprefix.'character`.`guardianid`=`'.$this->dbprefix.'guardian`.`guardianid` LEFT JOIN `'.$this->dbprefix.'grandcompany_rank` ON `'.$this->dbprefix.'character`.`gcrankid`=`'.$this->dbprefix.'grandcompany_rank`.`gcrankid` WHERE `'.$this->dbprefix.'character`.`deleted` IS NULL AND `'.$this->dbprefix.'grandcompany_rank`.`gc_name` IS NOT NULL GROUP BY `gc_name`, `value` ORDER BY `count` DESC');
                    #Add colors to guardians
                    foreach ($data['grand_companies']['guardians'] as $key=>$guardian) {
                        $data['grand_companies']['guardians'][$key]['color'] = $Lodestone->colorGuardians($guardian['value']);
                    }
                    $data['grand_companies']['guardians'] = $ArrayHelpers->splitByKey($data['grand_companies']['guardians'], 'gc_name', [], []);
                }
                break;
            case 'characters':
                #Jobs popularity
                if (!$nocache && !empty($json['characters']['jobs'])) {
                    $data['characters']['jobs'] = $json['characters']['jobs'];
                } else {
                    $data['characters']['jobs'] = $dbcon->selectPair('SELECT `'.$this->dbprefix.'job`.`name` AS `job`, `sum`.`level` FROM (SELECT `jobid`, SUM(`level`) AS `level` FROM `'.$this->dbprefix.'character_jobs` GROUP BY `jobid`) AS `sum` INNER JOIN `'.$this->dbprefix.'job` ON `sum`.`jobid`=`'.$this->dbprefix.'job`.`jobid` ORDER BY `sum`.`level` DESC;');
                }
                #Most name changes
                if (!$nocache && !empty($json['characters']['changes']['name'])) {
                    $data['characters']['changes']['name'] = $json['characters']['changes']['name'];
                } else {
                    $data['characters']['changes']['name'] = $dbcon->countUnique($this->dbprefix.'character_names', 'characterid', '', $this->dbprefix.'character', 'INNER', 'characterid', '`tempresult`.`characterid` AS `id`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'character`.`name`', 'DESC', 20, [], true);
                }
                #Most reincarnation
                if (!$nocache && !empty($json['characters']['changes']['clan'])) {
                    $data['characters']['changes']['clan'] = $json['characters']['changes']['clan'];
                } else {
                    $data['characters']['changes']['clan'] = $dbcon->countUnique($this->dbprefix.'character_clans', 'characterid', '', $this->dbprefix.'character', 'INNER', 'characterid', '`tempresult`.`characterid` AS `id`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'character`.`name`', 'DESC', 20, [], true);
                }
                #Most servers
                if (!$nocache && !empty($json['characters']['changes']['server'])) {
                    $data['characters']['changes']['server'] = $json['characters']['changes']['server'];
                } else {
                    $data['characters']['changes']['server'] = $dbcon->countUnique($this->dbprefix.'character_servers', 'characterid', '', $this->dbprefix.'character', 'INNER', 'characterid', '`tempresult`.`characterid` AS `id`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'character`.`name`', 'DESC', 20, [], true);
                }
                #Most companies
                if (!$nocache && !empty($json['characters']['xgroups']['Free Companies'])) {
                    $data['characters']['xgroups']['Free Companies'] = $json['characters']['xgroups']['Free Companies'];
                } else {
                    $data['characters']['xgroups']['Free Companies'] = $dbcon->countUnique($this->dbprefix.'freecompany_x_character', 'characterid', '', $this->dbprefix.'character', 'INNER', 'characterid', '`tempresult`.`characterid` AS `id`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'character`.`name`', 'DESC', 20, [], true);
                }
                #Most PvP teams
                if (!$nocache && !empty($json['characters']['xgroups']['PvP Teams'])) {
                    $data['characters']['xgroups']['PvP Teams'] = $json['characters']['xgroups']['PvP Teams'];
                } else {
                    $data['characters']['xgroups']['PvP Teams'] = $dbcon->countUnique($this->dbprefix.'pvpteam_x_character', 'characterid', '', $this->dbprefix.'character', 'INNER', 'characterid', '`tempresult`.`characterid` AS `id`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'character`.`name`', 'DESC', 20, [], true);
                }
                #Most x-linkshells
                if (!$nocache && !empty($json['characters']['xgroups']['Linkshells'])) {
                    $data['characters']['xgroups']['Linkshells'] = $json['characters']['xgroups']['Linkshells'];
                } else {
                    $data['characters']['xgroups']['Linkshells'] = $dbcon->countUnique($this->dbprefix.'linkshell_x_character', 'characterid', '', $this->dbprefix.'character', 'INNER', 'characterid', '`tempresult`.`characterid` AS `id`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'character`.`name`', 'DESC', 20, [], true);
                }
                #Most linkshells
                if (!$nocache && !empty($json['characters']['groups']['linkshell'])) {
                    $data['characters']['groups']['linkshell'] = $json['characters']['groups']['linkshell'];
                } else {
                    $data['characters']['groups']['linkshell'] = $dbcon->countUnique($this->dbprefix.'linkshell_character', 'characterid', '', $this->dbprefix.'character', 'INNER', 'characterid', '`tempresult`.`characterid` AS `id`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'character`.`name`', 'DESC', 20, [], true);
                }
                #Groups affiliation
                if (!$nocache && !empty($json['characters']['groups']['participation'])) {
                    $data['characters']['groups']['participation'] = $json['characters']['groups']['participation'];
                } else {
                    $data['characters']['groups']['participation'] = $dbcon->SelectAll('
                        SELECT `affiliation` AS `value`, COUNT(`affiliation`) AS `count`FROM (
                            SELECT `'.$this->dbprefix.'character`.`characterid`,
                                (CASE
                                    WHEN (`'.$this->dbprefix.'freecompany_character`.`freecompanyid` IS NOT NULL AND `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` IS NULL AND `'.$this->dbprefix.'linkshell_character`.`linkshellid` IS NULL) THEN \'Free Company only\'
                                    WHEN (`'.$this->dbprefix.'freecompany_character`.`freecompanyid` IS NULL AND `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` IS NOT NULL AND `'.$this->dbprefix.'linkshell_character`.`linkshellid` IS NULL) THEN \'PvP Team only\'
                                    WHEN (`'.$this->dbprefix.'freecompany_character`.`freecompanyid` IS NULL AND `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` IS NULL AND `'.$this->dbprefix.'linkshell_character`.`linkshellid` IS NOT NULL) THEN \'Linkshell only\'
                                    WHEN (`'.$this->dbprefix.'freecompany_character`.`freecompanyid` IS NOT NULL AND `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` IS NOT NULL AND `'.$this->dbprefix.'linkshell_character`.`linkshellid` IS NULL) THEN \'Free Company and PvP Team\'
                                    WHEN (`'.$this->dbprefix.'freecompany_character`.`freecompanyid` IS NOT NULL AND `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` IS NULL AND `'.$this->dbprefix.'linkshell_character`.`linkshellid` IS NOT NULL) THEN \'Free Company and Linkshell\'
                                    WHEN (`'.$this->dbprefix.'freecompany_character`.`freecompanyid` IS NULL AND `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` IS NOT NULL AND `'.$this->dbprefix.'linkshell_character`.`linkshellid` IS NOT NULL) THEN \'PvP Team and Linkshell\'
                                    WHEN (`'.$this->dbprefix.'freecompany_character`.`freecompanyid` IS NOT NULL AND `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` IS NOT NULL AND `'.$this->dbprefix.'linkshell_character`.`linkshellid` IS NOT NULL) THEN \'Free Company, PvP Team and Linkshell\'
                                    ELSE \'No groups\'
                                END) AS `affiliation`
                            FROM `'.$this->dbprefix.'character` LEFT JOIN `'.$this->dbprefix.'freecompany_character` ON `'.$this->dbprefix.'freecompany_character`.`characterid` = `'.$this->dbprefix.'character`.`characterid` LEFT JOIN `'.$this->dbprefix.'pvpteam_character` ON `'.$this->dbprefix.'pvpteam_character`.`characterid` = `'.$this->dbprefix.'character`.`characterid` LEFT JOIN `'.$this->dbprefix.'linkshell_character` ON `'.$this->dbprefix.'linkshell_character`.`characterid` = `'.$this->dbprefix.'character`.`characterid` WHERE `'.$this->dbprefix.'character`.`deleted` IS NULL GROUP BY `'.$this->dbprefix.'character`.`characterid`) `tempresult`
                        GROUP BY `affiliation` ORDER BY `count` DESC;
                    ');
                    #Move count of loners to separate key
                    foreach ($data['characters']['groups']['participation'] as $key=>$row) {
                        if ($row['value'] === 'No groups') {
                            $data['characters']['no_groups'] = $row['count'];
                            unset($data['characters']['groups']['participation'][$key]);
                            break;
                        }
                    }
                }
                #Get characters with most PvP matches. Using regular SQL since we do not count uniqie values, but rather use the regular column values
                if (!$nocache && !empty($json['characters']['most_pvp'])) {
                    $data['characters']['most_pvp'] = $json['characters']['most_pvp'];
                } else {
                    $data['characters']['most_pvp'] = $dbcon->SelectAll('SELECT `'.$this->dbprefix.'character`.`characterid` AS `id`, `'.$this->dbprefix.'character`.`avatar`, `'.$this->dbprefix.'character`.`name` AS `value`, `matches` AS `count` FROM `'.$this->dbprefix.'pvpteam_character` INNER JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'pvpteam_character`.`characterid`=`'.$this->dbprefix.'character`.`characterid` ORDER BY `'.$this->dbprefix.'pvpteam_character`.`matches` DESC LIMIT 20');
                }
                break;
            case 'freecompanies':
                #Get most popular estate locations
                if (!$nocache && !empty($json['freecompany']['estate'])) {
                    $data['freecompany']['estate'] = $json['freecompany']['estate'];
                } else {
                    $data['freecompany']['estate'] = $ArrayHelpers->topAndBottom($dbcon->countUnique($this->dbprefix.'freecompany', 'estateid', '`'.$this->dbprefix.'freecompany`.`deleted` IS NULL AND `'.$this->dbprefix.'freecompany`.`estateid` IS NOT NULL', $this->dbprefix.'estate', 'INNER', 'estateid', '`'.$this->dbprefix.'estate`.`area`, `'.$this->dbprefix.'estate`.`plot`, CONCAT(`'.$this->dbprefix.'estate`.`area`, \', plot \', `'.$this->dbprefix.'estate`.`plot`)', 'DESC', 0), 20);
                }
                #Get statistics by activite time
                if (!$nocache && !empty($json['freecompany']['active'])) {
                    $data['freecompany']['active'] = $json['freecompany']['active'];
                } else {
                    $data['freecompany']['active'] = $dbcon->sumUnique($this->dbprefix.'freecompany', 'activeid', [1, 2, 3], ['Always', 'Weekdays', 'Weekends'], '`'.$this->dbprefix.'freecompany`.`deleted` IS NULL', $this->dbprefix.'timeactive', 'INNER', 'activeid', 'IF(`'.$this->dbprefix.'freecompany`.`recruitment`=1, \'Recruting\', \'Not recruting\') AS `recruiting`', 'DESC', 0);
                }
                #Get statistics by activities
                if (!$nocache && !empty($json['freecompany']['activities'])) {
                    $data['freecompany']['activities'] = $json['freecompany']['activities'];
                } else {
                    $data['freecompany']['activities'] = $dbcon->SelectRow('SELECT SUM(`Tank`)/COUNT(`freecompanyid`)*100 AS `Tank`, SUM(`Healer`)/COUNT(`freecompanyid`)*100 AS `Healer`, SUM(`DPS`)/COUNT(`freecompanyid`)*100 AS `DPS`, SUM(`Crafter`)/COUNT(`freecompanyid`)*100 AS `Crafter`, SUM(`Gatherer`)/COUNT(`freecompanyid`)*100 AS `Gatherer`, SUM(`Role-playing`)/COUNT(`freecompanyid`)*100 AS `Role-playing`, SUM(`Leveling`)/COUNT(`freecompanyid`)*100 AS `Leveling`, SUM(`Casual`)/COUNT(`freecompanyid`)*100 AS `Casual`, SUM(`Hardcore`)/COUNT(`freecompanyid`)*100 AS `Hardcore`, SUM(`Dungeons`)/COUNT(`freecompanyid`)*100 AS `Dungeons`, SUM(`Guildhests`)/COUNT(`freecompanyid`)*100 AS `Guildhests`, SUM(`Trials`)/COUNT(`freecompanyid`)*100 AS `Trials`, SUM(`Raids`)/COUNT(`freecompanyid`)*100 AS `Raids`, SUM(`PvP`)/COUNT(`freecompanyid`)*100 AS `PvP` FROM `'.$this->dbprefix.'freecompany` WHERE `deleted` IS NULL');
                }
                #Get statistics by monthly ranks
                if (!$nocache && !empty($json['freecompany']['ranking']['monthly'])) {
                    $data['freecompany']['ranking']['monthly'] = $json['freecompany']['ranking']['monthly'];
                } else {
                    $data['freecompany']['ranking']['monthly'] = $dbcon->SelectAll('SELECT `tempresult`.*, `'.$this->dbprefix.'freecompany`.`name` FROM (SELECT `main`.`freecompanyid`, 1/(`members`*`monthly`)*100 AS `ratio` FROM `'.$this->dbprefix.'freecompany_ranking` `main` WHERE `main`.`date` = (SELECT MAX(`sub`.`date`) FROM `'.$this->dbprefix.'freecompany_ranking` `sub`)) `tempresult` INNER JOIN `'.$this->dbprefix.'freecompany` ON `'.$this->dbprefix.'freecompany`.`freecompanyid` = `tempresult`.`freecompanyid` ORDER BY `ratio` DESC');
                    if (count($data['freecompany']['ranking']['monthly']) > 1) {
                        $data['freecompany']['ranking']['monthly'] = $ArrayHelpers->topAndBottom($data['freecompany']['ranking']['monthly'], 20);
                    } else {
                        $data['freecompany']['ranking']['monthly'] = [];
                    }
                }
                #Get statistics by weekly ranks
                if (!$nocache && !empty($json['freecompany']['ranking']['weekly'])) {
                    $data['freecompany']['ranking']['weekly'] = $json['freecompany']['ranking']['weekly'];
                } else {
                    $data['freecompany']['ranking']['weekly'] = $dbcon->SelectAll('SELECT `tempresult`.*, `'.$this->dbprefix.'freecompany`.`name` FROM (SELECT `main`.`freecompanyid`, 1/(`members`*`weekly`)*100 AS `ratio` FROM `'.$this->dbprefix.'freecompany_ranking` `main` WHERE `main`.`date` = (SELECT MAX(`sub`.`date`) FROM `'.$this->dbprefix.'freecompany_ranking` `sub`)) `tempresult` INNER JOIN `'.$this->dbprefix.'freecompany` ON `'.$this->dbprefix.'freecompany`.`freecompanyid` = `tempresult`.`freecompanyid` ORDER BY `ratio` DESC');
                    if (count($data['freecompany']['ranking']['weekly']) > 1) {
                        $data['freecompany']['ranking']['weekly'] = $ArrayHelpers->topAndBottom($data['freecompany']['ranking']['weekly'], 20);
                    } else {
                        $data['freecompany']['ranking']['weekly'] = [];
                    }
                }
                #Get most popular crests
                if (!$nocache && !empty($json['freecompany']['crests'])) {
                    $data['freecompany']['crests'] = $json['freecompany']['crests'];
                } else {
                    $data['freecompany']['crests'] = $dbcon->countUnique($this->dbprefix.'freecompany', 'crest', '`'.$this->dbprefix.'freecompany`.`deleted` IS NULL AND `'.$this->dbprefix.'freecompany`.`crest` IS NOT NULL', '', 'INNER', '', '', 'DESC', 20);
                }
                break;
            case 'cities':
                #Get statistics by city
                if (!$nocache && !empty($json['cities']['gender'])) {
                    $data['cities']['gender'] = $json['cities']['gender'];
                } else {
                    $data['cities']['gender'] = $dbcon->countUnique($this->dbprefix.'character', 'cityid', '`'.$this->dbprefix.'character`.`deleted` IS NULL',$this->dbprefix.'city', 'INNER', 'cityid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'city`.`city`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']);
                    #Add colors to cities
                    foreach ($data['cities']['gender'] as $key=>$city) {
                        $data['cities']['gender'][$key]['color'] = $Lodestone->colorCities($city['value']);
                    }
                    #Split cities by gender
                    $data['cities']['gender'] = $ArrayHelpers->splitByKey($data['cities']['gender'], 'genderid', ['female', 'male'], [0, 1]);
                }
                #City by free company
                if (!$nocache && !empty($json['cities']['free_company'])) {
                    $data['cities']['free_company'] = $json['cities']['free_company'];
                } else {
                    $data['cities']['free_company'] = $dbcon->countUnique($this->dbprefix.'freecompany', 'estateid', '`'.$this->dbprefix.'freecompany`.`estateid` IS NOT NULL AND `'.$this->dbprefix.'freecompany`.`deleted` IS NULL', $this->dbprefix.'estate', 'INNER', 'estateid', '`'.$this->dbprefix.'estate`.`area`');
                    #Add colors to cities
                    foreach ($data['cities']['free_company'] as $key=>$city) {
                        $data['cities']['free_company'][$key]['color'] = $Lodestone->colorCities($city['value']);
                    }
                }
                #Grand companies distribution (characters)
                if (!$nocache && !empty($json['cities']['gc_characters'])) {
                    $data['cities']['gc_characters'] = $json['cities']['gc_characters'];
                } else {
                    $data['cities']['gc_characters'] = $dbcon->SelectAll('SELECT `'.$this->dbprefix.'city`.`city`, `'.$this->dbprefix.'grandcompany_rank`.`gc_name` AS `value`, COUNT(`'.$this->dbprefix.'character`.`characterid`) AS `count` FROM `'.$this->dbprefix.'character` LEFT JOIN `'.$this->dbprefix.'city` ON `'.$this->dbprefix.'character`.`cityid`=`'.$this->dbprefix.'city`.`cityid` LEFT JOIN `'.$this->dbprefix.'grandcompany_rank` ON `'.$this->dbprefix.'character`.`gcrankid`=`'.$this->dbprefix.'grandcompany_rank`.`gcrankid` WHERE `'.$this->dbprefix.'character`.`deleted` IS NULL AND `'.$this->dbprefix.'grandcompany_rank`.`gc_name` IS NOT NULL GROUP BY `city`, `value` ORDER BY `count` DESC');
                    #Add colors to companies
                    foreach ($data['cities']['gc_characters'] as $key=>$company) {
                        $data['cities']['gc_characters'][$key]['color'] = $Lodestone->colorGC($company['value']);
                    }
                    $data['cities']['gc_characters'] = $ArrayHelpers->splitByKey($data['cities']['gc_characters'], 'city', [], []);
                }
                #Grand companies distribution (free companies)
                if (!$nocache && !empty($json['cities']['gc_fc'])) {
                    $data['cities']['gc_fc'] = $json['cities']['gc_fc'];
                } else {
                    $data['cities']['gc_fc'] = $dbcon->SelectAll('SELECT `'.$this->dbprefix.'city`.`city`, `'.$this->dbprefix.'grandcompany_rank`.`gc_name` AS `value`, COUNT(`'.$this->dbprefix.'freecompany`.`freecompanyid`) AS `count` FROM `'.$this->dbprefix.'freecompany` LEFT JOIN `'.$this->dbprefix.'estate` ON `'.$this->dbprefix.'freecompany`.`estateid`=`'.$this->dbprefix.'estate`.`estateid` LEFT JOIN `'.$this->dbprefix.'city` ON `'.$this->dbprefix.'estate`.`cityid`=`'.$this->dbprefix.'city`.`cityid` LEFT JOIN `'.$this->dbprefix.'grandcompany_rank` ON `'.$this->dbprefix.'freecompany`.`grandcompanyid`=`'.$this->dbprefix.'grandcompany_rank`.`gcrankid` WHERE `'.$this->dbprefix.'freecompany`.`deleted` IS NULL AND `'.$this->dbprefix.'freecompany`.`estateid` IS NOT NULL GROUP BY `city`, `value` ORDER BY `count` DESC');
                    #Add colors to companies
                    foreach ($data['cities']['gc_fc'] as $key=>$company) {
                        $data['cities']['gc_fc'][$key]['color'] = $Lodestone->colorGC($company['value']);
                    }
                    $data['cities']['gc_fc'] = $ArrayHelpers->splitByKey($data['cities']['gc_fc'], 'city', [], []);
                }
                break;
            case 'grandcompanies':
                #Get statistics for grand companies
                if (!$nocache && !empty($json['grand_companies']['population'])) {
                    $data['grand_companies']['population'] = $json['grand_companies']['population'];
                } else {
                    $data['grand_companies']['population'] = $dbcon->countUnique($this->dbprefix.'character', 'gcrankid', '`'.$this->dbprefix.'character`.`deleted` IS NULL AND `'.$this->dbprefix.'character`.`gcrankid` IS NOT NULL', $this->dbprefix.'grandcompany_rank', 'INNER', 'gcrankid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'grandcompany_rank`.`gc_name`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']);
                    #Add colors to companies
                    foreach ($data['grand_companies']['population'] as $key=>$company) {
                        $data['grand_companies']['population'][$key]['color'] = $Lodestone->colorGC($company['value']);
                    }
                    #Split companies by gender
                    $data['grand_companies']['population'] = $ArrayHelpers->splitByKey($data['grand_companies']['population'], 'genderid', ['female', 'male'], [0, 1]);
                    $data['grand_companies']['population']['free_company'] = $dbcon->countUnique($this->dbprefix.'freecompany', 'grandcompanyid', '`'.$this->dbprefix.'freecompany`.`deleted` IS NULL', $this->dbprefix.'grandcompany_rank', 'INNER', 'gcrankid', '`'.$this->dbprefix.'grandcompany_rank`.`gc_name`');
                    #Add colors to cities
                    foreach ($data['grand_companies']['population']['free_company'] as $key=>$company) {
                        $data['grand_companies']['population']['free_company'][$key]['color'] = $Lodestone->colorGC($company['value']);
                    }
                }
                #Grand companies ranks
                if (!$nocache && !empty($json['grand_companies']['ranks'])) {
                    $data['grand_companies']['ranks'] = $json['grand_companies']['ranks'];
                } else {
                    $data['grand_companies']['ranks'] = $ArrayHelpers->splitByKey($dbcon->countUnique($this->dbprefix.'character', 'gcrankid', '`'.$this->dbprefix.'character`.`deleted` IS NULL', $this->dbprefix.'grandcompany_rank', 'INNER', 'gcrankid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'grandcompany_rank`.`gc_name`, `'.$this->dbprefix.'grandcompany_rank`.`gc_rank`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`', '`'.$this->dbprefix.'grandcompany_rank`.`gc_name`']), 'gc_name', [], []);
                    #Split by gender
                    foreach ($data['grand_companies']['ranks'] as $key=>$company) {
                        $data['grand_companies']['ranks'][$key] = $ArrayHelpers->splitByKey($company, 'genderid', ['female', 'male'], [0, 1]);
                    }
                }
                break;
            case 'servers':
                #Characters
                if (!$nocache && !empty($json['servers']['female population']) && !empty($json['servers']['male population'])) {
                    $data['servers']['female population'] = $json['servers']['female population'];
                    $data['servers']['male population'] = $json['servers']['male population'];
                } else {
                    $data['servers']['characters'] = $ArrayHelpers->splitByKey($dbcon->countUnique($this->dbprefix.'character', 'serverid', '`'.$this->dbprefix.'character`.`deleted` IS NULL', $this->dbprefix.'server', 'INNER', 'serverid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'server`.`server`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']), 'genderid', ['female', 'male'], [0, 1]);
                    $data['servers']['female population'] = $ArrayHelpers->topAndBottom($data['servers']['characters']['female'], 20);
                    $data['servers']['male population'] = $ArrayHelpers->topAndBottom($data['servers']['characters']['male'], 20);
                    unset($data['servers']['characters']);
                }
                #Free companies
                if (!$nocache && !empty($json['servers']['Free Companies'])) {
                    $data['servers']['Free Companies'] = $json['servers']['Free Companies'];
                } else {
                    $data['servers']['Free Companies'] = $ArrayHelpers->topAndBottom($dbcon->countUnique($this->dbprefix.'freecompany', 'serverid', '`'.$this->dbprefix.'freecompany`.`deleted` IS NULL', $this->dbprefix.'server', 'INNER', 'serverid', '`'.$this->dbprefix.'server`.`server`', 'DESC'), 20);
                }
                #Linkshells
                if (!$nocache && !empty($json['servers']['Linkshells'])) {
                    $data['servers']['Linkshells'] = $json['servers']['Linkshells'];
                } else {
                    $data['servers']['Linkshells'] = $ArrayHelpers->topAndBottom($dbcon->countUnique($this->dbprefix.'linkshell', 'serverid', '`'.$this->dbprefix.'linkshell`.`crossworld` = 0 AND `'.$this->dbprefix.'linkshell`.`deleted` IS NULL', $this->dbprefix.'server', 'INNER', 'serverid', '`'.$this->dbprefix.'server`.`server`', 'DESC'), 20);
                }
                #Crossworld linkshells
                if (!$nocache && !empty($json['servers']['crossworldlinkshell'])) {
                    $data['servers']['crossworldlinkshell'] = $json['servers']['crossworldlinkshell'];
                } else {
                    $data['servers']['crossworldlinkshell'] = $dbcon->countUnique($this->dbprefix.'linkshell', 'serverid', '`'.$this->dbprefix.'linkshell`.`crossworld` = 1 AND `'.$this->dbprefix.'linkshell`.`deleted` IS NULL', $this->dbprefix.'server', 'INNER', 'serverid', '`'.$this->dbprefix.'server`.`datacenter`', 'DESC');
                }
                #PvP teams
                if (!$nocache && !empty($json['servers']['pvpteam'])) {
                    $data['servers']['pvpteam'] = $json['servers']['pvpteam'];
                } else {
                    $data['servers']['pvpteam'] = $dbcon->countUnique($this->dbprefix.'pvpteam', 'datacenterid', '`'.$this->dbprefix.'pvpteam`.`deleted` IS NULL', $this->dbprefix.'server', 'INNER', 'serverid', '`'.$this->dbprefix.'server`.`datacenter`', 'DESC');
                }
                break;
            case 'achievements':
                #Get achievements statistics
                if (!$nocache && !empty($json['other']['achievements'])) {
                    $data['other']['achievements'] = $json['other']['achievements'];
                } else {
                    $data['other']['achievements'] = $dbcon->SelectAll('SELECT `'.$this->dbprefix.'achievement`.`category`, `'.$this->dbprefix.'achievement`.`achievementid` AS `id`, `'.$this->dbprefix.'achievement`.`icon`, `'.$this->dbprefix.'achievement`.`name` AS `value`, `count` FROM (SELECT `'.$this->dbprefix.'character_achievement`.`achievementid`, count(`'.$this->dbprefix.'character_achievement`.`achievementid`) AS `count` from `'.$this->dbprefix.'character_achievement` GROUP BY `'.$this->dbprefix.'character_achievement`.`achievementid` ORDER BY `count` ASC) `tempresult` INNER JOIN `'.$this->dbprefix.'achievement` ON `tempresult`.`achievementid`=`'.$this->dbprefix.'achievement`.`achievementid` WHERE `'.$this->dbprefix.'achievement`.`category` IS NOT NULL ORDER BY `count` ASC');
                    #Split achievements by categories
                    $data['other']['achievements'] = $ArrayHelpers->splitByKey($data['other']['achievements'], 'category', [], []);
                    #Get only top 20 for each category
                    foreach ($data['other']['achievements'] as $key=>$category) {
                        $data['other']['achievements'][$key] = array_slice($category, 0, 20);
                    }
                }
                break;
            case 'timelines':
                #Get namedays timeline. Using custom SQL, since need special order by `namedayid`, instead of by `count`
                if (!$nocache && !empty($json['timelines']['nameday'])) {
                    $data['timelines']['nameday'] = $json['timelines']['nameday'];
                } else {
                    $data['timelines']['nameday'] = $dbcon->SelectAll('SELECT `'.$this->dbprefix.'nameday`.`nameday` AS `value`, COUNT(`'.$this->dbprefix.'character`.`namedayid`) AS `count` FROM `'.$this->dbprefix.'character` INNER JOIN `'.$this->dbprefix.'nameday` ON `'.$this->dbprefix.'character`.`namedayid`=`'.$this->dbprefix.'nameday`.`namedayid` GROUP BY `value` ORDER BY `'.$this->dbprefix.'nameday`.`namedayid` ASC');
                }
                #Timeline of groups formations
                if (!$nocache && !empty($json['timelines']['formed'])) {
                    $data['timelines']['formed'] = $json['timelines']['formed'];
                } else {
                    $data['timelines']['formed'] = $dbcon->SelectAll(
                        'SELECT `formed` AS `value`, SUM(`freecompanies`) AS `freecompanies`, SUM(`linkshells`) AS `linkshells`, SUM(`pvpteams`) AS `pvpteams` FROM (
                            SELECT `formed`, COUNT(`formed`) AS `freecompanies`, 0 AS `linkshells`, 0 AS `pvpteams` FROM `'.$this->dbprefix.'freecompany` WHERE `formed` IS NOT NULL GROUP BY `formed`
                            UNION ALL
                            SELECT `formed`, 0 AS `freecompanies`, COUNT(`formed`) AS `linkshells`, 0 AS `pvpteams` FROM `'.$this->dbprefix.'linkshell` WHERE `formed` IS NOT NULL GROUP BY `formed`
                            UNION ALL
                            SELECT `formed`, 0 AS `freecompanies`, 0 AS `linkshells`, COUNT(`formed`) AS `pvpteams` FROM `'.$this->dbprefix.'pvpteam` WHERE `formed` IS NOT NULL GROUP BY `formed`
                        ) `tempresults`
                        GROUP BY `formed` ORDER BY `formed` ASC'
                    );
                }
                #Timeline of entities registration
                if (!$nocache && !empty($json['timelines']['registered'])) {
                    $data['timelines']['registered'] = $json['timelines']['registered'];
                } else {
                    $data['timelines']['registered'] = $dbcon->SelectAll(
                        'SELECT `registered` AS `value`, SUM(`characters`) AS `characters`, SUM(`freecompanies`) AS `freecompanies`, SUM(`linkshells`) AS `linkshells`, SUM(`pvpteams`) AS `pvpteams` FROM (
                            SELECT `registered`, COUNT(`registered`) AS `characters`, 0 AS `freecompanies`, 0 AS `linkshells`, 0 AS `pvpteams` FROM `'.$this->dbprefix.'character` WHERE `registered` IS NOT NULL GROUP BY `registered`
                            UNION ALL
                            SELECT `registered`, 0 AS `characters`, COUNT(`registered`) AS `freecompanies`, 0 AS `linkshells`, 0 AS `pvpteams` FROM `'.$this->dbprefix.'freecompany` WHERE `registered` IS NOT NULL GROUP BY `registered`
                            UNION ALL
                            SELECT `registered`, 0 AS `characters`, 0 AS `freecompanies`, COUNT(`registered`) AS `linkshells`, 0 AS `pvpteams` FROM `'.$this->dbprefix.'linkshell` WHERE `registered` IS NOT NULL GROUP BY `registered`
                            UNION ALL
                            SELECT `registered`, 0 AS `characters`, 0 AS `freecompanies`, 0 AS `linkshells`, COUNT(`registered`) AS `pvpteams` FROM `'.$this->dbprefix.'pvpteam` WHERE `registered` IS NOT NULL GROUP BY `registered`
                        ) `tempresults`
                        GROUP BY `registered` ORDER BY `registered` ASC'
                    );
                }
                #Timeline of entities deletion
                if (!$nocache && !empty($json['timelines']['deleted'])) {
                    $data['timelines']['deleted'] = $json['timelines']['deleted'];
                } else {
                    $data['timelines']['deleted'] = $dbcon->SelectAll(
                        'SELECT `deleted` AS `value`, SUM(`characters`) AS `characters`, SUM(`freecompanies`) AS `freecompanies`, SUM(`linkshells`) AS `linkshells`, SUM(`pvpteams`) AS `pvpteams` FROM (
                            SELECT `deleted`, COUNT(`deleted`) AS `characters`, 0 AS `freecompanies`, 0 AS `linkshells`, 0 AS `pvpteams` FROM `'.$this->dbprefix.'character` WHERE `deleted` IS NOT NULL GROUP BY `deleted`
                            UNION ALL
                            SELECT `deleted`, 0 AS `characters`, COUNT(`deleted`) AS `freecompanies`, 0 AS `linkshells`, 0 AS `pvpteams` FROM `'.$this->dbprefix.'freecompany` WHERE `deleted` IS NOT NULL GROUP BY `deleted`
                            UNION ALL
                            SELECT `deleted`, 0 AS `characters`, 0 AS `freecompanies`, COUNT(`deleted`) AS `linkshells`, 0 AS `pvpteams` FROM `'.$this->dbprefix.'linkshell` WHERE `deleted` IS NOT NULL GROUP BY `deleted`
                            UNION ALL
                            SELECT `deleted`, 0 AS `characters`, 0 AS `freecompanies`, 0 AS `linkshells`, COUNT(`deleted`) AS `pvpteams` FROM `'.$this->dbprefix.'pvpteam` WHERE `deleted` IS NOT NULL GROUP BY `deleted`
                        ) `tempresults`
                        GROUP BY `deleted` ORDER BY `deleted` ASC'
                    );
                }
                break;
            case 'other':
                #Communities
                if (!$nocache && !empty($json['other']['communities'])) {
                    $data['other']['communities'] = $json['other']['communities'];
                } else {
                    $data['other']['communities'] = $ArrayHelpers->splitByKey($dbcon->SelectAll('
                        SELECT `type`, IF(`has_community`=0, \'No community\', \'Community\') AS `value`, count(`has_community`) AS `count` FROM (
                            SELECT \'Free Company\' AS `type`, IF(`communityid` IS NULL, 0, 1) AS `has_community` FROM `'.$this->dbprefix.'freecompany` WHERE `deleted` IS NULL
                            UNION ALL
                            SELECT \'PvP Team\' AS `type`, IF(`communityid` IS NULL, 0, 1) AS `has_community` FROM `'.$this->dbprefix.'pvpteam` WHERE `deleted` IS NULL
                            UNION ALL
                            SELECT IF(`crossworld`=1, \'Crossworld Linkshell\', \'Linkshell\') AS `type`, IF(`communityid` IS NULL, 0, 1) AS `has_community` FROM `'.$this->dbprefix.'linkshell` WHERE `deleted` IS NULL
                        ) `tempresult`
                        GROUP BY `type`, `value` ORDER BY `count` DESC
                    '), 'type', [], []);
                    #Sanitize results
                    foreach ($data['other']['communities'] as $key=>$row) {
                        if (!empty($row[0])) {
                            $data['other']['communities'][$key][$row[0]['value']] = $row[0]['count'];
                        }
                        if (!empty($row[1])) {
                            $data['other']['communities'][$key][$row[1]['value']] = $row[1]['count'];
                        }
                        if (empty($data['other']['communities'][$key]['Community'])) {
                            $data['other']['communities'][$key]['Community'] = '0';
                        }
                        if (empty($data['other']['communities'][$key]['No community'])) {
                            $data['other']['communities'][$key]['No community'] = '0';
                        }
                        unset($data['other']['communities'][$key][0], $data['other']['communities'][$key][1]);
                    }
                }
                #Deleted entities statistics
                if (!$nocache && !empty($json['other']['entities'])) {
                    $data['other']['entities'] = $json['other']['entities'];
                } else {
                    $data['other']['entities'] = $dbcon->SelectAll('
                        SELECT CONCAT(IF(`deleted`=0, \'Active\', \'Deleted\'), \' \', `type`) AS `value`, count(`deleted`) AS `count` FROM (
                            SELECT \'Character\' AS `type`, IF(`deleted` IS NULL, 0, 1) AS `deleted` FROM `'.$this->dbprefix.'character`
                            UNION ALL
                            SELECT \'Free Company\' AS `type`, IF(`deleted` IS NULL, 0, 1) AS `deleted` FROM `'.$this->dbprefix.'freecompany`
                            UNION ALL
                            SELECT \'PvP Team\' AS `type`, IF(`deleted` IS NULL, 0, 1) AS `deleted` FROM `'.$this->dbprefix.'pvpteam`
                            UNION ALL
                            SELECT IF(`crossworld`=1, \'Crossworld Linkshell\', \'Linkshell\') AS `type`, IF(`deleted` IS NULL, 0, 1) AS `deleted` FROM `'.$this->dbprefix.'linkshell`
                        ) `tempresult`
                        GROUP BY `type`, `value` ORDER BY `count` DESC
                    ');
                }
                if (!$nocache && !empty($json['pvpteam']['crests'])) {
                    $data['pvpteam']['crests'] = $json['pvpteam']['crests'];
                } else {
                    $data['pvpteam']['crests'] = $dbcon->countUnique($this->dbprefix.'pvpteam', 'crest', '`'.$this->dbprefix.'pvpteam`.`deleted` IS NULL AND `'.$this->dbprefix.'pvpteam`.`crest` IS NOT NULL', '', 'INNER', '', '', 'DESC', 20);
                }
                break;
        }
        unset($dbcon, $ArrayHelpers, $Lodestone);
        #Attempt to write to cache
        file_put_contents($cachepath, json_encode(array_merge($json, $data), JSON_INVALID_UTF8_SUBSTITUTE | JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT));
        return $data;
    }
    
    public function GetRandomEntities(int $number): array
    {
        return (new \Simbiat\Database\Controller)->selectAll('
                (SELECT `characterid` AS `id`, \'character\' as `type`, `name`, `avatar` AS `icon`, 0 AS `crossworld` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN (SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `deleted` IS NULL ORDER BY RAND()) LIMIT '.$number.')
                UNION ALL
                (SELECT `freecompanyid` AS `id`, \'freecompany\' as `type`, `name`, `crest` AS `icon`, 0 AS `crossworld` FROM `'.$this->dbprefix.'freecompany` WHERE `freecompanyid` IN (SELECT `freecompanyid` FROM `'.$this->dbprefix.'freecompany` WHERE `deleted` IS NULL ORDER BY RAND()) LIMIT '.$number.')
                UNION ALL
                (SELECT `linkshellid` AS `id`, \'linkshell\' as `type`, `name`, NULL AS `icon`, `crossworld` FROM `'.$this->dbprefix.'linkshell` WHERE `linkshellid` IN (SELECT `linkshellid` FROM `'.$this->dbprefix.'linkshell` WHERE `deleted` IS NULL ORDER BY RAND()) LIMIT '.$number.')
                UNION ALL
                (SELECT `pvpteamid` AS `id`, \'pvpteam\' as `type`, `name`, `crest` AS `icon`, 1 AS `crossworld` FROM `'.$this->dbprefix.'pvpteam`WHERE `pvpteamid` IN (SELECT `pvpteamid` FROM `'.$this->dbprefix.'pvpteam` WHERE `deleted` IS NULL ORDER BY RAND()) LIMIT '.$number.')
                UNION ALL
                (SELECT `achievementid` AS `id`, \'achievement\' as `type`, `name`, `icon`, 1 AS `crossworld` FROM `'.$this->dbprefix.'achievement` WHERE `achievementid` IN (SELECT `achievementid` FROM `'.$this->dbprefix.'achievement` ORDER BY RAND()) LIMIT '.$number.')
                ORDER BY RAND() LIMIT '.$number.'
        ');
    }
}
?>