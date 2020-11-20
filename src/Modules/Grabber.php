<?php
#Functions used to get data from Lodestone
declare(strict_types=1);
namespace FFTracker\Modules;

trait Grabber
{
    #Attempt to grab data
    private function LodestoneGrab(string $id, string $type = '')
    {
        switch ($type) {
            case 'character':
                #Check if numeric and reset type, if it's not
                if (is_numeric($id) === true) {
                    $data = $this->CharacterGrab($id);
                } else {
                    $data = $this->LodestoneGrab($id, '');
                }
                break;
            case 'freecompany':
                #Check if numeric and reset type, if it's not
                if (is_numeric($id) === true) {
                    $data = $this->CompanyGrab($id);
                } else {
                    $data = $this->LodestoneGrab($id, '');
                }
                break;
            case 'linkshell':
                #Check if numeric and reset type, if it's not
                if (is_numeric($id) === true) {
                    $data = $this->LinkshellGrab($id);
                } else {
                    $data = $this->LodestoneGrab($id, '');
                }
                break;
            case 'crossworldlinkshell':
                #Check if valid format
                if (preg_match('/[a-zA-Z0-9]{40}/mi', $id)) {
                    $data = $this->CrossLinkGrab($id);
                } else {
                    $data = $this->LodestoneGrab($id, '');
                }
                break;
            case 'pvpteam':
                #Check if valid format
                if (preg_match('/[a-zA-Z0-9]{40}/mi', $id)) {
                    $data = $this->PVPGrab($id);
                } else {
                    $data = $this->LodestoneGrab($id, '');
                }
                break;
            default:
                if (is_numeric($id)) {
                    #Try getting character
                    $data = $this->CharacterGrab($id);
                    if (!is_array($data) || $data['404'] === true) {
                        #Try getting Free Company
                        $data = $this->CompanyGrab($id);
                        if (!is_array($data) || $data['404'] === true) {
                            #Try getting Linkshell
                            $data = $this->LinkshellGrab($id);
                            if (!is_array($data) || $data['404'] === true) {
                                $data = 'Failed to find entity with ID '.$id;
                            }
                        }
                    }
                } else {
                    if (preg_match('/[a-zA-Z0-9]{40}/mi', $id)) {
                        #Try getting PvP Team
                        $data = $this->PVPGrab($id);
                        if (!is_array($data) || $data['404'] === true) {
                            #Try getting Crossworld Linkshell
                            $data = $this->CrossLinkGrab($id);
                            if (!is_array($data) || $data['404'] === true) {
                                $data = 'Failed to find entity with ID '.$id;
                            }
                        }
                    } else {
                        $data = 'Wrong ID '.$id;
                    }
                }
                break;
        }
        return $data;
    }
    
    private function CharacterGrab(string $id)
    {
        $Lodestone = (new \Lodestone\Api);
        $data = $Lodestone->setLanguage($this->language)->setUseragent($this->useragent)->getCharacter($id)->getCharacterJobs($id)->getResult();
        if (empty($data['characters'][$id]['server'])) {
            if (@$data['characters'][$id] == 404) {
                $data['entitytype'] = 'character';
                $data['404'] = true;
                return $data;
            } else {
                return 'Failed to get all necessary data for character '.$id.' ('.$Lodestone->getLastError()['url'].'): '.$Lodestone->getLastError()['error'];
            }
        }
        $data = $data['characters'][$id];
        $data['characterid'] = $id;
        $data['entitytype'] = 'character';
        $data['404'] = false;
        return $data;
    }
    
    private function CompanyGrab(string $id)
    {
        $Lodestone = (new \Lodestone\Api);
        $data = $Lodestone->setLanguage($this->language)->setUseragent($this->useragent)->getFreeCompany($id)->getFreeCompanyMembers($id, 0)->getResult();
        if (empty($data['freecompanies'][$id]['server']) || empty($data['freecompanies'][$id]['members']) || (count($data['freecompanies'][$id]['members']) - 3) < $data['freecompanies'][$id]['members_count']) {
            if (@$data['freecompanies'][$id] == 404) {
                $data['entitytype'] = 'freecompany';
                $data['404'] = true;
                return $data;
            }
            return 'Failed to get all necessary data for Free Company '.$id.' ('.$Lodestone->getLastError()['url'].'): '.$Lodestone->getLastError()['error'];
        }
        $data = $data['freecompanies'][$id];
        $data['freecompanyid'] = $id;
        $data['entitytype'] = 'freecompany';
        $data['404'] = false;
        return $data;
    }
    
    private function LinkshellGrab(string $id)
    {
        $Lodestone = (new \Lodestone\Api);
        $data = $Lodestone->setLanguage($this->language)->setUseragent($this->useragent)->getLinkshellMembers($id, 0)->getResult();
        if (empty($data['linkshells'][$id]['server']) || empty($data['linkshells'][$id]['members']) || (count($data['linkshells'][$id]['members']) - 3) < $data['linkshells'][$id]['members']['total']) {
            if (@$data['linkshells'][$id]['members'] == 404) {
                $data['entitytype'] = 'linkshell';
                $data['404'] = true;
                return $data;
            }
            return 'Failed to get all necessary data for Linkshell '.$id.' ('.$Lodestone->getLastError()['url'].'): '.$Lodestone->getLastError()['error'];
        }
        $data = $data['linkshells'][$id];
        $data['linkshellid'] = $id;
        $data['entitytype'] = 'linkshell';
        $data['404'] = false;
        return $data;
    }
    
    private function CrossLinkGrab(string $id)
    {
        $Lodestone = (new \Lodestone\Api);
        $data = $Lodestone->setLanguage($this->language)->setUseragent($this->useragent)->getLinkshellMembers($id, 0)->getResult();
        if (empty($data['linkshells'][$id]['server']) || empty($data['linkshells'][$id]['members']) || (count($data['linkshells'][$id]['members']) - 3) < $data['linkshells'][$id]['members']['total']) {
            if (@$data['linkshells'][$id]['members'] == 404) {
                $data['entitytype'] = 'crossworldlinkshell';
                $data['404'] = true;
                return $data;
            }
            return 'Failed to get all necessary data for Crossworld Linkshell '.$id.' ('.$Lodestone->getLastError()['url'].'): '.$Lodestone->getLastError()['error'];
        }
        $data = $data['linkshells'][$id];
        $data['linkshellid'] = $id;
        $data['entitytype'] = 'crossworldlinkshell';
        $data['404'] = false;
        return $data;
    }
    
    private function PVPGrab(string $id)
    {
        $Lodestone = (new \Lodestone\Api);
        $data = $Lodestone->getPvPTeam($id)->getResult();
        if (empty($data['pvpteams'][$id]['dataCenter']) || empty($data['pvpteams'][$id]['members'])) {
            if (@$data['pvpteams'][$id]['members'] == 404) {
                $data['entitytype'] = 'pvpteam';
                $data['404'] = true;
                return $data;
            }
            return 'Failed to get all necessary data for PvP Team '.$id.' ('.$Lodestone->getLastError()['url'].'): '.$Lodestone->getLastError()['error'];
        }
        $data = $data['pvpteams'][$id];
        $data['pvpteamid'] = $id;
        $data['entitytype'] = 'pvpteam';
        $data['404'] = false;
        return $data;
    }
    
    private function AchievementGrab(string $character, string $achievement): array
    {
        #Grab data
        $Lodestone = (new \Lodestone\Api)->setUseragent($this->useragent)->setLanguage($this->language);
        $data = NULL;
        $data = $Lodestone->getCharacterAchievements($character, $achievement)->getResult();
        if (empty($data['characters'][$character]['achievements'][$achievement])) {
            return [];
        }
        $data = $data['characters'][$character]['achievements'][$achievement];
        #Prepare bindings for actual update
        $bindings = [];
        $bindings[':name'] = $data['name'];
        $bindings[':icon'] = str_replace('https://img.finalfantasyxiv.com/lds/pc/global/images/itemicon/', '', $data['icon']);
        $bindings[':points'] = $data['points'];
        $bindings[':category'] = $data['category'];
        $bindings[':subcategory'] = $data['subcategory'];
        if (!empty($data['howto'])) {
            $bindings[':howto'] = $data['howto'];
        }
        if (!empty($data['title'])) {
            $bindings[':title'] = $data['title'];
        }
        if (!empty($data['item']['name'])) {
            $bindings[':item'] = $data['item']['name'];
        }
        if (!empty($data['item']['icon'])) {
            $bindings[':itemicon'] = str_replace('https://img.finalfantasyxiv.com/lds/pc/global/images/itemicon/', '', $data['item']['icon']);
        }
        if (!empty($data['item']['id'])) {
            $bindings[':itemid'] = $data['item']['id'];
        }
        return $bindings;
    }
}
?>