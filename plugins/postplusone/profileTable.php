<?php

$val = $user["postplusones"];
if($user["postplusones"])
	$val .= " [".actionLinkTag("View...", "listplusones", $user["id"])."]";
	
$profileParts[__("General information")][__("Total +1s received")] = $val;
$profileParts[__("General information")][__("Total +1s given")] = $user["postplusonesgiven"];
