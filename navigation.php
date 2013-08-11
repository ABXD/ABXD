<?php
$navigation = new PipeMenu();

if($loguser['powerlevel'] >= 3 && isAllowed("viewAdminRoom"))
	$navigation->add(new PipeMenuLinkEntry(__("Admin"), "admin", "", "", "cogs"));

$bucket = "topMenuStart"; include($libPath . "/pluginloader.php");

$navigation->add(new PipeMenuLinkEntry(Settings::get("menuMainName"), "board", "", "", "home"));
if(isAllowed("viewMembers"))
	$navigation->add(new PipeMenuLinkEntry(__("Member list"), "memberlist", "", "", "group"));
if(isAllowed("viewRanks"))
	$navigation->add(new PipeMenuLinkEntry(__("Ranks"), "ranks", "", "", "trophy"));
if(isAllowed("viewOnline"))
	$navigation->add(new PipeMenuLinkEntry(__("Online users"), "online", "", "", "eye-open"));
if(isAllowed("search"))
	$navigation->add(new PipeMenuLinkEntry(__("Search"), "search", "", "", "search"));

$navigation->add(new PipeMenuLinkEntry(__("Last posts"), "lastposts", "", "", "reorder"));

$bucket = "topMenu"; include($libPath . "/pluginloader.php");

$layout_navigation = $navigation;
?>
