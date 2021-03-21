<?php
declare(strict_types=1);
namespace Simbiat;

class FFTracker
{
    #Allowed languages
    const langallowed = ['na', 'jp', 'ja', 'eu', 'fr', 'de'];
    
    #Use traits
    use FFTModules\Setters;
    use FFTModules\Grabber;
    use FFTModules\Updater;
    use FFTModules\Cron;
    use FFTModules\Crest;
    use FFTModules\Output;
    
    public function __construct(string $dbprefix = '', string $language = 'na', int $maxAge = 90, int $maxLines = 50, string $userAgent = '')
    {
        $this->setDbPrefix($dbprefix);
        $this->setLanguage($language);
        $this->setUseragent($userAgent);
        $this->setMaxage($maxAge);
        $this->setMaxlines($maxLines);
        $this->getCrestPath();
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