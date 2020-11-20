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
    
    public function __construct(string $dbprefix = '')
    {
        $this->setDbPrefix($dbprefix);
        #Get settings from Database
        $this->getLanguage();
        $this->getUseragent();
        $this->getFcCrestPath();
        $this->getPvpCrestPath();
    }
    
    public function Update(string $id, string $type = ''): bool
    {
        #Grab data first
        $data = $this->LodestoneGrab($id, $type);
        if (is_array($data)) {
            if ($data['404'] === true) {
                #Means that entity was removed from Lodestone
                #Mark as deleted in tracker
                $this->DeleteEntity($id, $data['entitytype']);
            } else {
                #Data was retrieved, update entity
                return $this->EntityUpdate($data);
            }
        } else {
            if (is_string($data)) {
                #This means, that an error was returned
                
            }
        }
    }
}
?>