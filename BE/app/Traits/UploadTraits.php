<?php
namespace App\Traits;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

trait UploadTraits
{
    public static function getConvertImage($url,$width,$height,$crop)
    {
        $new_url = substr($url, 0, strrpos($url, 'upload/') + 7) . 'w_'.$width.',h_'.$height.',c_'.$crop.'/' . substr($url, strrpos($url, 'upload/') + 7);
        return $new_url;
    }

    public function convertImage($url,$width,$height,$crop)
    {
        $new_url = substr($url, 0, strrpos($url, 'upload/') + 7) . 'w_'.$width.',h_'.$height.',c_'.$crop.'/' . substr($url, strrpos($url, 'upload/') + 7);
        return $new_url;
    }
    
}