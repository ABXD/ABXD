<?php

$ajaxPage = true;
$id = (int)$_GET["id"];

$qQuote = "	select
				p.id, p.deleted, pt.text,
				f.minpower,
				u.name poster
			from posts p
				left join {posts_text} pt on pt.pid = p.id and pt.revision = p.currentrevision
				left join {threads} t on t.id=p.thread
				left join {forums} f on f.id=t.forum
				left join {users} u on u.id=p.user
			where p.id={0}";
$rQuote = Query($qQuote, $id);

if(!NumRows($rQuote))
	die(__("Unknown post ID."));

$quote = Fetch($rQuote);

if($quote['minpower'] > $loguser['powerlevel'])
	die("No.");

if ($quote['deleted'])
	$quote['text'] = __("Post is deleted");

$reply = "[quote=\"".$quote['poster']."\" id=\"".$quote['id']."\"]".$quote['text']."[/quote]";
$reply = str_replace("/me ", "[b]* ".htmlspecialchars($quote['poster'])."[/b]", $reply);

echo $reply;

