<?php
#Functions used to manipulate crests for Free Companies and PvP Teams
declare(strict_types=1);
namespace FFTracker\Modules;

trait Crest
{
    #Function to return either the actual crest or a placeholder if it's missing
    public function CrestShow(string $imgname): void
    {
        #Checking if we have PvP or FC based on ID format and get appropriate default path
        if (preg_match('/[a-zA-Z0-9]{40}\.png/mi', $imgname)) {
            $imgname = $this->pvpcrestpath.$imgname;
        } else {
            $imgname = $this->fccrestpath.$imgname;
        }
        #Use palceholder in case file is missing
        if (!file_exists($imgname)) {
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
    private function CrestMerge(string $groupid, array $images): bool
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
                    return false;
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
            #Saving file
            imagepng($image, $imgfolder.$groupid.'.png', 9, PNG_ALL_FILTERS);
            #Explicitely destroy image object
            imagedestroy($image);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>