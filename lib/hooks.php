<?php

namespace image\orientation;

function actions_hook($h, $t, $r, $p) {
	$inputs = get_input('image_orientation_names', false);
	
	if (!$inputs) {
		return $r;
	}
	
	if (!is_array($inputs)) {
		$inputs = array($inputs);
	}
	
	foreach ($inputs as $name) {
		if ($_FILES[$name]['tmpname']) {
			if (is_array($_FILES[$name]['tmpname'])) {
				foreach ($_FILES[$name]['tmpname'] as $key => $tmpname) {
					if (!substr_count($_FILES[$name]['type'][$key],'image/') || $_FILES[$name]['error'][$key]) {
						continue;
					}
					
					fix_orientation($_FILES[$name]['tmpname'][$key]);
				}
			}
			else {
				if (!substr_count($_FILES[$name]['type'],'image/') || $_FILES[$name]['error']) {
					continue;
				}
				
				fix_orientation($_FILES[$name]['tmpname']);
			}
		}
	}
	
	return $r;
}
