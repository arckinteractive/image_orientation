<?php

$inputs = [];

$inputs[] = [
    'label' => elgg_echo('image_orientation:setting:rotate'),
    'input' => elgg_view('input/dropdown', [
        'name' => 'params[enable_avatar_rotate]',
        'value' => $vars['entity']->enable_avatar_rotate,
        'options_values' => [
            'no' => elgg_echo('option:no'),
            'yes' => elgg_echo('option:yes')
        ]
    ])
];

foreach ($inputs as $input) {
    echo '<div class="pas">';
    echo '<div class="label">' . $input['label'] . '</div>';
    echo $input['input'];
    echo '</div>';
}