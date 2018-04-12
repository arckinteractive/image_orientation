<?php

namespace image\orientation;

use Imagine\Gd\Imagine as ImagineGD;
use Imagine\Imagick\Imagine as ImagineImagick;
use Imagine\Image\Metadata\ExifMetadataReader;
use Imagine\Filter\Basic\Autorotate;

require_once __DIR__ . '/lib/hooks.php';

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\init');

// newer elgg provides this by default
// only include if we can't find it
if (!class_exists('Imagine\\Gd\\Imagine') && file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
}

function init() {
	elgg_register_action('image_orientation/avatar_rotate', __DIR__ . '/actions/avatar/rotate.php');

	elgg_extend_view('input/file', 'input/image_orientation');

	elgg_extend_view('core/avatar/upload', 'image_orientation/avatar_rotate');

	elgg_register_plugin_hook_handler('action', 'all', __NAMESPACE__ . '\\actions_hook', 0);
}


function fix_orientation($source, $name) {

	$allowed_extensions = array(
		"gif",
		"jpeg",
		"jpg",
		"png",
		"wbmp",
		"xbm"
	);
	
	$pathinfo = pathinfo($name);
	if (!in_array(strtolower($pathinfo['extension']), $allowed_extensions)) {
		// not in the capabilities of Imagine
		return false;
	}

	$imginfo = getimagesize($source);
	$requiredMemory1 = ceil($imginfo[0] * $imginfo[1] * 5.35);
	$requiredMemory2 = ceil($imginfo[0] * $imginfo[1] * ($imginfo['bits'] / 8) * $imginfo['channels'] * 2.5);
	$requiredMemory = (int)max($requiredMemory1, $requiredMemory2);

	$mem_avail = elgg_get_ini_setting_in_bytes('memory_limit');
	$mem_used = memory_get_usage();
	
	$mem_avail = $mem_avail - $mem_used - 2097152; // 2 MB buffer
	
	if ($requiredMemory > $mem_avail) {
		// we don't have enough memory for any manipulation
		// @TODO - we should only throw an error if the image needs rotating...
		//register_error(elgg_echo('image_orientation:toolarge'));
		return false;
	}

	$name = uniqid() . $name;
	$tmp_location = elgg_get_config('dataroot') . 'image_orientation/' . $name;
	
	//@note - need to copy to a tmp_location as
	// imagine doesn't like images with no file extension
	//@note - need to copy to a tmp_location as
	// imagine doesn't like images with no file extension
	if (!copy($source, $tmp_location)) {
		return false;
	}
	
	if (!file_exists($tmp_location)) {
		// the copy failed
		// full disk or something?
		return false;
	}
	
	try {

		if (extension_loaded('imagick')) {
			$imagine = new ImagineImagick();
		} else {
			$imagine = new ImagineGD();
		}
		$imagine->setMetadataReader(new ExifMetadataReader());

		$autorotate = new Autorotate();
	
		$autorotate->apply($imagine->open($tmp_location))->save($tmp_location);
		copy($tmp_location, $source);
		unlink($tmp_location);
		
		return true;
	} catch (Imagine\Exception\Exception $exc) {
		// fail silently, we don't need to rotate it bad enough to kill the script
		
		unlink($tmp_location);
		return false;
	}
}
