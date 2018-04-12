<?php

namespace image\orientation;

use Imagine\Gd\Imagine as ImagineGD;
use Imagine\Imagick\Imagine as ImagineImagick;
use Imagine\Image\Metadata\ExifMetadataReader;
use Imagine\Filter\Basic\Autorotate;

$user = get_user(get_input('guid'));
if (!$user instanceof \ElggUser) {
    register_error(elgg_echo('image_orientation:rotate:invalid:guid'));
    forward(REFERER);
}

if (!$user->canEdit()) {
    register_error(elgg_echo('image_orientation:rotate:invalid:guid'));
    forward(REFERER);
}

$degrees = (int) get_input('rotate', 0);

if (!in_array($degrees, [90, 180, 270])) {
    register_error(elgg_echo('image_orientation:rotate:invalid:rotation'));
    forward(REFERER);
}

$filehandler = new \ElggFile();
$filehandler->owner_guid = $user->guid;
$filehandler->setFilename("profile/{$user->guid}master.jpg");

$file_location = $filehandler->getFilenameOnFilestore();

$imginfo = getimagesize($file_location);
$requiredMemory1 = ceil($imginfo[0] * $imginfo[1] * 5.35);
$requiredMemory2 = ceil($imginfo[0] * $imginfo[1] * ($imginfo['bits'] / 8) * $imginfo['channels'] * 2.5);
$requiredMemory = (int)max($requiredMemory1, $requiredMemory2);

$mem_avail = elgg_get_ini_setting_in_bytes('memory_limit');
$mem_used = memory_get_usage();
	
$mem_avail = $mem_avail - $mem_used - 2097152; // 2 MB buffer
	
if ($requiredMemory > $mem_avail) {
	// we don't have enough memory for any manipulation
    register_error(elgg_echo('image_orientation:toolarge'));
    forward(REFERER);
}

$name = uniqid() . pathinfo($file_location, PATHINFO_BASENAME);
$tmp_location = elgg_get_config('dataroot') . 'image_orientation/' . $name;

if (!copy($file_location, $tmp_location) || !file_exists($tmp_location)) {
    register_error(elgg_echo('image_orientation:error:avatarrotation'));
    forward(REFERER);
}

try {

    if (extension_loaded('imagick')) {
        $imagine = new ImagineImagick();
    } else {
        $imagine = new ImagineGD();
    }
    $imagine->open($tmp_location)->rotate($degrees)->save($tmp_location, ['jpeg_quality' => 100, 'png_compression_level' => 9]);

    copy($tmp_location, $file_location);
    unlink($tmp_location);

    // regenerate thumbnails
    unset($user->x1);
    unset($user->x2);
    unset($user->y1);
    unset($user->y2);

    $icon_sizes = elgg_get_config('icon_sizes');
    unset($icon_sizes['master']);

    foreach ($icon_sizes as $name => $size_info) {
        $resized = get_resized_image_from_existing_file($filehandler->getFilenameOnFilestore(), $size_info['w'], $size_info['h'], $size_info['square'], 0, 0, 0, 0, $size_info['upscale']);
        if (!$resized) {
            unset($owner->icontime);
            unset($owner->x1);
            unset($owner->x2);
            unset($owner->y1);
            unset($owner->y2);
    
            foreach ($files as $file) {
                $file->delete();
            }
            register_error(elgg_echo('avatar:resize:fail'));
            forward(REFERER);
        }
    
        $file = new \ElggFile();
        $file->owner_guid = $user->guid;
        $file->setFilename("profile/{$user->guid}{$name}.jpg");
        $file->open('write');
        $file->write($resized);
        $file->close();
    }

    $user->icontime = time();
    
} catch (Imagine\Exception\Exception $exc) {
    // fail silently, we don't need to rotate it bad enough to kill the script
    error_log($exc->getMessage());
    register_error(elgg_echo('image_orientation:error:avatarrotation'));
    forward(REFERER);
}

system_message('Image rotated');
forward(REFERER);