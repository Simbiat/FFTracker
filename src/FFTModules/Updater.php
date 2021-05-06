<?php
#Functions used to get update data on tracker
declare(strict_types=1);
namespace Simbiat\FFTModules;

trait Updater
{
    #Update data
    private function EntityUpdate(array $data): string|bool
    {
        return match(@$data['entitytype']) {
            'character' => $this->CharacterUpdate($data),
            'freecompany' => $this->CompanyUpdate($data),
            'linkshell' => $this->LinkshellUpdate($data),
            'crossworldlinkshell' => $this->CrossLinkUpdate($data),
            'pvpteam' => $this->PVPUpdate($data),
            'achievement' => $this->AchievementUpdate($data),
            default => false,
        };
    }
    
    private function CharacterUpdate(array $data): string|bool
    {
        try {
            #Flags to schedule Free Company or PvPTeam updates
            $fccron=false;
            $pvpcron=false;
            #Main query to insert or update a character
            $queries[] = [
                'INSERT INTO `'.$this->dbprefix.'character`(
                    `characterid`, `serverid`, `name`, `registered`, `updated`, `deleted`, `biography`, `titleid`, `avatar`, `clanid`, `genderid`, `namedayid`, `guardianid`, `cityid`, `gcrankid`
                )
                VALUES (
                    :characterid, (SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `server`=:server), :name, UTC_DATE(), UTC_TIMESTAMP(), NULL, :biography, (SELECT `achievementid` as `titleid` FROM `'.$this->dbprefix.'achievement` WHERE `title` IS NOT NULL AND `title`=:title LIMIT 1), :avatar, (SELECT `clanid` FROM `'.$this->dbprefix.'clan` WHERE `clan`=:clan), :genderid, (SELECT `namedayid` FROM `'.$this->dbprefix.'nameday` WHERE `nameday`=:nameday), (SELECT `guardianid` FROM `'.$this->dbprefix.'guardian` WHERE `guardian`=:guardian), (SELECT `cityid` FROM `'.$this->dbprefix.'city` WHERE `city`=:city), `gcrankid` = (SELECT `gcrankid` FROM `'.$this->dbprefix.'grandcompany_rank` WHERE `gc_rank` IS NOT NULL AND `gc_rank`=:gcrank ORDER BY `gcrankid` ASC LIMIT 1)
                )
                ON DUPLICATE KEY UPDATE
                    `serverid`=(SELECT `serverid` FROM `'.$this->dbprefix.'server` WHERE `server`=:server), `name`=:name, `updated`=UTC_TIMESTAMP(), `deleted`=NULL, `biography`=:biography, `titleid`=(SELECT `achievementid` as `titleid` FROM `'.$this->dbprefix.'achievement` WHERE `title` IS NOT NULL AND `title`=:title LIMIT 1), `avatar`=:avatar, `clanid`=(SELECT `clanid` FROM `'.$this->dbprefix.'clan` WHERE `clan`=:clan), `genderid`=:genderid, `namedayid`=(SELECT `namedayid` FROM `'.$this->dbprefix.'nameday` WHERE `nameday`=:nameday), `guardianid`=(SELECT `guardianid` FROM `'.$this->dbprefix.'guardian` WHERE `guardian`=:guardian), `cityid`=(SELECT `cityid` FROM `'.$this->dbprefix.'city` WHERE `city`=:city), `gcrankid`=(SELECT `gcrankid` FROM `'.$this->dbprefix.'grandcompany_rank` WHERE `gc_rank` IS NOT NULL AND `gc_rank`=:gcrank ORDER BY `gcrankid` ASC LIMIT 1);',
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
                ],
            ];
            #Add levels
            if (!empty($data['jobs'])) {
                foreach ($data['jobs'] as $job=>$level) {
                    #Insert job (we lose performance a tiny bit, but this allows to automatically add new jobs and avoid failures on next step)
                    $query[] = [
                        'INSERT IGNORE INTO `'.$this->dbprefix.'job` (`name`) VALUES (:job);',
                        [
                            ':job' => [$job, 'string'],
                        ]
                    ];
                    #Insert actual level
                    $query[] = [
                        'INSERT INTO `'.$this->dbprefix.'character_jobs`(`characterid`, `jobid`, `level`) VALUES (:characterid, (SELECT `jobid` FROM `'.$this->dbprefix.'job` WHERE `name`=:job) AS `jobid`, :level) ON DUPLICATE KEY UPDATE `level`=:level;',
                        [
                            ':job' => [$job, 'string'],
                            ':level' => [(empty($level) ? 0 : intval($level)), 'int'],
                        ],
                    ];
                }
            }
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
            if (!empty($data['clan'])) {
                $queries[] = [
                    'INSERT INTO `'.$this->dbprefix.'character_clans`(`characterid`, `genderid`, `clanid`) VALUES (:characterid, :genderid, (SELECT `clanid` FROM `'.$this->dbprefix.'clan` WHERE `clan`=:clan)) ON DUPLICATE KEY UPDATE `clanid`=`clanid`;',
                    [
                        ':characterid'=>$data['characterid'],
                        ':genderid'=>($data['gender']==='male' ? '1' : '0'),
                        ':clan'=>$data['clan'],
                    ],
                ];
            }
            #Check if present in Free Company
            if (empty($data['freeCompany']['id'])) {
                $queries = array_merge($queries, $this->RemoveFromGroup($data['characterid'], 'freecompany'));
            } else {
                #Check if not already registered in this Free Company
                if (!(new \Simbiat\Database\Controller)->check('SELECT `characterid` FROM `'.$this->dbprefix.'freecompany_character` WHERE `characterid`=:characterid AND `freecompanyid`=:freecompanyid', [':characterid'=>$data['characterid'],':freecompanyid'=>$data['freeCompany']['id']]) === true) {
                    #Remove character from other companies
                    $queries = array_merge($queries, $this->RemoveFromGroup($data['characterid'], 'freecompany'));
                    #Add to company (without rank) if the company is already registered. Needed to prevent grabbing data for the character again during company update. If company is not registered yet - nothing will happen
                    $query[] = [
                        'INSERT INTO `'.$this->dbprefix.'freecompany_character`(`characterid`, `freecompanyid`) SELECT (SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid`=:characterid) AS `characterid`, (SELECT `freecompanyid` FROM `'.$this->dbprefix.'freecompany` WHERE `freecompanyid`=:freecompanyid) AS `freecompanyid` FROM DUAL HAVING `freecompanyid` IS NOT NULL;',
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
                if (!(new \Simbiat\Database\Controller)->check('SELECT `characterid` FROM `'.$this->dbprefix.'pvpteam_character` WHERE `characterid`=:characterid AND `pvpteamid`=:pvpteamid', [':characterid'=>$data['characterid'],':pvpteamid'=>$data['pvp']['id']])) {
                    #Remove character from other teams
                    $queries = array_merge($queries, $this->RemoveFromGroup($data['characterid'], 'pvpteam'));
                    #Add to team (without rank) if the team is already registered. Needed to prevent grabbing data for the character again during team update. If team is not registered yet - nothing will happen
                    $query[] = [
                        'INSERT INTO `'.$this->dbprefix.'pvpteam_character`(`characterid`, `pvpteamid`) SELECT (SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid`=:characterid) AS `characterid`, (SELECT `pvpteamid` FROM `'.$this->dbprefix.'pvpteam` WHERE `pvpteamid`=:pvpteamid) AS `pvpteamid` FROM DUAL HAVING `pvpteamid` IS NOT NULL;',
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
            (new \Simbiat\Database\Controller)->query($queries);
            #Register Free Company update if change was detected
            if ($fccron === true || $pvpcron === true) {
                #Cache CRON object
                $cron = (new \Simbiat\Cron);
            }
            if ($fccron) {
                #If we have triggered this from within Free Company update, it will simply update the next run time which should not affect anything
                $cron->add('ffentityupdate', ['freecompany', $data['freeCompany']['id']], message: 'Updating free company with ID '.$data['freeCompany']['id'], priority: 1);
            }
            #Register PvP Team update if change was detected
            if ($pvpcron) {
                #If we have triggered this from within PvP Team update, it will simply update the next run time which should not affect anything
                $cron->add('ffentityupdate', ['pvpteam', $data['pvp']['id']], message: 'Updating PvP team with ID '.$data['pvp']['id'], priority: 1);
            }
            return true;
        } catch(\Exception $e) {
            return $e->getMessage()."\r\n".$e->getTraceAsString();
        }
    }
    
    private function CompanyUpdate(array $data): string|bool
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
                    'INSERT INTO `'.$this->dbprefix.'freecompany_ranking` (`freecompanyid`, `date`, `weekly`, `monthly`, `members`) SELECT * FROM (SELECT :freecompanyid AS `freecompanyid`, UTC_DATE() AS `date`, :weekly AS `weekly`, :monthly AS `monthly`, :members AS `members` FROM DUAL WHERE :freecompanyid NOT IN (SELECT `freecompanyid` FROM (SELECT * FROM `'.$this->dbprefix.'freecompany_ranking` WHERE `freecompanyid`=:freecompanyid ORDER BY `date` DESC LIMIT 1) `lastrecord` WHERE `weekly`=:weekly AND `monthly`=:monthly) LIMIT 1) `actualinsert` ON DUPLICATE KEY UPDATE `weekly`=:weekly, `monthly`=:monthly, `members`=:members;',
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
                $regmembers = (new \Simbiat\Database\Controller)->selectColumn('SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN ('.$inmembers.')');
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
            (new \Simbiat\Database\Controller)->query($queries);
            return true;
        } catch(\Exception $e) {
            return $e->getMessage()."\r\n".$e->getTraceAsString();
        }
    }
    
    private function LinkshellUpdate(array $data): string|bool
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
                $regmembers = (new \Simbiat\Database\Controller)->selectColumn('SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN ('.$inmembers.')');
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
            (new \Simbiat\Database\Controller)->query($queries);
            return true;
        } catch(\Exception $e) {
            return $e->getMessage()."\r\n".$e->getTraceAsString();
        }
    }
    
    private function CrossLinkUpdate(array $data): string|bool
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
                $regmembers = (new \Simbiat\Database\Controller)->selectColumn('SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN ('.$inmembers.')');
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
            (new \Simbiat\Database\Controller)->query($queries);
            return true;
        } catch(\Exception $e) {
            return $e->getMessage()."\r\n".$e->getTraceAsString();
        }
    }
    
    private function PVPUpdate(array $data): string|bool
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
                $regmembers = (new \Simbiat\Database\Controller)->selectColumn('SELECT `characterid` FROM `'.$this->dbprefix.'character` WHERE `characterid` IN ('.$inmembers.')');
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
            (new \Simbiat\Database\Controller)->query($queries);
            return true;
        } catch(\Exception $e) {
            return $e->getMessage()."\r\n".$e->getTraceAsString();
        }
    }
    
    #Update statistics
    public function UpdateStatistics(): bool|string
    {
        try {
            foreach (['genetics', 'astrology', 'characters', 'freecompanies', 'cities', 'grandcompanies', 'servers', 'achievements', 'timelines', 'other'] as $type) {
                $this->Statistics($type, '', true);
            }
            return true;
        } catch(\Exception $e) {
            return $e->getMessage()."\r\n".$e->getTraceAsString();
        }
    }
    
    public function AchievementUpdate(array $data): bool|string
    {
        try {
            #Unset entitytype
            unset($data['entitytype']);
            return (new \Simbiat\Database\Controller)->query('INSERT INTO `'.$this->dbprefix.'achievement` SET `achievementid`=:achievementid, `name`=:name, `icon`=:icon, `points`=:points, `category`=:category, `subcategory`=:subcategory, `howto`=:howto, `title`=:title, `item`=:item, `itemicon`=:itemicon, `itemid`=:itemid, `dbid`=:dbid ON DUPLICATE KEY UPDATE `achievementid`=:achievementid, `name`=:name, `icon`=:icon, `points`=:points, `category`=:category, `subcategory`=:subcategory, `howto`=:howto, `title`=:title, `item`=:item, `itemicon`=:itemicon, `itemid`=:itemid, `dbid`=:dbid, `updated`=UTC_TIMESTAMP()', $data);
        } catch(\Exception $e) {
            return $e->getMessage()."\r\n".$e->getTraceAsString();
        }
    }
    
    #Function to update old entities
    public function UpdateOld(int $limit = 1): bool|string
    {
        #Sanitize entities number
        if ($limit < 1) {
            $limit = 1;
        }
        try {
            $dbcon = (new \Simbiat\Database\Controller);
            $entities = $dbcon->selectAll('
                    SELECT `type`, `id`, `charid` FROM (
                        SELECT * FROM (
                            SELECT \'character\' AS `type`, `characterid` AS `id`, \'\' AS `charid`, `updated`, `deleted` FROM `'.$this->dbprefix.'character`
                            UNION ALL
                            SELECT \'freecompany\' AS `type`, `freecompanyid` AS `id`, \'\' AS `charid`, `updated`, `deleted` FROM `'.$this->dbprefix.'freecompany`
                            UNION ALL
                            SELECT \'pvpteam\' AS `type`, `pvpteamid` AS `id`, \'\' AS `charid`, `updated`, `deleted` FROM `'.$this->dbprefix.'pvpteam`
                            UNION ALL
                            SELECT IF(`crossworld` = 0, \'linkshell\', \'crossworldlinkshell\') AS `type`, `linkshellid`, \'\' AS `charid`, `updated`, `deleted` AS `id` FROM `'.$this->dbprefix.'linkshell`
                            WHERE `deleted` IS NULL
                        ) `nonach`
                        UNION ALL
                        SELECT \'achievement\' AS `type`, `'.$this->dbprefix.'achievement`.`achievementid` AS `id`, (SELECT `characterid` FROM `'.$this->dbprefix.'character_achievement` WHERE `'.$this->dbprefix.'character_achievement`.`achievementid` = `'.$this->dbprefix.'achievement`.`achievementid` LIMIT 1) AS `charid`, `updated`, NULL AS `deleted` FROM `'.$this->dbprefix.'achievement` HAVING `charid` IS NOT NULL
                    ) `allentities`
                    ORDER BY `updated` ASC LIMIT :maxlines',
                [
                    ':maxlines'=>[$limit, 'int'],
                ]
            );
            foreach ($entities as $entity) {
                $result = $this->Update($entity['type'], strval($entity['id']), $entity['charid']);
                if (!in_array($result, ['character', 'freecompany', 'linkshell', 'crossworldlinkshell', 'pvpteam', 'achievement'])) {
                    return $result;
                }
            }
            return true;
        } catch(\Exception $e) {
            return $e->getMessage()."\r\n".$e->getTraceAsString();
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
        $result  = (new \Simbiat\Database\Controller)->query($queries);
        return $result;
    }
}
?>