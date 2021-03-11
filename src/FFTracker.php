<?php
declare(strict_types=1);
namespace FFTracker;

class FFTracker
{
    #Allowed languages
    const langallowed = ['na', 'jp', 'ja', 'eu', 'fr', 'de'];
    
    #Use traits
    use Modules\Setters;
    use Modules\Grabber;
    use Modules\Updater;
    use Modules\Cron;
    use Modules\Crest;
    use Modules\Output;
    
    public function __construct(string $dbprefix = '', bool $nodb = false)
    {
        #We do not need DB for ImageShow() function, but it is requried for the rest
        if ($nodb === false) {
            $this->setDbPrefix($dbprefix);
            #Get settings from Database
            $this->getLanguage();
            $this->getUseragent();
            $this->getFcCrestPath();
            $this->getPvpCrestPath();
        }
    }
    
    public function Update(string $id, string $type = ''): string|bool
    {
        #Grab data first
        $data = $this->LodestoneGrab($id, $type);
        if (is_array($data)) {
            if ($data['404'] === true) {
                #Means that entity was removed from Lodestone
                #Mark as deleted in tracker
                return $this->DeleteEntity($id, $data['entitytype']);
            } else {
                #Data was retrieved, update entity
                return $this->EntityUpdate($data);
            }
        } else {
            #This means, that an error was returned
            return strval($data);
        }
    }
}
?>