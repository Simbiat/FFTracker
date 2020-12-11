<?php
#Functions used to manipulate crests for Free Companies and PvP Teams
declare(strict_types=1);
namespace FFTracker\Modules;

trait Crest
{
    #Function to return either the actual crest or a placeholder if it's missing
    public function CrestShow(string $imgname): void
    {
        #Check if format is correct
        if (preg_match('/[a-zA-Z0-9]{1,40}\.png/mi', $imgname)) {
            #Checking if we have PvP or FC based on ID format and get appropriate default path
            if (preg_match('/[a-zA-Z0-9]{40}\.png/mi', $imgname)) {
                $imgname = $this->pvpcrestpath.$imgname;
                $type = 'pvpteam';
            } else {
                $imgname = $this->fccrestpath.$imgname;
                $type = 'freecompany';
            }
            #Check if file exists
            if (!file_exists($imgname)) {
                #Get crest name from database
                $crest = strval((new \SimbiatDB\Controller)->selectValue(
                    'SELECT `crest` FROM `'.$this->dbprefix.$type.'` WHERE `'.$type.'id`=:id',
                    [':id'=>basename($imgname, '.png')]
                ));
                if (empty($crest) || !file_exists($this->pvpcrestpath.$crest.'.png')) {
                    $imgname = dirname(dirname(__FILE__)).'/Images/fftracker.png';
                } else {
                    $imgname = $this->pvpcrestpath.$crest.'.png';
                }
            }
        } else {
            #Use placeholder
            $imgname = dirname(dirname(__FILE__)).'/Images/fftracker.png';
        }
        #Pass the file through to browser
        $fp = fopen($imgname, 'rb');
        header('Cache-Control: public, max-age=15552000');
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($imgname));
        fpassthru($fp);
        #Ensure we exit
        exit;
    }
    
    public function ImageShow(string $imgname): void
    {
        $imgname = dirname(dirname(__FILE__)).'/Images/'.$imgname;
        if (!file_exists($imgname)) {
            #Use placeholder
            $imgname = dirname(dirname(__FILE__)).'/Images/fftracker.png';
        }
        #Pass the file through to browser
        $fp = fopen($imgname, 'rb');
        header('Cache-Control: public, max-age=15552000');
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($imgname));
        fpassthru($fp);
        #Ensure we exit
        exit;
    }
    
    #Function to merge 1 to 3 images making up a crest on Lodestone into 1 stored on tracker side
    private function CrestMerge(string $groupid, array $images): string
    {
        try {
            #Checking if we have PvP or FC based on ID format and determining the path to save the file to
            if (preg_match('/[a-zA-Z0-9]{40}/mi', $groupid)) {
                $imgfolder = $this->pvpcrestpath;
            } else {
                $imgfolder = $this->fccrestpath;
            }
            #Checking if directory exists
            if (!file_exists($imgfolder)) {
                #Creating directory
                mkdir($imgfolder, 0777, true);
            }
            #Preparing set of layers, since Lodestone stores crests as 3 (or less) separate images
            $layers = array();
            foreach ($images as $key=>$image) {
                $layers[$key] = @imagecreatefrompng($image);
                if (empty($layers[$key])) {
                    #This means that we failed to get the image thus final crest will either fail or be corrupt, thus exiting early
                    return '';
                }
            }
            #Create image object
            $image = imagecreatetruecolor(128, 128);
            #Set transparency
            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagecolortransparent($image, imagecolorallocatealpha($image, 255, 0, 0, 127));
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 0, 0, 127));
            #Copy each Lodestone image onto the image object
            for ($i = 0; $i < count($layers); $i++) {
                imagecopy($image, $layers[$i], 0, 0, 0, 0, 128, 128);
                #Destroy layer to free some memory
                imagedestroy($layers[$i]);
            }
            #Saving temporary file
            imagepng($image, $imgfolder.$groupid.'.png', 9, PNG_ALL_FILTERS);
            #Explicitely destroy image object
            imagedestroy($image);
            #Get hash of the file
            $hash = hash_file('sha3-256', $imgfolder.$groupid.'.png');
            #Check if file with hash name exists
            if (!file_exists($imgfolder.$hash.'.png')) {
                #Copy the file to new path
                copy($imgfolder.$groupid.'.png', $imgfolder.$hash.'.png');
            }
            #Remove temporary file
            unlink($imgfolder.$groupid.'.png');
            return $hash;
        } catch (Exception $e) {
            return '';
        }
    }
}
?>