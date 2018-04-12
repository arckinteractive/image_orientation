<?php

$user = elgg_extract('entity', $vars, false);
if (!$user instanceof \ElggEntity) {
    return;
}

if (!$user->canEdit()) {
    return;
}

if (method_exists($user, 'hasIcon')) {
    if (!$user->hasIcon('master')) {
        return;
    }
}
else {
    if (!$user->icontime) {
        return;
    }
}

if (elgg_get_plugin_setting('enable_avatar_rotate', 'image_orientation') !== 'yes') {
    return;
}

$rotate = elgg_view_form('image_orientation/avatar_rotate', [], [
    'entity' => $user
]);

echo '<div class="hidden">';
echo elgg_view_module('aside', elgg_echo('image_orientation:rotate:label'), $rotate, array(
	'class' => 'avatar-rotate-module pvs',
));
echo "</div>";

?>
<script>
    require(['jquery'], function($) {
        $('.avatar-rotate-module').insertAfter('.avatar-upload-image-block > .elgg-image > .elgg-module');
    });
</script>