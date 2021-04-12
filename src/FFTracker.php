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
    
    public function Update(string $type = '', string $id, string $charid = ''): string|bool
    {
        #Grab data first
        $data = $this->LodestoneGrab($id, $type, $charid);
        if (is_array($data)) {
            if (isset($data['404']) && $data['404'] === true) {
                #Means that entity was removed from Lodestone
                #Mark as deleted in tracker
                return $this->DeleteEntity($id, $data['entitytype']);
            } else {
                #Data was retrieved, update entity
                $result = $this->EntityUpdate($data);
                if ($result === true) {
                    return $data['entitytype'];
                } else {
                    return $result;
                }
            }
        } else {
            #This means, that an error was returned
            return strval($data);
        }
    }
}
?>