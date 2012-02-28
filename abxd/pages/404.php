<?php
//  AcmlmBoard XD - 404
//  Access: all

// Some servers use one response, some use other. For safety, use both.
header('HTTP/1.1 404 Not Found');
header('Status: 404 Not Found');

$title = __('404');

Kill(__('404. Not found.'));

?>
