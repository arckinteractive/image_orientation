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
	
	$inputs = array_unique($inputs);

	foreach ($inputs as $name) {
		if ($_FILES[$name]['tmp_name']) {
			if (is_array($_FILES[$name]['tmp_name'])) {
				foreach ($_FILES[$name]['tmp_name'] as $key => $tmpname) {
					if (!substr_count($_FILES[$name]['type'][$key],'image/') || $_FILES[$name]['error'][$key]) {
						continue;
					}
					
					fix_orientation($_FILES[$name]['tmp_name'][$key], $_FILES[$name]['name']);
				}
			}
			else {
				if (!substr_count($_FILES[$name]['type'],'image/') || $_FILES[$name]['error']) {
					continue;
				}

				fix_orientation($_FILES[$name]['tmp_name'], $_FILES[$name]['name']);
			}
		}
	}

	return $r;
}
