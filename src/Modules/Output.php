<?php
declare(strict_types=1);
namespace FFTracker\Modules;

trait Output
{
    #Generalized function to get entity data
    public function TrackerGrab(string $id, string $type): array
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
        return $data;
    }
    
    private function GetCharacter(string $id): array
    {
        $dbcon = (new \SimbiatDB\Controller);
        #Get general information. Using *, but add name, because otherwise Achievement name overrides Character name and we do not want that
        $data = $dbcon->selectRow('SELECT *, `'.$this->dbprefix.'character`.`name`, `'.$this->dbprefix.'character`.`updated` FROM `'.$this->dbprefix.'character` LEFT JOIN `'.$this->dbprefix.'clan` ON `'.$this->dbprefix.'character`.`clanid` = `'.$this->dbprefix.'clan`.`clanid` LEFT JOIN `'.$this->dbprefix.'guardian` ON `'.$this->dbprefix.'character`.`guardianid` = `'.$this->dbprefix.'guardian`.`guardianid` LEFT JOIN `'.$this->dbprefix.'nameday` ON `'.$this->dbprefix.'character`.`namedayid` = `'.$this->dbprefix.'nameday`.`namedayid` LEFT JOIN `'.$this->dbprefix.'city` ON `'.$this->dbprefix.'character`.`cityid` = `'.$this->dbprefix.'city`.`cityid` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'character`.`serverid` = `'.$this->dbprefix.'server`.`serverid` LEFT JOIN `'.$this->dbprefix.'grandcompany_rank` ON `'.$this->dbprefix.'character`.`gcrankid` = `'.$this->dbprefix.'grandcompany_rank`.`gcrankid` LEFT JOIN `'.$this->dbprefix.'achievement` ON `'.$this->dbprefix.'character`.`titleid` = `'.$this->dbprefix.'achievement`.`achievementid` WHERE `'.$this->dbprefix.'character`.`characterid` = :id;', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get old names. For now this is commented out due to cases of bullying, when the old names are learnt. They are still being collected, though for statistical purposes.
        #$data['oldnames'] = $dbcon->selectColumn('SELECT `name` FROM `'.$this->dbprefix.'character_names` WHERE `characterid`=:id AND `name`!=:name', [':id'=>$id, ':name'=>$data['name']]);
        #Get previous known incarnations (combination of gender and race/clan)
        $data['incarnations'] = $dbcon->selectAll('SELECT `genderid`, `'.$this->dbprefix.'clan`.`race`, `'.$this->dbprefix.'clan`.`clan` FROM `'.$this->dbprefix.'character_clans` LEFT JOIN `'.$this->dbprefix.'clan` ON `'.$this->dbprefix.'character_clans`.`clanid` = `'.$this->dbprefix.'clan`.`clanid` WHERE `'.$this->dbprefix.'character_clans`.`characterid`=:id AND (`'.$this->dbprefix.'character_clans`.`clanid`!=:clanid AND `'.$this->dbprefix.'character_clans`.`genderid`!=:genderid) ORDER BY `genderid` ASC, `race` ASC, `clan` ASC', [':id'=>$id, ':clanid'=>$data['clanid'], ':genderid'=>$data['genderid']]);
        #Get old servers
        $data['servers'] = $dbcon->selectAll('SELECT `'.$this->dbprefix.'server`.`datacenter`, `'.$this->dbprefix.'server`.`server` FROM `'.$this->dbprefix.'character_servers` LEFT JOIN `'.$this->dbprefix.'server` ON `'.$this->dbprefix.'server`.`serverid`=`'.$this->dbprefix.'character_servers`.`serverid` WHERE `'.$this->dbprefix.'character_servers`.`characterid`=:id AND `'.$this->dbprefix.'character_servers`.`serverid` != :serverid ORDER BY `datacenter` ASC, `server` ASC', [':id'=>$id, ':serverid'=>$data['serverid']]);
        #Get achievements
        $data['achievements'] = $dbcon->selectAll('SELECT `'.$this->dbprefix.'achievement`.`achievementid`, `'.$this->dbprefix.'achievement`.`category`, `'.$this->dbprefix.'achievement`.`subcategory`, `'.$this->dbprefix.'achievement`.`name`, `time`, `icon` FROM `'.$this->dbprefix.'character_achievement` LEFT JOIN `'.$this->dbprefix.'achievement` ON `'.$this->dbprefix.'character_achievement`.`achievementid`=`'.$this->dbprefix.'achievement`.`achievementid` WHERE `'.$this->dbprefix.'character_achievement`.`characterid` = :id AND `'.$this->dbprefix.'achievement`.`category` IS NOT NULL AND `'.$this->dbprefix.'achievement`.`achievementid` IS NOT NULL ORDER BY `time` DESC, `name` ASC', [':id'=>$id]);
        #Get affiliated groups' details
        $data['groups'] = $dbcon->selectAll(
            '(SELECT \'freecompany\' AS `type`, `'.$this->dbprefix.'freecompany_character`.`freecompanyid` AS `id`, `'.$this->dbprefix.'freecompany`.`name` as `name`, 1 AS `current`, `'.$this->dbprefix.'freecompany_character`.`join`, `'.$this->dbprefix.'freecompany_character`.`rankid`, `'.$this->dbprefix.'freecompany_rank`.`rankname` FROM `'.$this->dbprefix.'freecompany_character` LEFT JOIN `'.$this->dbprefix.'freecompany` ON `'.$this->dbprefix.'freecompany_character`.`freecompanyid`=`'.$this->dbprefix.'freecompany`.`freecompanyid` LEFT JOIN `'.$this->dbprefix.'freecompany_rank` ON `'.$this->dbprefix.'freecompany_character`.`freecompanyid`=`'.$this->dbprefix.'freecompany_rank`.`freecompanyid` AND `'.$this->dbprefix.'freecompany_character`.`rankid`=`'.$this->dbprefix.'freecompany_rank`.`rankid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'freecompany\' AS `type`, `'.$this->dbprefix.'freecompany_x_character`.`freecompanyid` AS `id`, `'.$this->dbprefix.'freecompany`.`name` as `name`, 0 AS `current`, NULL AS `join`, NULL AS `rankid`, NULL AS `rankname` FROM `'.$this->dbprefix.'freecompany_x_character` LEFT JOIN `'.$this->dbprefix.'freecompany` ON `'.$this->dbprefix.'freecompany_x_character`.`freecompanyid`=`'.$this->dbprefix.'freecompany`.`freecompanyid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT IF(`crossworld`=1, \'crossworld_linkshell\', \'linkshell\') AS `type`, `'.$this->dbprefix.'linkshell_character`.`linkshellid` AS `id`, `'.$this->dbprefix.'linkshell`.`name` as `name`, 1 AS `current`, NULL AS `join`, `'.$this->dbprefix.'linkshell_character`.`rankid`, `'.$this->dbprefix.'linkshell_rank`.`rank` AS `rankname` FROM `'.$this->dbprefix.'linkshell_character` LEFT JOIN `'.$this->dbprefix.'linkshell` ON `'.$this->dbprefix.'linkshell_character`.`linkshellid`=`'.$this->dbprefix.'linkshell`.`linkshellid` LEFT JOIN `'.$this->dbprefix.'linkshell_rank` ON `'.$this->dbprefix.'linkshell_character`.`rankid`=`'.$this->dbprefix.'linkshell_rank`.`lsrankid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT IF(`crossworld`=1, \'crossworld_linkshell\', \'linkshell\') AS `type`, `'.$this->dbprefix.'linkshell_x_character`.`linkshellid` AS `id`, `'.$this->dbprefix.'linkshell`.`name` as `name`, 0 AS `current`, NULL AS `join`, NULL AS `rankid`, NULL AS `rankname` FROM `'.$this->dbprefix.'linkshell_x_character` LEFT JOIN `'.$this->dbprefix.'linkshell` ON `'.$this->dbprefix.'linkshell_x_character`.`linkshellid`=`'.$this->dbprefix.'linkshell`.`linkshellid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'pvpteam\' AS `type`, `'.$this->dbprefix.'pvpteam_character`.`pvpteamid` AS `id`, `'.$this->dbprefix.'pvpteam`.`name` as `name`, 1 AS `current`, NULL AS `join`, `'.$this->dbprefix.'pvpteam_character`.`rankid`, `'.$this->dbprefix.'pvpteam_rank`.`rank` AS `rankname` FROM `'.$this->dbprefix.'pvpteam_character` LEFT JOIN `'.$this->dbprefix.'pvpteam` ON `'.$this->dbprefix.'pvpteam_character`.`pvpteamid`=`'.$this->dbprefix.'pvpteam`.`pvpteamid` LEFT JOIN `'.$this->dbprefix.'pvpteam_rank` ON `'.$this->dbprefix.'pvpteam_character`.`rankid`=`'.$this->dbprefix.'pvpteam_rank`.`pvprankid` WHERE `characterid`=:id)
            UNION ALL
            (SELECT \'pvpteam\' AS `type`, `'.$this->dbprefix.'pvpteam_x_character`.`pvpteamid` AS `id`, `'.$this->dbprefix.'pvpteam`.`name` as `name`, 0 AS `current`, NULL AS `join`, NULL AS `rankid`, NULL AS `rankname` FROM `'.$this->dbprefix.'pvpteam_x_character` LEFT JOIN `'.$this->dbprefix.'pvpteam` ON `'.$this->dbprefix.'pvpteam_x_character`.`pvpteamid`=`'.$this->dbprefix.'pvpteam`.`pvpteamid` WHERE `characterid`=:id)
            ORDER BY `current` DESC, `name` ASC;',
            [':id'=>$id]
        );
        #Clean up the data from unnecessary (technical) clutter
        unset($data['clanid'], $data['namedayid'], $data['achievementid'], $data['category'], $data['subcategory'], $data['howto'], $data['points'], $data['icon'], $data['item'], $data['itemicon'], $data['itemid'], $data['serverid']);
        #In case the entry is old enough (at least 1 day old) and register it for update
        if (empty($data['deleted']) && (time() - strtotime($data['updated'])) >= 86400) {
            $this->CronAdd($id, 'character');
        }
        unset($dbcon);
        return $data;
    }
    
    private function GetCompany(string $id): array
    {
        $dbcon = (new \SimbiatDB\Controller);
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
            $this->CronAdd($id, 'freecompany');
        }
        unset($dbcon);
        return $data;
    }
    
    private function GetLinkshell(string $id): array
    {
        $dbcon = (new \SimbiatDB\Controller);
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
                $this->CronAdd($id, 'linkshell');
            } else {
                $this->CronAdd($id, 'crossworldlinkshell');
            }
        }
        unset($dbcon);
        return $data;     
    }
    
    private function GetPVP(string $id): array
    {
        $dbcon = (new \SimbiatDB\Controller);
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
            $this->CronAdd($id, 'pvpteam');
        }
        unset($dbcon);
        return $data;   
    }
    
    private function GetAchievement(string $id): array
    {
        $dbcon = (new \SimbiatDB\Controller);
        #Get general information
        $data = $dbcon->selectRow('SELECT *, (SELECT COUNT(*) FROM `'.$this->dbprefix.'character_achievement` WHERE `'.$this->dbprefix.'character_achievement`.`achievementid` = `'.$this->dbprefix.'achievement`.`achievementid`) as `count` FROM `'.$this->dbprefix.'achievement` WHERE `'.$this->dbprefix.'achievement`.`achievementid` = :id', [':id'=>$id]);
        #Return empty, if nothing was found
        if (empty($data) || !is_array($data)) {
            return [];
        }
        #Get random characters with this achievement
        $data['characters'] = $dbcon->selectAll('SELECT * FROM (SELECT `'.$this->dbprefix.'character`.`characterid`, `'.$this->dbprefix.'character`.`name`, `'.$this->dbprefix.'character`.`avatar` FROM `'.$this->dbprefix.'character_achievement` LEFT JOIN `'.$this->dbprefix.'character` ON `'.$this->dbprefix.'character`.`characterid` = `'.$this->dbprefix.'character_achievement`.`characterid` WHERE `'.$this->dbprefix.'character_achievement`.`achievementid` = :id ORDER BY rand() LIMIT '.$this->maxlines.') t ORDER BY `name`', [':id'=>$id]);
        unset($dbcon);
        return $data;   
    }
    
    #Function to search for entities
    public function Search(string $what = '')
    {
        $dbcon = (new \SimbiatDB\Controller);
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
            $where_pdo = array(':id'=>[(is_int($what) ? intval($what) : $what), (is_int($what) ? 'int' : 'string')], ':name'=>rawurldecode($what).'*');
            #Count entities
            $result['counts'] = $dbcon->selectPair('
                        SELECT \'characters\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'character` WHERE `characterid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                        UNION ALL
                        SELECT \'companies\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'freecompany` WHERE `freecompanyid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                        UNION ALL
                        SELECT \'linkshells\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'linkshell` WHERE `linkshellid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                        UNION ALL
                        SELECT \'pvpteams\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'pvpteam` WHERE `pvpteamid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                        UNION ALL
                        SELECT \'achievements\' AS `type`, COUNT(*) AS `count` FROM `'.$this->dbprefix.'achievement` WHERE `achievementid` = :name OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE) OR MATCH (`howto`) AGAINST (:name IN BOOLEAN MODE)
            ', $where_pdo);
            #If there are actual entities matching the criteria - show $maxlines amount of them
            if (array_sum($result['counts']) > 0) {
                #Need to use a secondary SELECT, because IN BOOLEAN MODE does not sort by default and we need `relevance` column for that, but we do not want to send to client
                $result['entities'] = $dbcon->selectAll('
                        SELECT `id`, `type`, `name`, `icon` FROM (
                            SELECT `characterid` AS `id`, \'character\' as `type`, `name`, `avatar` AS `icon`, IF(`characterid` = :id, 99999, MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'character` WHERE `characterid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                            UNION ALL
                            SELECT `freecompanyid` AS `id`, \'freecompany\' as `type`, `name`, NULL AS `icon`, IF(`freecompanyid` = :id, 99998, MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'freecompany` WHERE `freecompanyid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                            UNION ALL
                            SELECT `linkshellid` AS `id`, IF(`crossworld`=1, \'crossworld_linkshell\', \'linkshell\') as `type`, `name`, NULL AS `icon`, IF(`linkshellid` = :id, 99997, MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'linkshell` WHERE `linkshellid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                            UNION ALL
                            SELECT `pvpteamid` AS `id`, \'pvpteam\' as `type`, `name`, NULL AS `icon`, IF(`pvpteamid` = :id, 99996, MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'pvpteam` WHERE `pvpteamid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)
                            UNION ALL
                            SELECT `achievementid` AS `id`, \'achievement\' as `type`, `name`, `icon`, IF(`achievementid` = :id, 99995, MATCH (`name`) AGAINST (:name IN BOOLEAN MODE)) AS `relevance` FROM `'.$this->dbprefix.'achievement` WHERE `achievementid` = :id OR MATCH (`name`) AGAINST (:name IN BOOLEAN MODE) OR MATCH (`howto`) AGAINST (:name IN BOOLEAN MODE)
                            ORDER BY `relevance` DESC, `name` ASC LIMIT '.$this->maxlines.'
                        ) tempdata
                ', $where_pdo);
            }
        }
        unset($dbcon);
        return $result;
    }
    
    public function Statistics(): array
    {
        #Get Lodestone object for optimization
        $Lodestone = (new \Lodestone\Modules\Converters);
        #Get ArrayHelpers object for optimization
        $ArrayHelpers = (new \ArrayHelpers\ArrayHelpers);
        #Get connection object for slight optimization
        $dbcon = (new \SimbiatDB\Controller);
        #Get statistics by clan
        $data['genetics']['clans'] = $ArrayHelpers->splitByKey($dbcon->countUnique($this->dbprefix.'character', 'clanid', '`'.$this->dbprefix.'character`.`deleted` IS NULL', $this->dbprefix.'clan', 'INNER', 'clanid', '`'.$this->dbprefix.'character`.`genderid`, CONCAT(`'.$this->dbprefix.'clan`.`race`, \' of \', `'.$this->dbprefix.'clan`.`clan`, \' clan\')', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']), 'genderid', ['female', 'male'], [0, 1]);
        #Get statitics by guardian
        $data['genetics']['guardians'] = $dbcon->countUnique($this->dbprefix.'character', 'guardianid', '`'.$this->dbprefix.'character`.`deleted` IS NULL',$this->dbprefix.'guardian', 'INNER', 'guardianid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'guardian`.`guardian`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']);
        #Add colors to guardians
        foreach ($data['genetics']['guardians'] as $key=>$guardian) {
            $data['genetics']['guardians'][$key]['color'] = $Lodestone->colorGuardians($guardian['value']);
        }
        #Split guardians by gender
        $data['genetics']['guardians'] = $ArrayHelpers->splitByKey($data['genetics']['guardians'], 'genderid', ['female', 'male'], [0, 1]);
        #Get statistics by city
        $data['cities'] = $dbcon->countUnique($this->dbprefix.'character', 'cityid', '`'.$this->dbprefix.'character`.`deleted` IS NULL',$this->dbprefix.'city', 'INNER', 'cityid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'city`.`city`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']);
        #Add colors to cities
        foreach ($data['cities'] as $key=>$city) {
            $data['cities'][$key]['color'] = $Lodestone->colorCities($city['value']);
        }
        #Split cities by gender
        $data['cities'] = $ArrayHelpers->splitByKey($data['cities'], 'genderid', ['female', 'male'], [0, 1]);
        $data['cities']['free_company'] = $dbcon->countUnique($this->dbprefix.'freecompany', 'estateid', '`'.$this->dbprefix.'freecompany`.`deleted` IS NULL', $this->dbprefix.'estate', 'INNER', 'estateid', '`'.$this->dbprefix.'estate`.`area`');
        #Add colors to cities
        foreach ($data['cities']['free_company'] as $key=>$city) {
            $data['cities']['free_company'][$key]['color'] = $Lodestone->colorCities($city['value']);
        }
        #Get statistics for grand companies
        $data['grand_companies']['population'] = $dbcon->countUnique($this->dbprefix.'character', 'gcrankid', '`'.$this->dbprefix.'character`.`deleted` IS NULL', $this->dbprefix.'grandcompany_rank', 'INNER', 'gcrankid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'grandcompany_rank`.`gc_name`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']);
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
        #Grand companies ranks
        $data['grand_companies']['ranks'] = $ArrayHelpers->splitByKey($dbcon->countUnique($this->dbprefix.'character', 'gcrankid', '`'.$this->dbprefix.'character`.`deleted` IS NULL', $this->dbprefix.'grandcompany_rank', 'INNER', 'gcrankid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'grandcompany_rank`.`gc_name`, `'.$this->dbprefix.'grandcompany_rank`.`gc_rank`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`', '`'.$this->dbprefix.'grandcompany_rank`.`gc_name`']), 'gc_name', ['maelstrom', 'order', 'flames'], [$Lodestone->getGrandCompanyName(1, 'en'), $Lodestone->getGrandCompanyName(2, 'en'), $Lodestone->getGrandCompanyName(3, 'en')]);
        $data['grand_companies']['ranks']['maelstrom'] = $ArrayHelpers->splitByKey($data['grand_companies']['ranks']['maelstrom'], 'genderid', ['female', 'male'], [0, 1]);
        $data['grand_companies']['ranks']['order'] = $ArrayHelpers->splitByKey($data['grand_companies']['ranks']['order'], 'genderid', ['female', 'male'], [0, 1]);
        $data['grand_companies']['ranks']['flames'] = $ArrayHelpers->splitByKey($data['grand_companies']['ranks']['flames'], 'genderid', ['female', 'male'], [0, 1]);
        #Server-based statistics
        $data['servers']['characters'] = $ArrayHelpers->splitByKey($dbcon->countUnique($this->dbprefix.'character', 'serverid', '`'.$this->dbprefix.'character`.`deleted` IS NULL', $this->dbprefix.'server', 'INNER', 'serverid', '`'.$this->dbprefix.'character`.`genderid`, `'.$this->dbprefix.'server`.`server`', 'DESC', 0, ['`'.$this->dbprefix.'character`.`genderid`']), 'genderid', ['female', 'male'], [0, 1]);
        $data['servers']['female'] = $ArrayHelpers->topAndBottom($data['servers']['characters']['female'], 10);
        $data['servers']['male'] = $ArrayHelpers->topAndBottom($data['servers']['characters']['male'], 10);
        unset($data['servers']['characters']);
        unset($dbcon);
        #rarest achievement (top 10 or 20?)
        #servers: companies, linkshells (and crossworld linkshells), pvpteam
        #character: most name changes, most clan changes, most server changes
        #character: most companies, most linkshells, most pvpteams
        #character: in company and not, in linkshell and not, in pvp team and not, in multiple groups (same on server based?)
        #deleted?: per server companies, linkshells, characters (male, female), pvpteams
        #free companies: number of recruiting, number of activeid, pie of ranks(?)
        #free company: number of rankids (pictures) used
        #free company: pies based on "in search of" and "type"
        #community ids: number of using and not using
        #free company: most popular houses
        #number of updated/registered/deleted enitites on line graph?
        #number of formed linkshells, companies, pvpteams per date on line graph?
        #population: GC by city? clan by city? guardians by city?
        #GC: by clan and by guardian?
        return $data;
    }
    
    public function GetRandomEntities(int $number): array
    {
        return (new \SimbiatDB\Controller)->selectAll('
                (SELECT `characterid` AS `id`, \'character\' as `type`, `name`, `avatar` AS `icon`, 0 AS `crossworld` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN (SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `deleted` IS NULL ORDER BY RAND()) LIMIT '.$number.')
                UNION ALL
                (SELECT `freecompanyid` AS `id`, \'freecompany\' as `type`, `name`, NULL AS `icon`, 0 AS `crossworld` FROM `'.$this->dbprefix.'freecompany` WHERE `freecompanyid` IN (SELECT `freecompanyid` FROM `'.$this->dbprefix.'freecompany` WHERE `deleted` IS NULL ORDER BY RAND()) LIMIT '.$number.')
                UNION ALL
                (SELECT `linkshellid` AS `id`, \'linkshell\' as `type`, `name`, NULL AS `icon`, `crossworld` FROM `'.$this->dbprefix.'linkshell` WHERE `linkshellid` IN (SELECT `linkshellid` FROM `'.$this->dbprefix.'linkshell` WHERE `deleted` IS NULL ORDER BY RAND()) LIMIT '.$number.')
                UNION ALL
                (SELECT `pvpteamid` AS `id`, \'pvpteam\' as `type`, `name`, NULL AS `icon`, 1 AS `crossworld` FROM `'.$this->dbprefix.'pvpteam`WHERE `pvpteamid` IN (SELECT `pvpteamid` FROM `'.$this->dbprefix.'pvpteam` WHERE `deleted` IS NULL ORDER BY RAND()) LIMIT '.$number.')
                UNION ALL
                (SELECT `achievementid` AS `id`, \'achievement\' as `type`, `name`, `icon`, 1 AS `crossworld` FROM `'.$this->dbprefix.'achievement` WHERE `achievementid` IN (SELECT `achievementid` FROM `'.$this->dbprefix.'achievement` ORDER BY RAND()) LIMIT '.$number.')
                ORDER BY RAND() LIMIT '.$number.'
        ');
    }
}
?>