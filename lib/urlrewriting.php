<?php

function actionLink($action, $id="", $args="", $urlname="")
{
	global $boardroot, $mainPage, $urlNameCache, $libPath;

	if($urlname == "_")
		$urlname = "";
	else if(isset($urlNameCache[$action."_".$id]) && $urlname == "")
		$urlname = $urlNameCache[$action."_".$id];

	$bucket = "linkMangler"; include($libPath . '/pluginloader.php');

	$res = $boardroot;
	if($action != $mainPage)
		$res .= "$action/";

	if($id != "")
	{
		$res .= $id;
		if($urlname)
			$res .= "-".urlNamify($urlname);
		$res .= "/";
	}
	if($args)
		$res .= "?$args";

	if(strpos($res, "&amp"))
	{
		debug_print_backtrace();
		Kill("Found &amp;amp; in link");
	}

	return $res;

//Possible URL Rewriting :D
//	return "$boardroot/$action/$id?$args";
}

//Find out if the current URL is valid.
$valid = true;

if($_GET["rewritten"] == 0 && isset($_GET["page"]))
	$valid = false;

//Find out the correct name.
$name = "";
$page = $_GET["page"];
$name = "";

if($_GET["id"])
{
	if($page === "") $page == $mainPage;
	if($page == "profile" || $page == "listthreads" || $page === "listposts")
		$name = FetchResult("SELECT name FROM {users} WHERE id={0} LIMIT 1", (int)$_GET["id"]);
	if($page == "thread" || $page == "editthread" || $page === "newreply")
		$name = FetchResult("SELECT title FROM {threads} WHERE id={0} LIMIT 1", (int)$_GET["id"]);
	if($page == "forum" || $page == "newthread")
		$name = FetchResult("SELECT title FROM {forums} WHERE id={0} LIMIT 1", (int)$_GET["id"]);

	$name = urlNamify($name);
	if($name != $_GET["rewriteurlname"])
		$valid = false;
}

// If URL is not valid, we have to redirect to the correct one!
// Only if NOT POST request, though!
if(strtoupper($_SERVER['REQUEST_METHOD']) != 'POST' && !$valid)
{
	$params = "";
	foreach($_GET as $key => $val)
	{
		if($key == "rewriteurlname" ||
		   $key == "rewritten" ||
		   $key == "page" ||
		   $key == "id") continue;
		if($params != "") $params .= "&";
		$params .= urlencode($key)."=".urlencode($val);
	}
	$newUrl = actionLink($page, $_GET["id"], $params, $name);
	header("HTTP/1.1 301 Moved Permanently");
	header("Status: 301 Moved Permanently");
	die(header("Location: ".$newUrl));
}

if(isset($_GET["rewriteurlname"]) && $_GET["rewriteurlname"] != "")
	setUrlName($_GET["page"], $_GET["id"], $_GET["rewriteurlname"]);




















