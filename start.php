<?php

namespace image\orientation;
use Imagine\Gd\Imagine;
use Imagine\Image\Metadata\ExifMetadataReader;
use Imagine\Filter\Basic\Autorotate;

require_once __DIR__ . '/lib/hooks.php';

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\init');

function init() {
	elgg_register_library('imagine', __DIR__ . '/vendor/autoload.php');
	
	elgg_extend_view('input/file', 'input/image_orientation');
	elgg_register_plugin_hook_handler('action', 'all', __NAMESPACE__ . '\\actions_hook', 0);
}


function fix_orientation($source, $name) {
	
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

	elgg_load_library('imagine');
	$name = uniqid() . $name;
	$tmp_location = elgg_get_config('dataroot') . 'image_orientation/' . $name;
	
	//@note - need to copy to a tmp_location as
	// imagine doesn't like images with no file extension
	copy($source, $tmp_location);
	
	try {

		$imagine = new Imagine();
		$imagine->setMetadataReader(new ExifMetadataReader());

		$autorotate = new Autorotate();
	
		$autorotate->apply($imagine->open($tmp_location))->save($tmp_location);
		copy($tmp_location, $source);
		unlink($tmp_location);
		
		return true;
	} catch (Imagine\Exception\Exception $exc) {
		// fail silently, we don't need to rotate it bad enough to kill the script
		return false;
	}	
}