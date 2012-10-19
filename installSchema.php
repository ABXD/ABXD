<?php

$genericInt = "int(11) NOT NULL DEFAULT '0'";
$smallerInt = "int(8) NOT NULL DEFAULT '0'";
$bool = "tinyint(1) NOT NULL DEFAULT '0'";
$notNull = " NOT NULL DEFAULT ''";
$text = "text DEFAULT ''"; //NOT NULL breaks in certain versions/settings.
$var128 = "varchar(128)".$notNull;
$var256 = "varchar(256)".$notNull;
$var1024 = "varchar(1024)".$notNull;
$AI = "int(11) NOT NULL AUTO_INCREMENT";
$keyID = "primary key (`id`)";

$tables = array
(
	"badges" => array
	(
		"fields" => array
		(
			"owner" => $genericInt,
			"name" => $var256,
			"color" => $smallerInt,
		),
		"special" => "unique key `steenkinbadger` (`owner`,`name`)"
	),
	"settings" => array
	(
		"fields" => array
		(
			"plugin" => $var128,
			"name" => $var128,
			"value" => $text,
		),
		"special" => "unique key `mainkey` (`plugin`,`name`)"
	),
	"blockedlayouts" => array
	(
		"fields" => array
		(
			"user" => $genericInt,
			"blockee" => $genericInt,
		),
		"special" => "key `user` (`user`)"
	),
	"categories" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"name" => $var256,
			"corder" => $smallerInt,
		),
		"special" => $keyID
	),
	"enabledplugins" => array
	(
		"fields" => array
		(
			"plugin" => $var256,
		),
		"special" => "key `plugin` (`plugin`)"
	),
	"forummods" => array
	(
		"fields" => array
		(
			"forum" => $genericInt,
			"user" => $genericInt,			
		),
	),
	"forums" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"title" => $var256,
			"description" => $text,
			"catid" => $smallerInt,
			"minpower" => $smallerInt,
			"minpowerthread" => $smallerInt,
			"minpowerreply" => $smallerInt,
			"numthreads" => $genericInt,
			"numposts" => $genericInt,
			"lastpostdate" => $genericInt,
			"lastpostuser" => $genericInt,
			"lastpostid" => $genericInt,
			"hidden" => $bool,
			"forder" => $smallerInt,
		),
		"special" => $keyID.", key `catid` (`catid`)"
	),
	"guests" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"ip" => "varchar(32)".$notNull,
			"date" => $genericInt,
			"lasturl" => "varchar(100)".$notNull,
			"lastforum" => $genericInt,
			"useragent" => "varchar(100)".$notNull,			
			"bot" => $bool,
		),
		"special" => $keyID
	),
	"ignoredforums" => array
	(
		"fields" => array
		(
			"uid" => $genericInt,
			"fid" => $genericInt,			
		),
	),
	"ip2c" => array
	(
		"fields" => array
		(
			"ip_from" => "bigint(12) NOT NULL DEFAULT '0'",
			"ip_to" => "bigint(12) NOT NULL DEFAULT '0'",
			"cc" => "varchar(2) DEFAULT ''",			
		),
	),
	"ipbans" => array
	(
		"fields" => array
		(
			"ip" => "varchar(16)".$notNull,
			"reason" => "varchar(100)".$notNull,			
			"date" => $genericInt,			
		),
		"special" => "unique key `ip` (`ip`)"
	),
	"misc" => array
	(
		"fields" => array
		(
			"version" => $genericInt,
			"views" => $genericInt,
			"hotcount" => $genericInt,			
			"maxusers" => $genericInt,
			"maxusersdate" => $genericInt,
			"maxuserstext" => $text,
			"maxpostsday" => $genericInt,
			"maxpostsdaydate" => $genericInt,
			"maxpostshour" => $genericInt,
			"maxpostshourdate" => $genericInt,
			"milestone" => $text,
			"porabox" => $text,
			"poratitle" => "varchar(100)".$notNull,
		),
	),
	"moodavatars" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"uid" => $genericInt,			
			"mid" => $genericInt,			
			"name" => $var256,
		),
		"special" => $keyID
	),
	"pmsgs" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"userto" => $genericInt,
			"userfrom" => $genericInt,
			"date" => $genericInt,
			"ip" => "varchar(16)".$notNull,
			"msgread" => $bool,
			"deleted" => "tinyint(4) NOT NULL DEFAULT '0'",
			"drafting" => $bool,
		),
		"special" => $keyID.", key `userto` (`userto`), key `userfrom` (`userfrom`), key `msgread` (`msgread`)"
	),
	"pmsgs_text" => array
	(
		"fields" => array
		(
			"pid" => $genericInt,
			"title" => $var256,
			"text" => $text,
		),
		"special" => "primary key (`pid`)"
	),
	"poll" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"question" => $var256,
			"briefing" => $text,
			"closed" => $bool,
			"doublevote" => $bool,
		),
		"special" => $keyID
	),
	"pollvotes" => array
	(
		"fields" => array
		(
			"poll" => $genericInt,
			"choice" => $genericInt,
			"user" => $genericInt,
		),
	),
	"poll_choices" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"poll" => $genericInt,
			"choice" => $var256,
			"color" => "varchar(25)".$notNull,
		),
		"special" => $keyID
	),
	"posts" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"thread" => $genericInt,
			"user" => $genericInt,
			"date" => $genericInt,
			"ip" => "varchar(16)".$notNull,
			"num" => $genericInt,
			"deleted" => $bool,
			"deletedby" => $genericInt,
			"reason" => "varchar(300)".$notNull,
			"options" => "tinyint(4) NOT NULL DEFAULT '0'",
			"mood" => $genericInt,
			"currentrevision" => $genericInt,
		),
		"special" => $keyID.", key `thread` (`thread`), key `date` (`date`), key `user` (`user`), key `ip` (`ip`)"
	),
	"posts_text" => array
	(
		"fields" => array
		(
			"pid" => $genericInt,
			"text" => $text,
			"revision" => $genericInt,
			"user" => $genericInt,
			"date" => $genericInt,
		),
		"special" => "fulltext key `text` (`text`)"
	),
	"proxybans" => array
	(
		"fields" => array
		(
			"id" => $AI,			
			"ip" => "varchar(16)".$notNull,
		),
		"special" => $keyID
	),
	"queryerrors" => array
	(
		"fields" => array
		(
			"id" => $AI,		
			"user" => $genericInt,	
			"ip" => "varchar(16)".$notNull,
			"time" => $genericInt,	
			"query" => $text,
			"get" => $text,
			"post" => $text,
			"cookie" => $text,
			"error" => $text
		),
		"special" => $keyID
	),
	"ranks" => array
	(
		"fields" => array
		(
			"rset" => $genericInt,
			"num" => $genericInt,
			"text" => $var256,
		),
		"special" => "key `count` (`num`)"
	),
	"ranksets" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"name" => "varchar(50)".$notNull,
		),
		"special" => $keyID
	),
	"reports" => array
	(
		"fields" => array
		(
			"ip" => "varchar(15)".$notNull,
			"user" => $genericInt,
			"time" => $genericInt,
			"text" => "varchar(1024)".$notNull,
			"hidden" => $bool,
			"severity" => "tinyint(2) NOT NULL DEFAULT '0'",
			"request" => $text,
		),
	),
	"sessions" => array
	(
		"fields" => array
		(
			"id" => $var256,
			"user" => $genericInt,
			"expiration" => $genericInt,
			"autoexpire" => $bool,
			"iplock" => $bool,
			"iplockaddr" => $var128,
			"lastip" => $var128,
			"lasturl" => $var128,
			"lasttime" => $genericInt,
		),
		"special" => $keyID.", key `user` (`user`), key `expiration` (`expiration`)"
	),
	"smilies" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"code" => "varchar(8)".$notNull,
			"image" => "varchar(32)".$notNull,
		),
		"special" => $keyID
	),
	"threads" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"forum" => $genericInt,
			"user" => $genericInt,
			"views" => $genericInt,
			"title" => "varchar(100)".$notNull,
			"icon" => "varchar(200)".$notNull,
			"replies" => $genericInt,
			"lastpostdate" => $genericInt,
			"lastposter" => $genericInt,
			"lastpostid" => $genericInt,
			"closed" => $bool,
			"sticky" => $bool,
			"poll" => $genericInt,
		),
		"special" => $keyID.", key `forum` (`forum`), key `user` (`user`), key `sticky` (`sticky`), key `pollid` (`poll`), key `lastpostdate` (`lastpostdate`), fulltext key `title` (`title`)"
	),
	"threadsread" => array
	(
		"fields" => array
		(
			"id" => $genericInt,
			"thread" => $genericInt,
			"date" => $genericInt,
		),
		"special" => "primary key (`id`, `thread`)"
	),
	"usercomments" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"uid" => $genericInt,
			"cid" => $genericInt,
			"text" => $text,
			"date" => $genericInt,
		),
		"special" => $keyID
	),
	"users" => array
	(
		"fields" => array
		(
			"id" => $AI,
			"name" => "varchar(32)".$notNull,
			"displayname" => "varchar(32)".$notNull,
			"password" => $var256,
			"pss" => "varchar(16)".$notNull,
			"powerlevel" => $smallerInt,
			"posts" => $genericInt,
			"regdate" => $genericInt,
			"minipic" => $var128,
			"picture" => $var128,
			"title" => $var256,
			"postheader" => $text,
			"signature" => $text,
			"bio" => $text,
			"sex" => "tinyint(2) NOT NULL DEFAULT '2'",
			"rankset" => $smallerInt,
			"realname" => "varchar(60)".$notNull,
			"lastknownbrowser" => $text,
			"location" => $var128,
			"birthday" => $genericInt,
			"email" => "varchar(60)".$notNull,
			"homepageurl" => "varchar(80)".$notNull,
			"homepagename" => "varchar(100)".$notNull,			
			"lastposttime" => $genericInt,
			"lastactivity" => $genericInt,
			"lastip" => "varchar(16)".$notNull,
			"lasturl" => $var128,
			"lastforum" => $genericInt,
			"postsperpage" => "int(8) NOT NULL DEFAULT '20'",
			"threadsperpage" => "int(8) NOT NULL DEFAULT '50'",
			"timezone" => "float NOT NULL DEFAULT '0'",
			"theme" => "varchar(64)".$notNull,
			"signsep" => $bool,
			"dateformat" => "varchar(20) NOT NULL DEFAULT 'm-d-y'",
			"timeformat" => "varchar(20) NOT NULL DEFAULT 'h:i a'",
			"fontsize" => "int(8) NOT NULL DEFAULT '80'",
			"karma" => "int(11) NOT NULL DEFAULT '100'",
			"blocklayouts" => $bool,
			"globalblock" => $bool,
			"usebanners" => "tinyint(1) NOT NULL DEFAULT '1'",
			"showemail" => $bool,
			"newcomments" => $bool,
			"tempbantime" => $genericInt,
			"tempbanpl" => $smallerInt,
			"forbiddens" => $var1024,
			"pluginsettings" => $text,
			"lostkey" => $var128,
			"lostkeytimer" => $genericInt,
			"loggedin" => $bool,
		),
		"special" => $keyID.", key `posts` (`posts`), key `name` (`name`), key `lastforum` (`lastforum`), key `lastposttime` (`lastposttime`), key `lastactivity` (`lastactivity`)"
	),
	"uservotes" => array
	(
		"fields" => array
		(
			"uid" => $genericInt,
			"voter" => $genericInt,
			"up" => $bool,
		),
		"special" => "primary key (`uid`, `voter`), key `uid` (`uid`)"
	),
	"usergroups" => array(
		"fields" => array
		(
			"id" => $genericInt,
			"title" => $var256,
			"inherits" => $genericInt,
			"permissions" => $text
		)
	),
	"userpermissions" => array(
		"fields" => array
		(
			"uid" => $genericInt,
			"permissions" => $text
		)
	),
	"notifications" => array(
		"fields" => array(
			"id" => $AI,
			"uid" => $genericInt,
			"type" => $var256,
			"title" => $var256,
			"description" => $text,
			"link" => $bool,
			"linklocation" => $var256,
			"time" => $genericInt
		),
		"special" => $keyID
	)
);
?>
