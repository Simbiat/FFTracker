<?php
#Functions used to use "scheduler" on tracker
declare(strict_types=1);
namespace Simbiat\FFTModules;

trait Cron
{
    #Function to process cron jobs for tracker for ease of access from outside
    public function CronProcess(): bool|string
    {
        try {
            #Update achievements first, since, most likely it will be quick due lack of those requiring an update
            $this->UpdateAchievements();
            #Get settings
            $this->getMaxage();
            $this->getMaxlines();
            #Do actual updates with cron taking priority
            $entities = (new \Simbiat\Database\Controller)->selectAll('(
                	SELECT `type`, `id`, `nextrun`, IF(`type`=\'character\', 2, 1) AS `priority` FROM `'.$this->dbprefix.'cron` ORDER BY `nextrun` ASC LIMIT :maxlines
                )
                UNION ALL
                (
                	SELECT `type`, `id`, `nextrun`, 0 AS `priority` FROM (
                		(SELECT \'character\' AS `type`, `characterid` AS `id`, `updated` AS `nextrun` FROM `'.$this->dbprefix.'character` WHERE `deleted` IS NULL ORDER BY `updated` ASC LIMIT :maxlines)
                		UNION ALL
                		(SELECT \'freecompany\' AS `type`, `freecompanyid` AS `id`, `updated` AS `nextrun` FROM `'.$this->dbprefix.'freecompany` WHERE `deleted` IS NULL ORDER BY `updated` ASC LIMIT :maxlines)
                		UNION ALL
                		(SELECT IF(`crossworld` = 0, \'linkshell\', \'crossworldlinkshell\') AS `type`, `linkshellid` AS `id`, `updated` AS `nextrun` FROM  `'.$this->dbprefix.'linkshell` WHERE `deleted` IS NULL ORDER BY `updated` ASC LIMIT :maxlines)
                		UNION ALL
                		(SELECT \'pvpteam\' AS `type`, `pvpteamid` AS `id`, `updated` AS `nextrun` FROM `'.$this->dbprefix.'pvpteam` WHERE `deleted` IS NULL ORDER BY `updated` ASC LIMIT :maxlines)
                	) entities WHERE `nextrun` <= DATE_ADD(UTC_TIMESTAMP(), INTERVAL -:maxage DAY) AND `id` NOT IN (SELECT `id` FROM `'.$this->dbprefix.'cron`) ORDER BY `nextrun` ASC LIMIT :maxlines
                )
                ORDER BY `nextrun` ASC, `priority` DESC LIMIT :maxlines',
                [
                    ':maxlines'=>[$this->maxlines, 'int'],
                    ':maxage'=>[$this->maxage, 'int'],
                ]
            );
            if (!empty($entities)) {
                foreach ($entities as $entity) {
                    #Updating entities. IDs are converted to string explicitly, since character and free company IDs should be returned as integers by default, but we need to use strings
                    $result = $this->Update(strval($entity['id']), $entity['type']);
                    if (!in_array($result, ['character', 'freecompany', 'linkshell', 'crossworldlinkshell', 'pvpteam'])) {
                        return $result;
                    }
                }
            }
            return true;
        } catch(Exception $e) {
            return false;
        }
    }
    
    #Add to cron
    private function CronAdd(string $id, string $type): bool
    {
        return (new \Simbiat\Database\Controller)->query('INSERT INTO `'.$this->dbprefix.'cron` (`type`, `id`) VALUES (:type, :id) ON DUPLICATE KEY UPDATE `nextrun` = UTC_TIMESTAMP();', [':type'=>$type, ':id'=>$id]);
    }
    
    #Remove from cron
    private function CronRemove(string $id, string $type): void
    {
        (new \Simbiat\Database\Controller)->query('DELETE FROM `'.$this->dbprefix.'cron` WHERE `type` = :type AND `id` = :id', [':type'=>$type, ':id'=>$id]);
    }
    
    #Log error and reshedule for 1 hour
    private function CronError(string $id, string $type, string $error): void
    {
        (new \Simbiat\Database\Controller)->query('UPDATE `'.$this->dbprefix.'cron` SET `error` = :error, `nextrun` = TIMESTAMPADD(SECOND, 3600, UTC_TIMESTAMP()) WHERE `type` = :type AND `id` = :id', [':type'=>$type, ':id'=>$id, ':error'=>$error]);
    }
}
?>