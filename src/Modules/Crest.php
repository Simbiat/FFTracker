<?php
#Functions used to manipulate crests for Free Companies and PvP Teams
declare(strict_types=1);
namespace FFTracker\Modules;

trait Crest
{
    #Function to return either the actual crest or a placeholder if it's missing
    public function CrestShow(): void
    {
        if (file_exists($this->dir.$_SERVER['REQUEST_URI'])) {
            $imgname = $this->dir.$_SERVER['REQUEST_URI'];
        } else {
            $imgname = dirname(dirname(__FILE__)).'/images/fftracker/fftracker.png';
        }
        $fp = fopen($imgname, 'rb');
        header("Content-Type: image/png");
        header("Content-Length: " . filesize($imgname));
        fpassthru($fp);
    }
    
    #Function to show map with marker of Free Company's estate or a placeholder if no estate is registered
    public function EstateMarkerShow(): void
    {
        
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
            $dimensions = getimagesize($images[0]);
            foreach ($images as $key=>$image) {
                $layers[$key] = @imagecreatefrompng($image);
                if (empty($layers[$key])) {
                    #This means that we failed to get the image thus final crest will either fail or be corrupt, thus exiting early
                    return false;
                }
            }
            #Create image object
            $image = imagecreatetruecolor($dimensions[0], $dimensions[1]);
            #Set transparency
            imagealphablending($image, true);
            imagesavealpha($image, true);
            imagecolortransparent($image, imagecolorallocatealpha($image, 255, 0, 0, 127));
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 0, 0, 127));
            #Copy each Lodestone image onto the image object
            for ($i = 0; $i < count($layers); $i++) {
                imagecopy($image, $layers[$i], 0, 0, 0, 0, $dimensions[0], $dimensions[1]);
                #Destroy layer to free some memory
                imagedestroy($layers[$i]);
            }
            #Saving file
            imagepng($image, $imgfolder.$groupid.".png", 9, PNG_ALL_FILTERS);
            #Explicitely destroy image object
            imagedestroy($image);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>