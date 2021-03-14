<?php
#Functions used to set settings
declare(strict_types=1);
namespace Simbiat\FFTModules;

trait Setters
{    
    #Settings required for Lodestone library
    protected string $useragent = '';
    protected string $language = 'na';
    protected string $dbprefix = 'ff__';
    protected int $maxage = 90;
    protected int $maxlines = 50;
    protected string $fccrestpath = '';
    protected string $pvpcrestpath = '';
    
    #############
    #Setters
    #############
    public function setUseragent(string $useragent = ''): self
    {
        $this->useragent = $useragent;
        return $this;
    }
    
    public function setMaxage(int $maxage = 90): self
    {
        $this->maxage = $maxage;
        return $this;
    }
    
    public function setMaxlines(int $maxage = 50): self
    {
        $this->maxlines = $maxlines;
        return $this;
    }
    
    public function setLanguage(string $language = 'en'): self
    {
        #En is used only for user convinience, in reality it uses NA (North America)
        if ($language === 'en') {
            $language = 'na';
        }
        if (!in_array($language, self::langallowed)) {
            $language = 'na';
        }
        if (in_array($language, ['jp', 'ja'])) {$language = 'jp';}
        $this->language = $language;
        return $this;
    }
    
    public function setDbPrefix(string $dbprefix = 'ff__'): self
    {
        if (empty($dbprefix)) {
            $dbprefix = 'ff__';
        }
        $this->dbprefix = $dbprefix;
        return $this;
    }
    
    public function setFcCrestPath(string $fccrestpath = ''): self
    {
        if ($fccrestpath === '') {
            $fccrestpath = dirname(dirname(__FILE__)).'/Images/merged-crests';
        }
        $this->fccrestpath = preg_replace('/(.*[^\\\\\/]{1,})([\\\\\/]{1,}$)/m', '$1', $fccrestpath).'/';
        return $this;
    }
    
    public function setPvpCrestPath(string $pvpcrestpath = ''): self
    {
        if ($pvpcrestpath === '') {
            $pvpcrestpath = dirname(dirname(__FILE__)).'/Images/merged-crests';
        }
        $this->pvpcrestpath = preg_replace('/(.*[^\\\\\/]{1,})([\\\\\/]{1,}$)/m', '$1', $pvpcrestpath).'/';
        return $this;
    }
    
    #############
    #Getters
    #############
    public function getUseragent(): string
    {
        $this->useragent = strval((new \Simbiat\Database\Controller)->selectValue('SELECT `value` FROM `'.$this->dbprefix.'settings` WHERE `setting`=\'useragent\''));
        return $this->useragent;
    }
    
    public function getMaxlines(): int
    {
        $this->maxlines = intval((new \Simbiat\Database\Controller)->selectValue('SELECT `value` FROM `'.$this->dbprefix.'settings` WHERE `setting`=\'maxlines\''));
        return $this->maxlines;
    }
    
    public function getMaxage(): int
    {
        $this->maxage = intval((new \Simbiat\Database\Controller)->selectValue('SELECT `value` FROM `'.$this->dbprefix.'settings` WHERE `setting`=\'maxage\''));
        return $this->maxage;
    }
    
    public function getLanguage(): string
    {
        $language = (new \Simbiat\Database\Controller)->selectValue('SELECT `value` FROM `'.$this->dbprefix.'settings` WHERE `setting`=\'language\'');
        if (!in_array($language, self::langallowed)) {
            $language = 'na';
        }
        if (in_array($language, ['jp', 'ja'])) {$language = 'jp';}
        $this->language = $language;
        return $this->language;
    }
    
    public function getDbPrefix(): string
    {
        return $this->dbprefix;
    }
    
    public function getFcCrestPath(): string
    {
        $fccrestpath = strval((new \Simbiat\Database\Controller)->selectValue('SELECT `value` FROM `'.$this->dbprefix.'settings` WHERE `setting`=\'freecompanycrestpath\''));
        if ($fccrestpath === '') {
            $fccrestpath = dirname(dirname(__FILE__)).'/Images/merged-crests';
        }
        $this->fccrestpath = preg_replace('/(.*[^\\\\\/]{1,})([\\\\\/]{1,}$)/m', '$1', $fccrestpath).'/';
        return $this->fccrestpath;
    }
    
    public function getPvpCrestPath(): string
    {
        $pvpcrestpath = strval((new \Simbiat\Database\Controller)->selectValue('SELECT `value` FROM `'.$this->dbprefix.'settings` WHERE `setting`=\'pvpteamcrestpath\''));
        if ($pvpcrestpath === '') {
            $pvpcrestpath = dirname(dirname(__FILE__)).'/Images/merged-crests';
        }
        $this->pvpcrestpath = preg_replace('/(.*[^\\\\\/]{1,})([\\\\\/]{1,}$)/m', '$1', $pvpcrestpath).'/';
        return $this->pvpcrestpath;
    }
}
?>