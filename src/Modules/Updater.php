<?php
#Functions used to get update data on tracker
declare(strict_types=1);
namespace FFTracker\Modules;

trait Updater
{
    #Update data
    private function EntityUpdate(array $data): bool
    {
        $result = false;
        switch ($data['entitytype']) {
            case 'character':
                $result = $this->CharacterUpdate($data);
                break;
            case 'freecompany':
                $result = $this->CompanyUpdate($data);
                break;
            case 'linkshell':
                $result = $this->LinkshellUpdate($data);
                break;
            case 'crossworldlinkshell':
                $result = $this->CrossLinkUpdate($data);
                break;
            case 'pvpteam':
                $result = $this->PVPUpdate($data);
                break;
        }
        return $result;
    }
    
    private function CharacterUpdate(array $data): bool
    {
        try {
            #Flags to schedule Free Company or PvPTeam updates
            $fccron=false;
            $pvpcron=false;
            #Main query to insert or update a character
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'character`(
                    `characterid`, `serverid`, `name`, `registered`, `updated`, `deleted`, `biography`, `titleid`, `avatar`, `clanid`, `genderid`, `namedayid`, `guardianid`, `cityid`, `gcrankid`, `Alchemist`, `Armorer`, `Astrologian`, `Bard`, `BlackMage`, `Blacksmith`, `BlueMage`, `Botanist`, `Carpenter`, `Culinarian`, `Dancer`, `DarkKnight`, `Dragoon`, `Fisher`, `Goldsmith`, `Gunbreaker`, `Leatherworker`, `Machinist`, `Miner`, `Monk`, `Ninja`, `Paladin`, `RedMage`, `Samurai`, `Scholar`, `Summoner`, `Warrior`, `Weaver`, `WhiteMage`
                )
                VALUES (
                    :characterid, (SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `server`=:server), :name, UTC_DATE(), UTC_TIMESTAMP(), NULL, :biography, (SELECT `achievementid` as `titleid` FROM `'.$this->dbprefix.'achievement` WHERE `title` IS NOT NULL AND `title`=:title LIMIT 1), :avatar, (SELECT `clanid` FROM `'.$this->dbprefix.'clan` WHERE `clan`=:clan), :genderid, (SELECT `namedayid` FROM `'.$this->dbprefix.'nameday` WHERE `nameday`=:nameday), (SELECT `guardianid` FROM `'.$this->dbprefix.'guardian` WHERE `guardian`=:guardian), (SELECT `cityid` FROM `'.$this->dbprefix.'city` WHERE `city`=:city), `gcrankid` = (SELECT `gcrankid` FROM `'.$this->dbprefix.'grandcompany_rank` WHERE `gc_rank` IS NOT NULL AND `gc_rank`=:gcrank ORDER BY `gcrankid` ASC LIMIT 1), :Alchemist, :Armorer, :Astrologian, :Bard, :BlackMage, :Blacksmith, :BlueMage, :Botanist, :Carpenter, :Culinarian, :Dancer, :DarkKnight, :Dragoon, :Fisher, :Goldsmith, :Gunbreaker, :Leatherworker, :Machinist, :Miner, :Monk, :Ninja, :Paladin, :RedMage, :Samurai, :Scholar, :Summoner, :Warrior, :Weaver, :WhiteMage
                )
                ON DUPLICATE KEY UPDATE
                    `serverid`=(SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `server`=:server), `name`=:name, `updated`=UTC_TIMESTAMP(), `deleted`=NULL, `biography`=:biography, `titleid`=(SELECT `achievementid` as `titleid` FROM `'.$this->dbprefix.'achievement` WHERE `title` IS NOT NULL AND `title`=:title LIMIT 1), `avatar`=:avatar, `clanid`=(SELECT `clanid` FROM `'.$this->dbprefix.'clan` WHERE `clan`=:clan), `genderid`=:genderid, `namedayid`=(SELECT `namedayid` FROM `'.$this->dbprefix.'nameday` WHERE `nameday`=:nameday), `guardianid`=(SELECT `guardianid` FROM `'.$this->dbprefix.'guardian` WHERE `guardian`=:guardian), `cityid`=(SELECT `cityid` FROM `'.$this->dbprefix.'city` WHERE `city`=:city), `gcrankid`=(SELECT `gcrankid` FROM `'.$this->dbprefix.'grandcompany_rank` WHERE `gc_rank` IS NOT NULL AND `gc_rank`=:gcrank ORDER BY `gcrankid` ASC LIMIT 1), `Alchemist`=GREATEST(`Alchemist`, :Alchemist), `Armorer`=GREATEST(`Armorer`, :Armorer), `Astrologian`=GREATEST(`Astrologian`, :Astrologian), `Bard`=GREATEST(`Bard`, :Bard), `BlackMage`=GREATEST(`BlackMage`, :BlackMage), `Blacksmith`=GREATEST(`Blacksmith`, :Blacksmith), `BlueMage`=GREATEST(`BlueMage`, :BlueMage), `Botanist`=GREATEST(`Botanist`, :Botanist), `Carpenter`=GREATEST(`Carpenter`, :Carpenter), `Culinarian`=GREATEST(`Culinarian`, :Culinarian), `Dancer`=GREATEST(`Dancer`, :Dancer), `DarkKnight`=GREATEST(`DarkKnight`, :DarkKnight), `Dragoon`=GREATEST(`Dragoon`, :Dragoon), `Fisher`=GREATEST(`Fisher`, :Fisher), `Goldsmith`=GREATEST(`Goldsmith`, :Goldsmith), `Gunbreaker`=GREATEST(`Gunbreaker`, :Gunbreaker), `Leatherworker`=GREATEST(`Leatherworker`, :Leatherworker), `Machinist`=GREATEST(`Machinist`, :Machinist), `Miner`=GREATEST(`Miner`, :Miner), `Monk`=GREATEST(`Monk`, :Monk), `Ninja`=GREATEST(`Ninja`, :Ninja), `Paladin`=GREATEST(`Paladin`, :Paladin), `RedMage`=GREATEST(`RedMage`, :RedMage), `Samurai`=GREATEST(`Samurai`, :Samurai), `Scholar`=GREATEST(`Scholar`, :Scholar), `Summoner`=GREATEST(`Summoner`, :Summoner), `Warrior`=GREATEST(`Warrior`, :Warrior), `Weaver`=GREATEST(`Weaver`, :Weaver), `WhiteMage`=GREATEST(`WhiteMage`, :WhiteMage);',
                [
                    ':characterid'=>$data['characterid'],
                    ':server'=>$data['server'],
                    ':name'=>$data['name'],
                    ':biography'=>[
                            (($data['bio'] == '-') ? NULL : $data['bio']),
                            (empty($data['bio']) ? 'null' : (($data['bio'] == '-') ? 'null' : 'string')),
                        ],
                    ':title'=>(empty($data['title']) ? '' : $data['title']),
                    ':avatar'=>str_replace(['https://img2.finalfantasyxiv.com/f/', 'c0_96x96.jpg'], '', $data['avatar']),
                    ':clan'=>$data['clan'],
                    ':genderid'=>($data['gender']==='male' ? '1' : '0'),
                    ':nameday'=>$data['nameday'],
                    ':guardian'=>$data['guardian']['name'],
                    ':city'=>$data['city']['name'],
                    ':gcrank'=>(empty($data['grandCompany']['rank']) ? '' : $data['grandCompany']['rank']),
                    ':Alchemist'=>(empty($data['jobs']['Alchemist']['level']) ? '0' : $data['jobs']['Alchemist']['level']),
                    ':Armorer'=>(empty($data['jobs']['Armorer']['level']) ? '0' : $data['jobs']['Armorer']['level']),
                    ':Astrologian'=>(empty($data['jobs']['Astrologian']['level']) ? '0' : $data['jobs']['Astrologian']['level']),
                    ':Bard'=>(empty($data['jobs']['Bard']['level']) ? '0' : $data['jobs']['Bard']['level']),
                    ':BlackMage'=>(empty($data['jobs']['Black Mage']['level']) ? '0' : $data['jobs']['Black Mage']['level']),
                    ':Blacksmith'=>(empty($data['jobs']['Blacksmith']['level']) ? '0' : $data['jobs']['Blacksmith']['level']),
                    ':BlueMage'=>(empty($data['jobs']['Blue Mage']['level']) ? '0' : $data['jobs']['Blue Mage']['level']),
                    ':Botanist'=>(empty($data['jobs']['Botanist']['level']) ? '0' : $data['jobs']['Botanist']['level']),
                    ':Carpenter'=>(empty($data['jobs']['Carpenter']['level']) ? '0' : $data['jobs']['Carpenter']['level']),
                    ':Culinarian'=>(empty($data['jobs']['Culinarian']['level']) ? '0' : $data['jobs']['Culinarian']['level']),
                    ':Dancer'=>(empty($data['jobs']['Dancer']['level']) ? '0' : $data['jobs']['Dancer']['level']),
                    ':DarkKnight'=>(empty($data['jobs']['Dark Knight']['level']) ? '0' : $data['jobs']['Dark Knight']['level']),
                    ':Dragoon'=>(empty($data['jobs']['Dragoon']['level']) ? '0' : $data['jobs']['Dragoon']['level']),
                    ':Fisher'=>(empty($data['jobs']['Fisher']['level']) ? '0' : $data['jobs']['Fisher']['level']),
                    ':Goldsmith'=>(empty($data['jobs']['Goldsmith']['level']) ? '0' : $data['jobs']['Goldsmith']['level']),
                    ':Gunbreaker'=>(empty($data['jobs']['Gunbreaker']['level']) ? '0' : $data['jobs']['Gunbreaker']['level']),
                    ':Leatherworker'=>(empty($data['jobs']['Leatherworker']['level']) ? '0' : $data['jobs']['Leatherworker']['level']),
                    ':Machinist'=>(empty($data['jobs']['Machinist']['level']) ? '0' : $data['jobs']['Machinist']['level']),
                    ':Miner'=>(empty($data['jobs']['Miner']['level']) ? '0' : $data['jobs']['Miner']['level']),
                    ':Monk'=>(empty($data['jobs']['Monk']['level']) ? '0' : $data['jobs']['Monk']['level']),
                    ':Ninja'=>(empty($data['jobs']['Ninja']['level']) ? '0' : $data['jobs']['Ninja']['level']),
                    ':Paladin'=>(empty($data['jobs']['Paladin']['level']) ? '0' : $data['jobs']['Paladin']['level']),
                    ':RedMage'=>(empty($data['jobs']['Red Mage']['level']) ? '0' : $data['jobs']['Red Mage']['level']),
                    ':Samurai'=>(empty($data['jobs']['Samurai']['level']) ? '0' : $data['jobs']['Samurai']['level']),
                    ':Scholar'=>(empty($data['jobs']['Scholar']['level']) ? '0' : $data['jobs']['Scholar']['level']),
                    ':Summoner'=>(empty($data['jobs']['Summoner']['level']) ? '0' : $data['jobs']['Summoner']['level']),
                    ':Warrior'=>(empty($data['jobs']['Warrior']['level']) ? '0' : $data['jobs']['Warrior']['level']),
                    ':Weaver'=>(empty($data['jobs']['Weaver']['level']) ? '0' : $data['jobs']['Weaver']['level']),
                    ':WhiteMage'=>(empty($data['jobs']['White Mage']['level']) ? '0' : $data['jobs']['White Mage']['level']),
                ],
            ];
            #Insert server, if it has not been inserted yet
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'character_servers`(`characterid`, `serverid`) VALUES (:characterid, (SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `server`=:server)) ON DUPLICATE KEY UPDATE `serverid`=`serverid`;',
                [
                    ':characterid'=>$data['characterid'],
                    ':server'=>$data['server'],
                ],
            ];
            #Insert name, if it has not been inserted yet
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'character_names`(`characterid`, `name`) VALUES (:characterid, :name) ON DUPLICATE KEY UPDATE `name`=`name`;',
                [
                    ':characterid'=>$data['characterid'],
                    ':name'=>$data['name'],
                ],
            ];
            #Insert race, clan and sex combination, if it has not been inserted yet
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'character_clans`(`characterid`, `genderid`, `clanid`) VALUES (:characterid, :genderid, (SELECT `clanid` FROM `'.$this->dbprefix.'clan` WHERE `clan`=:clan)) ON DUPLICATE KEY UPDATE `clanid`=`clanid`;',
                [
                    ':characterid'=>$data['characterid'],
                    ':genderid'=>($data['gender']==='male' ? '1' : '0'),
                    ':clan'=>$data['clan'],
                ],
            ];
            #Check if present in Free Company
            if (empty($data['freeCompany']['id'])) {
                $queries = array_merge($queries, $this->RemoveFromGroup($data['characterid'], 'freecompany'));
            } else {
                #Check if not already registered in this Free Company
                if (!(new \SimbiatDB\Controller)->check('SELECT `characterid` FROM `'.$this->dbprefix.'freecompany_character` WHERE `characterid`=:characterid AND `freecompanyid`=:freecompanyid', [':characterid'=>$data['characterid'],':freecompanyid'=>$data['freeCompany']['id']]) === true) {
                    #Remove character from other companies
                    $queries = array_merge($queries, $this->RemoveFromGroup($data['characterid'], 'freecompany'));
                    #Add to company (without rank) if the company is already registered. Needed to prevent grabbing data for the character again during company update. If company is not registered yet - nothing will happen
                    $query[] = [
                        'INSERT INTO `'.$this->dbprefix.'freecompany_character`(`characterid`, `freecompanyid`) SELECT (SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid`=:characterid) AS `characterid`, (SELECT `freecompanyid` FROM `ff__freecompany` WHERE `freecompanyid`=:freecompanyid) AS `freecompanyid` FROM DUAL HAVING `freecompanyid` IS NOT NULL;',
                        [
                            ':characterid'=>$data['characterid'],
                            ':freecompanyid'=>$data['freeCompany']['id'],
                        ]
                    ];
                    #Flag to schedule company update after character is updated/inserted
                    $fccron=true;
                }
            }
            #Check if present in PvP Team
            if (empty($data['pvp']['id'])) {
                $queries = array_merge($queries, $this->RemoveFromGroup($data['characterid'], 'pvpteam'));
            } else {
                #Check if not already registered in this PvP Team
                if (!(new \SimbiatDB\Controller)->check('SELECT `characterid` FROM `'.$this->dbprefix.'pvpteam_character` WHERE `characterid`=:characterid AND `pvpteamid`=:pvpteamid', [':characterid'=>$data['characterid'],':pvpteamid'=>$data['pvp']['id']])) {
                    #Remove character from other teams
                    $queries = array_merge($queries, $this->RemoveFromGroup($data['characterid'], 'pvpteam'));
                    #Add to team (without rank) if the team is already registered. Needed to prevent grabbing data for the character again during team update. If team is not registered yet - nothing will happen
                    $query[] = [
                        'INSERT INTO `'.$this->dbprefix.'pvpteam_character`(`characterid`, `pvpteamid`) SELECT (SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid`=:characterid) AS `characterid`, (SELECT `pvpteamid` FROM `ff__pvpteam` WHERE `pvpteamid`=:pvpteamid) AS `pvpteamid` FROM DUAL HAVING `pvpteamid` IS NOT NULL;',
                        [
                            ':characterid'=>$data['characterid'],
                            ':pvpteamid'=>$data['pvp']['id'],
                        ]
                    ];
                    #Flag to schedule team update after character is updated/inserted
                    $pvpcron=true;
                }
            }
            #Achievements
            if (!empty($data['achievements']) && is_array($data['achievements'])) {
                foreach ($data['achievements'] as $achievementid=>$item) {
                    $queries[] = [
                        'INSERT INTO `'.$this->dbprefix.'achievement` SET `achievementid`=:achievementid, `name`=:name, `icon`=:icon, `points`=:points ON DUPLICATE KEY UPDATE `updated`=`updated`, `name`=:name, `icon`=:icon, `points`=:points;',
                        [
                            ':achievementid'=>$achievementid,
                            ':name'=>$item['name'],
                            ':icon'=>str_replace("https://img.finalfantasyxiv.com/lds/pc/global/images/itemicon/", "", $item['icon']),
                            ':points'=>$item['points'],
                        ],
                    ];
                    $queries[] = [
                        'INSERT INTO `'.$this->dbprefix.'character_achievement` SET `characterid`=:characterid, `achievementid`=:achievementid, `time`=UTC_DATE() ON DUPLICATE KEY UPDATE `time`=:time;',
                        [
                            ':characterid'=>$data['characterid'],
                            ':achievementid'=>$achievementid,
                            ':time'=>[$item['time'], 'date'],
                        ],
                    ];
                }
            }
            (new \SimbiatDB\Controller)->query($queries);
            #Remove from cron, in case update was triggered from there
            $this->CronRemove('character', $data['characterid']);
            #Register Free Company update if change was detected
            if ($fccron) {
                #If we have triggered this from within Free Company update, it will simply update the next run time which should not affect anything
                $this->CronAdd($data['freeCompany']['id'], 'freecompany');
            }
            #Register PvP Team update if change was detected
            if ($pvpcron) {
                #If we have triggered this from within PvP Team update, it will simply update the next run time which should not affect anything
                $this->CronAdd($data['pvp']['id'], 'pvpteam');
            }
            #Remove cron entry (if exists)
            $this->CronRemove($data['characterid'], 'character');
            return true;
        } catch(\Exception $e) {
            #Update cron entry (if exists) with the error
            $this->CronError($data['characterid'], 'character', $e->getTraceAsString());
            return false;
        }
    }
    
    private function CompanyUpdate(array $data): bool
    {
        try {
            #Attempt to get crest
            $data['crest'] = $this->CrestMerge($data['freecompanyid'], $data['crest']);
            #Main query to insert or update a Free Company
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'freecompany` (
                    `freecompanyid`, `name`, `serverid`, `formed`, `registered`, `updated`, `deleted`, `grandcompanyid`, `tag`, `crest`, `rank`, `slogan`, `activeid`, `recruitment`, `communityid`, `estate_zone`, `estateid`, `estate_message`, `Role-playing`, `Leveling`, `Casual`, `Hardcore`, `Dungeons`, `Guildhests`, `Trials`, `Raids`, `PvP`, `Tank`, `Healer`, `DPS`, `Crafter`, `Gatherer`
                )
                VALUES (
                    :freecompanyid, :name, (SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `server`=:server), :formed, UTC_DATE(), UTC_TIMESTAMP(), NULL, (SELECT `gcrankid` FROM `'.$this->dbprefix.'grandcompany_rank` WHERE `gc_name`=:grandcompany ORDER BY `gcrankid` ASC LIMIT 1), :tag, :crest, :rank, :slogan, (SELECT `activeid` FROM `'.$this->dbprefix.'timeactive` WHERE `active`=:active AND `active` IS NOT NULL LIMIT 1), :recruitment, :communityid, :estate_zone, (SELECT `estateid` FROM `'.$this->dbprefix.'estate` WHERE CONCAT(\'Plot \', `plot`, \', \', `ward`, \' Ward, \', `area`, \' (\', CASE WHEN `size` = 1 THEN \'Small\' WHEN `size` = 2 THEN \'Medium\' WHEN `size` = 3 THEN \'Large\' END, \')\')=:estate_address LIMIT 1), :estate_message, :roleplaying, :leveling, :casual, :hardcore, :dungeons, :guildhests, :trials, :raids, :pvp, :tank, :healer, :dps, :crafter, :gatherer
                )
                ON DUPLICATE KEY UPDATE
                    `name`=:name, `serverid`=(SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `server`=:server), `updated`=UTC_TIMESTAMP(), `deleted`=NULL, `tag`=:tag, `crest`=COALESCE(:crest, `crest`), `rank`=:rank, `slogan`=:slogan, `activeid`=(SELECT `activeid` FROM `'.$this->dbprefix.'timeactive` WHERE `active`=:active AND `active` IS NOT NULL LIMIT 1), `recruitment`=:recruitment, `communityid`=:communityid, `estate_zone`=:estate_zone, `estateid`=(SELECT `estateid` FROM `'.$this->dbprefix.'estate` WHERE CONCAT(\'Plot \', `plot`, \', \', `ward`, \' Ward, \', `area`, \' (\', CASE WHEN `size` = 1 THEN \'Small\' WHEN `size` = 2 THEN \'Medium\' WHEN `size` = 3 THEN \'Large\' END, \')\')=:estate_address LIMIT 1), `estate_message`=:estate_message, `Role-playing`=:roleplaying, `Leveling`=:leveling, `Casual`=:casual, `Hardcore`=:hardcore, `Dungeons`=:dungeons, `Guildhests`=:guildhests, `Trials`=:trials, `Raids`=:raids, `PvP`=:pvp, `Tank`=:tank, `Healer`=:healer, `DPS`=:dps, `Crafter`=:crafter, `Gatherer`=:gatherer;',
                [
                    ':freecompanyid'=>$data['freecompanyid'],
                    ':name'=>$data['name'],
                    ':server'=>$data['server'],
                    ':formed'=>[$data['formed'], 'date'],
                    ':grandcompany'=>$data['grandCompany'],
                    ':tag'=>$data['tag'],
                    ':crest'=>[
                            (empty($data['crest']) ? NULL : $data['crest']),
                            (empty($data['crest']) ? 'null' : 'string'),
                    ],
                    ':rank'=>$data['rank'],
                    ':slogan'=>[
                            (empty($data['slogan']) ? NULL : $data['slogan']),
                            (empty($data['slogan']) ? 'null' : 'string'),
                    ],
                    ':active'=>[
                            (($data['active'] == 'Not specified') ? NULL : (empty($data['active']) ? NULL : $data['active'])),
                            (($data['active'] == 'Not specified') ? 'null' : (empty($data['active']) ? 'null' : 'string')),
                    ],
                    ':recruitment'=>(strcasecmp($data['recruitment'], 'Open') === 0 ? 1 : 0),
                    ':estate_zone'=>[
                            (empty($data['estate']['name']) ? NULL : $data['estate']['name']),
                            (empty($data['estate']['name']) ? 'null' : 'string'),
                    ],
                    ':estate_address'=>[
                            (empty($data['estate']['address']) ? NULL : $data['estate']['address']),
                            (empty($data['estate']['address']) ? 'null' : 'string'),
                    ],
                    ':estate_message'=>[
                            (empty($data['estate']['greeting']) ? NULL : $data['estate']['greeting']),
                            (empty($data['estate']['greeting']) ? 'null' : 'string'),
                    ],
                    ':roleplaying'=>(empty($data['focus']) ? 0 : $data['focus'][array_search('Role-playing', array_column($data['focus'], 'name'))]['enabled']),
                    ':leveling'=>(empty($data['focus']) ? 0 : $data['focus'][array_search('Leveling', array_column($data['focus'], 'name'))]['enabled']),
                    ':casual'=>(empty($data['focus']) ? 0 : $data['focus'][array_search('Casual', array_column($data['focus'], 'name'))]['enabled']),
                    ':hardcore'=>(empty($data['focus']) ? 0 : $data['focus'][array_search('Hardcore', array_column($data['focus'], 'name'))]['enabled']),
                    ':dungeons'=>(empty($data['focus']) ? 0 : $data['focus'][array_search('Dungeons', array_column($data['focus'], 'name'))]['enabled']),
                    ':guildhests'=>(empty($data['focus']) ? 0 : $data['focus'][array_search('Guildhests', array_column($data['focus'], 'name'))]['enabled']),
                    ':trials'=>(empty($data['focus']) ? 0 : $data['focus'][array_search('Trials', array_column($data['focus'], 'name'))]['enabled']),
                    ':raids'=>(empty($data['focus']) ? 0 : $data['focus'][array_search('Raids', array_column($data['focus'], 'name'))]['enabled']),
                    ':pvp'=>(empty($data['focus']) ? 0 : $data['focus'][array_search('PvP', array_column($data['focus'], 'name'))]['enabled']),
                    ':tank'=>(empty($data['seeking']) ? 0 : $data['seeking'][array_search('Tank', array_column($data['seeking'], 'name'))]['enabled']),
                    ':healer'=>(empty($data['seeking']) ? 0 : $data['seeking'][array_search('Healer', array_column($data['seeking'], 'name'))]['enabled']),
                    ':dps'=>(empty($data['seeking']) ? 0 : $data['seeking'][array_search('DPS', array_column($data['seeking'], 'name'))]['enabled']),
                    ':crafter'=>(empty($data['seeking']) ? 0 : $data['seeking'][array_search('Crafter', array_column($data['seeking'], 'name'))]['enabled']),
                    ':gatherer'=>(empty($data['seeking']) ? 0 : $data['seeking'][array_search('Gatherer', array_column($data['seeking'], 'name'))]['enabled']),
                    ':communityid'=>[
                            (empty($data['communityid']) ? NULL : $data['communityid']),
                            (empty($data['communityid']) ? 'null' : 'string'),
                    ],
                ],
            ];
            #Register Free Company name if it's not registered already
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'freecompany_names`(`freecompanyid`, `name`) VALUES (:freecompanyid, :name) ON DUPLICATE KEY UPDATE `name`=`name`;',
                [
                    ':freecompanyid'=>$data['freecompanyid'],
                    ':name'=>$data['name'],
                ],
            ];
            #Generating list of IDs for selects to process members
            $members = [];
            #There were cases when some characters had non-numeric symbols on certain HTML pages, this is more of a precaution now
            if (!empty($data['members'])) {
                foreach ($data['members'] as $memberid=>$member) {
                    if (preg_match('/^\d{1,10}$/', strval($memberid))) {
                        $members[] = '\''.$memberid.'\'';
                    }
                }
            }
            #Adding ranking at this point since it needs members count, that we just got
            if (!empty($data['weekly_rank']) && !empty($data['monthly_rank'])) {
                $queries[] = [
                    'INSERT INTO `'.$this->dbprefix.'freecompany_ranking` (`freecompanyid`, `date`, `weekly`, `monthly`, `members`) SELECT * FROM (SELECT :freecompanyid AS `freecompanyid`, UTC_DATE() AS `date`, :weekly AS `weekly`, :monthly AS `monthly`, :members AS `members` FROM DUAL WHERE :freecompanyid NOT IN (SELECT `freecompanyid` FROM (SELECT * FROM `ff__freecompany_ranking` WHERE `freecompanyid`=:freecompanyid ORDER BY `date` DESC LIMIT 1) `lastrecord` WHERE `weekly`=:weekly AND `monthly`=:monthly) LIMIT 1) `actualinsert` ON DUPLICATE KEY UPDATE `weekly`=:weekly, `monthly`=:monthly, `members`=:members;',
                    [
                        ':freecompanyid'=>$data['freecompanyid'],
                        ':weekly'=>$data['weekly_rank'],
                        ':monthly'=>$data['monthly_rank'],
                        ':members'=>count($members),
                    ],
                ];
            }
            #Set list of members for select and list of members already registered
            if (empty($members)) {
                $inmembers = '\'\'';
                $regmembers = [];
            } else {
                $inmembers = implode(',', $members);
                $regmembers = (new \SimbiatDB\Controller)->selectColumn('SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN ('.$inmembers.')');
            }
            if (!empty($data['members'])) {
                foreach ($data['members'] as $memberid=>$member) {
                    if (preg_match('/^\d{1,10}$/', strval($memberid)) && !empty($member['rank'])) {
                        #Register or update rank names
                        $queries[] = [
                                'INSERT INTO `'.$this->dbprefix.'freecompany_rank` (`freecompanyid`, `rankid`, `rankname`) VALUE (:freecompanyid, :rankid, :rankname) ON DUPLICATE KEY UPDATE `rankname`=:rankname',
                                [
                                    ":freecompanyid"=>$data['freecompanyid'],
                                    ":rankid"=>$member['rankid'],
                                    ":rankname"=>$member['rank'],
                                ],
                            ];
                        #Actually registering/updating members
                        if (in_array(strval($memberid), $regmembers) || (!in_array(strval($memberid), $regmembers) && $this->Update(strval($memberid), 'character') === true)) {
                            $queries[] = [
                                'INSERT INTO `'.$this->dbprefix.'freecompany_character` (`characterid`, `freecompanyid`, `join`, `rankid`) VALUES (:memberid, :freecompanyid, UTC_DATE(), :rankid) ON DUPLICATE KEY UPDATE `rankid`=:rankid;',
                                [
                                    ':memberid'=>$memberid,
                                    ':freecompanyid'=>$data['freecompanyid'],
                                    ':rankid'=>$member['rankid'],
                                ],
                            ];
                        }
                    }
                }
            }
            #Mass remove characters, that left Free Company
            $queries = array_merge($queries, $this->MassRemoveFromGroup($data['freecompanyid'], 'freecompany', $inmembers));
            #Running the queries we've accumulated
            (new \SimbiatDB\Controller)->query($queries);
            #Remove cron entry (if exists)
            $this->CronRemove($data['freecompanyid'], 'freecompany');
            return true;
        } catch(\Exception $e) {
            #Update cron entry (if exists) with the error
            $this->CronError($data['freecompanyid'], 'freecompany', $e->getTraceAsString());
            return false;
        }
    }
    
    private function LinkshellUpdate(array $data): bool
    {
        try {
            #Main query to insert or update a Linkshell
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'linkshell`(`linkshellid`, `name`, `crossworld`, `formed`, `registered`, `updated`, `deleted`, `serverid`) VALUES (:linkshellid, :name, 0, NULL, UTC_DATE(), UTC_TIMESTAMP(), NULL, (SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `server`=:server)) ON DUPLICATE KEY UPDATE `name`=:name, `formed`=NULL, `updated`=UTC_TIMESTAMP(), `deleted`=NULL, `serverid`=(SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `server`=:server), `communityid`=:communityid;',
                [
                    ':linkshellid'=>$data['linkshellid'],
                    ':server'=>$data['server'],
                    ':name'=>$data['name'],
                    ':communityid'=>[
                            (empty($data['communityid']) ? NULL : $data['communityid']),
                            (empty($data['communityid']) ? 'null' : 'string'),
                    ],
                ],
            ];
            #Register Linkshell name if it's not registered already
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'linkshell_names`(`linkshellid`, `name`) VALUES (:linkshellid, :name) ON DUPLICATE KEY UPDATE `name`=`name`;',
                [
                    ':linkshellid'=>$data['linkshellid'],
                    ':name'=>$data['name'],
                ],
            ];
            #Generating list of IDs for selects to process members
            $members = [];
            #There were cases when some characters had non-numeric symbols on certain HTML pages, this is more of a precaution now
            if (!empty($data['members'])) {
                foreach ($data['members'] as $memberid=>$member) {
                    if (preg_match('/^\d{1,10}$/', strval($memberid))) {
                        $members[] = '\''.$memberid.'\'';
                    }
                }
            }
            #Set list of members for select and list of members already registered
            if (empty($members)) {
                $inmembers = '\'\'';
                $regmembers = [];
            } else {
                $inmembers = implode(',', $members);
                $regmembers = (new \SimbiatDB\Controller)->selectColumn('SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN ('.$inmembers.')');
            }
            #Actually registering/updating members
            if (!empty($data['members'])) {
                foreach ($data['members'] as $memberid=>$member) {
                    if (preg_match('/^\d{1,10}$/', strval($memberid))) {
                        if (in_array(strval($memberid), $regmembers) || (!in_array(strval($memberid), $regmembers) && $this->Update(strval($memberid), 'character') === true)) {
                            $queries[] = [
                                'INSERT INTO `'.$this->dbprefix.'linkshell_character` (`linkshellid`, `characterid`, `rankid`) VALUES (:linkshellid, :memberid, (SELECT `lsrankid` FROM `'.$this->dbprefix.'linkshell_rank` WHERE `rank`=:rank AND `rank` IS NOT NULL LIMIT 1)) ON DUPLICATE KEY UPDATE `rankid`=(SELECT `lsrankid` FROM `'.$this->dbprefix.'linkshell_rank` WHERE `rank`=:rank AND `rank` IS NOT NULL LIMIT 1);',
                                [
                                    ':linkshellid'=>$data['linkshellid'],
                                    ':memberid'=>$memberid,
                                    ':rank'=>(empty($member['rank']) ? 'Member' : $member['rank'])
                                ],
                            ];
                        }
                    }
                }
            }
            #Mass remove characters, that left Free Company
            $queries = array_merge($queries, $this->MassRemoveFromGroup($data['linkshellid'], 'linkshell', $inmembers));
            #Running the queries we've accumulated
            (new \SimbiatDB\Controller)->query($queries);
            #Remove cron entry (if exists)
            $this->CronRemove($data['linkshellid'], 'linkshell');
            return true;
        } catch(Exception $e) {
            #Update cron entry (if exists) with the error
            $this->CronError($data['linkshellid'], 'linkshell', $e->getTraceAsString());
            return false;
        }
    }
    
    private function CrossLinkUpdate(array $data): bool
    {
        try {
            #Main query to insert or update a Linkshell
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'linkshell`(`linkshellid`, `name`, `crossworld`, `formed`, `registered`, `updated`, `deleted`, `serverid`, `communityid`) VALUES (:linkshellid, :name, 1, :formed, UTC_DATE(), UTC_TIMESTAMP(), NULL, (SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `datacenter`=:datacenter LIMIT 1), :communityid) ON DUPLICATE KEY UPDATE `name`=:name, `formed`=:formed, `updated`=UTC_TIMESTAMP(), `deleted`=NULL, `serverid`=(SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `datacenter`=:datacenter LIMIT 1), `communityid`=:communityid;',
                [
                    ':linkshellid'=>$data['linkshellid'],
                    ':datacenter'=>$data['dataCenter'],
                    ':name'=>$data['name'],
                    ':formed'=>[$data['formed'], 'date'],
                    ':communityid'=>[
                            (empty($data['communityid']) ? NULL : $data['communityid']),
                            (empty($data['communityid']) ? 'null' : 'string'),
                    ],
                ],
            ];
            #Register Linkshell name if it's not registered already
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'linkshell_names`(`linkshellid`, `name`) VALUES (:linkshellid, :name) ON DUPLICATE KEY UPDATE `name`=`name`;',
                [
                    ':linkshellid'=>$data['linkshellid'],
                    ':name'=>$data['name'],
                ],
            ];
            #Generating list of IDs for selects to process members
            $members = [];
            #There were cases when some characters had non-numeric symbols on certain HTML pages, this is more of a precaution now
            if (!empty($data['members'])) {
                foreach ($data['members'] as $memberid=>$member) {
                    if (preg_match('/^\d{1,10}$/', strval($memberid))) {
                        $members[] = '\''.$memberid.'\'';
                    }
                }
            }
            #Set list of members for select and list of members already registered
            if (empty($members)) {
                $inmembers = '\'\'';
                $regmembers = [];
            } else {
                $inmembers = implode(',', $members);
                $regmembers = (new \SimbiatDB\Controller)->selectColumn('SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN ('.$inmembers.')');
            }
            #Actually registering/updating members
            if (!empty($data['members'])) {
                foreach ($data['members'] as $memberid=>$member) {
                    if (preg_match('/^\d{1,10}$/', strval($memberid))) {
                        if (in_array(strval($memberid), $regmembers) || (!in_array(strval($memberid), $regmembers) && $this->Update(strval($memberid), 'character') === true)) {
                            $queries[] = [
                                'INSERT INTO `'.$this->dbprefix.'linkshell_character` (`linkshellid`, `characterid`, `rankid`) VALUES (:linkshellid, :memberid, (SELECT `lsrankid` FROM `'.$this->dbprefix.'linkshell_rank` WHERE `rank`=:rank AND `rank` IS NOT NULL LIMIT 1)) ON DUPLICATE KEY UPDATE `rankid`=(SELECT `lsrankid` FROM `'.$this->dbprefix.'linkshell_rank` WHERE `rank`=:rank AND `rank` IS NOT NULL LIMIT 1);',
                                [
                                    ':linkshellid'=>$data['linkshellid'],
                                    ':memberid'=>$memberid,
                                    ':rank'=>(empty($member['rank']) ? 'Member' : $member['rank'])
                                ],
                            ];
                        }
                    }
                }
            }
            #Mass remove characters, that left Free Company
            $queries = array_merge($queries, $this->MassRemoveFromGroup($data['linkshellid'], 'linkshell', $inmembers));
            #Running the queries we've accumulated
            (new \SimbiatDB\Controller)->query($queries);
            #Remove cron entry (if exists)
            $this->CronRemove($data['linkshellid'], 'crossworldlinkshell');
            return true;
        } catch(Exception $e) {
            #Update cron entry (if exists) with the error
            $this->CronError($data['linkshellid'], 'crossworldlinkshell', $e->getTraceAsString());
            return false;
        }
    }
    
    private function PVPUpdate(array $data): bool
    {
        try {
            #Attempt to get crest
            $data['crest'] = $this->CrestMerge($data['pvpteamid'], $data['crest']);
            #Main query to insert or update a PvP Team
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'pvpteam` (`pvpteamid`, `name`, `formed`, `registered`, `updated`, `deleted`, `datacenterid`, `communityid`, `crest`) VALUES (:pvpteamid, :name, :formed, UTC_DATE(), UTC_TIMESTAMP(), NULL, (SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `datacenter`=:datacenter ORDER BY `serverid` LIMIT 1), :communityid, :crest) ON DUPLICATE KEY UPDATE `name`=:name, `formed`=:formed, `updated`=UTC_TIMESTAMP(), `deleted`=NULL, `datacenterid`=(SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `datacenter`=:datacenter ORDER BY `serverid` LIMIT 1), `communityid`=:communityid, `crest`=COALESCE(:crest, `crest`);',
                [
                    ':pvpteamid'=>$data['pvpteamid'],
                    ':datacenter'=>$data['dataCenter'],
                    ':name'=>$data['name'],
                    ':formed'=>[$data['formed'], 'date'],
                    ':communityid'=>[
                            (empty($data['communityid']) ? NULL : $data['communityid']),
                            (empty($data['communityid']) ? 'null' : 'string'),
                    ],
                    ':crest'=>[
                            (empty($data['crest']) ? NULL : $data['crest']),
                            (empty($data['crest']) ? 'null' : 'string'),
                    ]
                ],
            ];
            #Register PvP Team name if it's not registered already
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'pvpteam_names`(`pvpteamid`, `name`) VALUES (:pvpteamid, :name) ON DUPLICATE KEY UPDATE `name`=`name`;',
                [
                    ':pvpteamid'=>$data['pvpteamid'],
                    ':name'=>$data['name'],
                ],
            ];
            #Generating list of IDs for selects to process members
            $members = [];
            #There were cases when some characters had non-numeric symbols on certain HTML pages, this is more of a precaution now
            foreach ($data['members'] as $memberid=>$member) {
                if (preg_match('/^\d{1,10}$/', strval($memberid))) {
                    $members[] = '\''.$memberid.'\'';
                }
            }
            #Set list of members for select and list of members already registered
            if (empty($members)) {
                $inmembers = '\'\'';
                $regmembers = [];
            } else {
                $inmembers = implode(',', $members);
                $regmembers = (new \SimbiatDB\Controller)->selectColumn('SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN ('.$inmembers.')');
            }
            #Actually registering/updating members
            foreach ($data['members'] as $memberid=>$member) {
                if (preg_match('/^\d{1,10}$/', strval($memberid))) {
                    if (in_array(strval($memberid), $regmembers) || (!in_array(strval($memberid), $regmembers) && $this->Update(strval($memberid), 'character') === true)) {
                        $queries[] = [
                            'INSERT INTO `'.$this->dbprefix.'pvpteam_character` (`pvpteamid`, `characterid`, `rankid`, `matches`) VALUES (:pvpteamid, :memberid, (SELECT `pvprankid` FROM `'.$this->dbprefix.'pvpteam_rank` WHERE `rank`=:rank AND `rank` IS NOT NULL LIMIT 1), :matches) ON DUPLICATE KEY UPDATE `rankid`=(SELECT `pvprankid` FROM `'.$this->dbprefix.'pvpteam_rank` WHERE `rank`=:rank AND `rank` IS NOT NULL LIMIT 1), `matches`=:matches;',
                            [
                                ':pvpteamid'=>$data['pvpteamid'],
                                ':memberid'=>$memberid,
                                ':rank'=>(empty($member['rank']) ? 'Member' : $member['rank']),
                                ':matches'=>(empty($member['feasts']) ? 0 : $member['feasts']),
                            ],
                        ];
                    }
                }
            }
            #Mass remove characters, that left Free Company
            $queries = array_merge($queries, $this->MassRemoveFromGroup($data['pvpteamid'], 'pvpteam', $inmembers));
            #Running the queries we've accumulated
            (new \SimbiatDB\Controller)->query($queries);
            #Remove cron entry (if exists)
            $this->CronRemove($data['pvpteamid'], 'pvpteam');
            return true;
        } catch(Exception $e) {
            #Update cron entry (if exists) with the error
            $this->CronError($data['pvpteamid'], 'pvpteam', $e->getTraceAsString());
            return false;
        }
    }
    
    #Update achievements with missing details
    public function UpdateAchievements(): bool
    {
        try {
            #Selection is limited to 10 achievements at once in order not to backlog Cron too much
            $achievements = (new \SimbiatDB\Controller)->selectAll(
                'SELECT `'.$this->dbprefix.'achievement`.`achievementid`, `'.$this->dbprefix.'character_achievement`.`characterid` FROM `'.$this->dbprefix.'achievement` LEFT JOIN `'.$this->dbprefix.'character_achievement` ON `'.$this->dbprefix.'character_achievement`.`achievementid` = `'.$this->dbprefix.'achievement`.`achievementid` WHERE `category` IS NULL OR `howto` IS NULL OR `dbid` IS NULL OR `updated` <= DATE_ADD(UTC_TIMESTAMP(), INTERVAL -:maxage DAY) GROUP BY `'.$this->dbprefix.'achievement`.`achievementid` LIMIT 10',
                [
                    ':maxage'=>[$this->maxage, 'int'],
                ]
                );
            foreach ($achievements as $achievement) {
                $bindings = $this->AchievementGrab($achievement['characterid'], $achievement['achievementid']);
                if (!empty($bindings)) {
                    (new \SimbiatDB\Controller)->query('INSERT INTO `'.$this->dbprefix.'achievement` SET `achievementid`=:achievementid, `name`=:name, `icon`=:icon, `points`=:points, `category`=:category, `subcategory`=:subcategory, `howto`=:howto, `title`=:title, `item`=:item, `itemicon`=:itemicon, `itemid`=:itemid, `dbid`=:dbid ON DUPLICATE KEY UPDATE `achievementid`=:achievementid, `name`=:name, `icon`=:icon, `points`=:points, `category`=:category, `subcategory`=:subcategory, `howto`=:howto, `title`=:title, `item`=:item, `itemicon`=:itemicon, `itemid`=:itemid, `dbid`=:dbid', $bindings);
                }
            }
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
       
    #Helper function to not duplicate code for removal from groups
    private function RemoveFromGroup(string $characterid, string $grouptype): array
    {
        #If previously registered in a group, add to list of previous members for it
        $queries[] = [
            'INSERT INTO `'.$this->dbprefix.$grouptype.'_x_character`(`characterid`, `'.$grouptype.'id`) SELECT `'.$this->dbprefix.$grouptype.'_character`.`characterid`, `'.$this->dbprefix.$grouptype.'_character`.`'.$grouptype.'id` FROM `'.$this->dbprefix.$grouptype.'_character` WHERE `'.$this->dbprefix.$grouptype.'_character`.`characterid`=:characterid ON DUPLICATE KEY UPDATE `'.$this->dbprefix.$grouptype.'_x_character`.`characterid`=`'.$this->dbprefix.$grouptype.'_x_character`.`characterid`;',
            [
                ':characterid'=>$characterid,
            ],
        ];
        #Remove from group
        $queries[] = [
            'DELETE FROM `'.$this->dbprefix.$grouptype.'_character` WHERE `characterid`=:characterid;',
            [
                ':characterid'=>$characterid,
            ],
        ];
        return $queries;
    }
    
    #Helper function to not duplicate code for mass removal from groups
    private function MassRemoveFromGroup(string $groupid, string $grouptype, string $xmembers): array
    {
        #If previously registered in a group, add to list of previous members for it
        $queries[] = [
            'INSERT INTO `'.$this->dbprefix.$grouptype.'_x_character` (`characterid`, `'.$grouptype.'id`) SELECT `'.$this->dbprefix.$grouptype.'_character`.`characterid`, `'.$this->dbprefix.$grouptype.'_character`.`'.$grouptype.'id` FROM `'.$this->dbprefix.$grouptype.'_character` WHERE `'.$this->dbprefix.$grouptype.'_character`.`'.$grouptype.'id`=:groupid'.($xmembers === '\'\'' ? '' : ' AND `'.$this->dbprefix.$grouptype.'_character`.`characterid` NOT IN ('.$xmembers.')').' ON DUPLICATE KEY UPDATE `'.$this->dbprefix.$grouptype.'_x_character`.`characterid`=`'.$this->dbprefix.$grouptype.'_x_character`.`characterid`;',
            [
                ':groupid'=>$groupid,
            ]
        ];
        #Remove from group
        $queries[] = [
            'DELETE FROM `'.$this->dbprefix.$grouptype.'_character` WHERE `'.$grouptype.'id`=:groupid'.($xmembers === '\'\'' ? '' : ' AND `'.$this->dbprefix.$grouptype.'_character`.`characterid` NOT IN ('.$xmembers.')').';',
            [
                ':groupid'=>$groupid,
            ]
        ];
        return $queries;
    }
    
    private function DeleteEntity(string $id, string $type): bool
    {
        $queries[] = [
            'UPDATE `'.$this->dbprefix.$type.'` SET `deleted` = UTC_DATE() WHERE `'.$type.'id` = :id',
            [':id'=>$id],
        ];
        if ($type !== 'character') {
            #Remove characters from group
            $queries = array_merge($queries, $this->MassRemoveFromGroup($id, $type, '\'\''));
            #Remove free company ranks (not ranking!)
            if ($type === 'freecompany') {
                $queries[] = [
                    'DELETE FROM `'.$this->dbprefix.'freecompany_rank` WHERE `'.$type.'id` = :id',
                    [':id'=>$id],
                ];
            }
        } else {
            #Remove character from groups
            $queries = array_merge($queries, $this->RemoveFromGroup($id, 'freecompany', '\'\''));
            $queries = array_merge($queries, $this->RemoveFromGroup($id, 'linkshell', '\'\''));
            $queries = array_merge($queries, $this->RemoveFromGroup($id, 'pvpteam', '\'\''));
        }
        $result  = (new \SimbiatDB\Controller)->query($queries);
        if ($result === true) {
            $this->CronRemove($id, $type);
        }
        return $result;
    }
}
?>