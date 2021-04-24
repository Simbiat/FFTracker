<?php
#Functions used to manipulate crests for Free Companies and PvP Teams
declare(strict_types=1);
namespace Simbiat\FFTModules;

trait Crest
{   
    public function ImageShow(string $imgname): string
    {
        $imgname = dirname(__DIR__).'/Images/'.$imgname;
        if (!file_exists($imgname)) {
            #Use placeholder
            $imgname = dirname(__DIR__).'/Images/fftracker.png';
        }
        return $imgname;
    }
    
    #Function to merge 1 to 3 images making up a crest on Lodestone into 1 stored on tracker side
    private function CrestMerge(string $groupid, array $images): string
    {
        try {
            $imgfolder = dirname(__DIR__).'/merged-crests/';
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
            if (!file_exists($imgfolder.$groupid.'.png')) {
                #Failed to save the image
                return '';
            }
            $hash = hash_file('sha3-256', $imgfolder.$groupid.'.png');
            #Check if file with hash name exists
            if (!file_exists($imgfolder.$hash.'.png')) {
                #Copy the file to new path
                copy($imgfolder.$groupid.'.png', $imgfolder.$hash.'.png');
            }
            #Remove temporary file
            unlink($imgfolder.$groupid.'.png');
            return $hash;
        } catch (\Exception $e) {
            return '';
        }
    }
}
?>