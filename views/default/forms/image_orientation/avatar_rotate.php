<?php

echo '<div>' . elgg_view('input/dropdown', [
    'name' => 'rotate',
    'value' => 90,
    'options_values' => [
        90 => elgg_echo('image_orientation:rotate:option:90'),
        180 => elgg_echo('image_orientation:rotate:option:180'),
        270 => elgg_echo('image_orientation:rotate:option:270'),
    ]
]) . '</div>';

echo elgg_view('input/hidden', ['name' => 'guid', 'value' => $vars['entity']->guid]);

echo elgg_view('input/submit', ['value' => elgg_echo('image_orientation:rotate'), 'class' => 'mtm']);