<?php
declare(strict_types=1);
namespace Simbiat;

class FFTracker
{
    #Allowed languages
    const langAllowed = ['na', 'jp', 'ja', 'eu', 'fr', 'de'];
    private ?HTMLCache $HTMLCache = NULL;

    #Use traits
    use FFTModules\Setters;
    use FFTModules\Grabber;
    use FFTModules\Updater;
    use FFTModules\Crest;
    use FFTModules\Output;

    public function __construct(string $language = 'na', int $maxAge = 90, int $maxLines = 50, string $userAgent = '', string $cacheDir = '')
    {
        $this->setLanguage($language);
        $this->setUseragent($userAgent);
        $this->setMaxage($maxAge);
        $this->setMaxlines($maxLines);
        $this->getCrestPath();
        #Checking if HTML Cache is used
        if (method_exists('\Simbiat\HTMLCache','delete')) {
            if (empty($cacheDir)) {
                $this->HTMLCache = (new HTMLCache());
            } else {
                $this->HTMLCache = (new HTMLCache($cacheDir));
            }
        }
    }

    public function Update(string $id, string $type = '', string $charId = ''): string|bool
    {
        #Grab data first
        $data = $this->LodestoneGrab($id, $type, $charId);
        if (is_array($data)) {
            if (isset($data['404']) && $data['404'] === true) {
                #Means that entity was removed from Lodestone
                #Mark as deleted in tracker
                $result = $this->DeleteEntity($id, $data['entitytype']);
                if ($result === true) {
                    #Clean cache
                    if ($this->HTMLCache !== NULL) {
                        $this->HTMLCache->delete('fftracker/'.$data['entitytype'].'/'.$id);
                    }
                }
                return $result;
            } else {
                #Data was retrieved, update entity
                $result = $this->EntityUpdate($data);
                if ($result === true) {
                    #Clean cache
                    if ($this->HTMLCache !== NULL) {
                        $this->HTMLCache->delete('fftracker/'.$data['entitytype'].'/'.$id);
                    }
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
