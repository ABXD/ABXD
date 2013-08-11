<?php

include_once($installationPath . '/plugins/custombb/defines.php');

if (file_exists(BB_FILE)) {
	$bbcodes = unserialize(file_get_contents(BB_FILE));
}
else {
	$bbcodes = array();
}

foreach ($bbcodes as $bbcode) {
	$bbcodeCallbacks[$bbcode['name']] = array(new BBCodeCallback($bbcode), 'callback');
}
