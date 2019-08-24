<?php
//  AcmlmBoard XD - 404
//  Access: all

// Some servers use one response, some use another. For safety, use both.
header('HTTP/1.1 404 Not Found');
header('Status: 404 Not Found');

$title = __("404 - Not found");

Kill(__('The page you are looking for was not found.').'<br /><br />
	<a href=".'actionLink($mainPage)'.">'.__('Return to the board index').'</a>', __("404 - Not found"));
?>
