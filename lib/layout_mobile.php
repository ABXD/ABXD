<?php

function makeForumListing($parent)
{
	global $loguserid, $loguser, $libPath;

	$pl = $loguser['powerlevel'];
	if ($pl < 0) $pl = 0;

	$lastCatID = -1;
	$rFora = Query("	SELECT f.*,
							c.name cname,
							".($loguserid ? "(NOT ISNULL(i.fid))" : "0")." ignored,
							(SELECT COUNT(*) FROM {threads} t".($loguserid ? " LEFT JOIN {threadsread} tr ON tr.thread=t.id AND tr.id={0}" : "")."
								WHERE t.forum=f.id AND t.lastpostdate>".($loguserid ? "IFNULL(tr.date,0)" : time()-900).") numnew,
							lu.(_userfields)
						FROM {forums} f
							LEFT JOIN {categories} c ON c.id=f.catid
							".($loguserid ? "LEFT JOIN {ignoredforums} i ON i.fid=f.id AND i.uid={0}" : "")."
							LEFT JOIN {users} lu ON lu.id=f.lastpostuser
						WHERE ".forumAccessControlSQL().' AND '.($parent==0 ? 'f.catid>0' : 'f.catid={1}').(($pl < 1) ? " AND f.hidden=0" : '')."
						ORDER BY c.corder, c.id, f.forder, f.id",
						$loguserid, -$parent);
	if (!NumRows($rFora))
		return;

	$rSubfora = Query("	SELECT f.*,
							".($loguserid ? "(NOT ISNULL(i.fid))" : "0")." ignored,
							(SELECT COUNT(*) FROM {threads} t".($loguserid ? " LEFT JOIN {threadsread} tr ON tr.thread=t.id AND tr.id={0}" : "")."
								WHERE t.forum=f.id AND t.lastpostdate>".($loguserid ? "IFNULL(tr.date,0)" : time()-900).") numnew
						FROM {forums} f
							".($loguserid ? "LEFT JOIN {ignoredforums} i ON i.fid=f.id AND i.uid={0}" : "")."
						WHERE ".forumAccessControlSQL().' AND '.($parent==0 ? 'f.catid<0' : 'f.catid!={1}').(($pl < 1) ? " AND f.hidden=0" : '')."
						ORDER BY f.forder, f.id",
						$loguserid, -$parent);
	$subfora = array();
	while ($sf = Fetch($rSubfora))
		$subfora[-$sf['catid']][] = $sf;

	$theList = "";
	$firstCat = true;
	while($forum = Fetch($rFora))
	{
		$skipThisOne = false;
		$bucket = "forumListMangler"; include($libPath . "/pluginloader.php");
		if($skipThisOne)
			continue;

		if($firstCat || $forum['catid'] != $lastCatID)
		{
			$lastCatID = $forum['catid'];
			$firstCat = false;

			$theList .= format(
"
		".($firstCat ? '':'</tbody></table>')."
	<table class=\"outline margin\">
		<tbody>
			<tr class=\"header1\">
				<th>{0}</th>
				<th style=\"min-width:150px; width:15%;\">".__("Last post")."</th>
			</tr>
		</tbody>
		<tbody>
", ($parent==0)?$forum['cname']:'Subforums');
		}

		$newstuff = 0;
		$NewIcon = "";
		$subforaList = '';

		$newstuff = $forum['ignored'] ? 0 : $forum['numnew'];
		$ignoreClass = $forum['ignored'] ? " class=\"ignored\"" : "";

		if ($newstuff > 0)
			$NewIcon = "<img src=\"".resourceLink("img/status/new.png")."\" alt=\"New!\"/>";

		if (isset($subfora[$forum['id']]))
		{
			foreach ($subfora[$forum['id']] as $subforum)
			{
				$link = actionLinkTag($subforum['title'], 'forum', $subforum['id']);

				if ($subforum['ignored'])
					$link = '<span class="ignored">'.$link.'</span>';
				else if ($subforum['numnew'] > 0)
					$link = '<img src="'.resourceLink('img/status/new.png').'" alt="New!"/> '.$link;

				$subforaList .= $link.', ';
			}
		}

		if($subforaList)
			$subforaList = "<br />".__("Subforums:")." ".substr($subforaList,0,-2);

		if($forum['lastpostdate'])
		{
			$user = getDataPrefix($forum, "lu_");

			$lastLink = "";
			if($forum['lastpostid'])
				$lastLink = actionLinkTag("&raquo;", "post", $forum['lastpostid']);
			$lastLink = format("<span class=\"nom\">{0}<br />".__("by")." </span>{1} {2}", formatdate($forum['lastpostdate']), UserLink($user), $lastLink);
		}
		else
			$lastLink = "----";


		$theList .=
"
		<tr class=\"cell1\">
			<td>
				<h4 $ignoreClass>".
					$NewIcon.' '.actionLinkTag($forum['title'], "forum",  $forum['id']) . "
				</h4>
				<span $ignoreClass class=\"nom\" style=\"font-size:80%;\">
					{$forum['description']}
					$subforaList
				</span>
			</td>
			<td class=\"cell0 smallFonts center\">
				$lastLink
			</td>
		</tr>";
	}

	write(
"
		{0}
	</tbody>
</table>
",	$theList);
}


function listThread($thread, $cellClass, $dostickies = true, $showforum = false)
{
	global $haveStickies, $loguserid, $loguser, $misc;

	$forumList = "";

	$starter = getDataPrefix($thread, "su_");
	$last = getDataPrefix($thread, "lu_");


	$NewIcon = "";
	$newstuff = 0;
	if($thread['closed'])
		$NewIcon = "off";
	if($thread['replies'] >= $misc['hotcount'])
		$NewIcon .= "hot";
	if((!$loguserid && $thread['lastpostdate'] > time() - 900) ||
		($loguserid && $thread['lastpostdate'] > $thread['readdate']) &&
		!$isIgnored)
	{
		$NewIcon .= "new";
		$newstuff++;
	}
	else if(!$thread['closed'] && !$thread['sticky'] && Settings::get("oldThreadThreshold") > 0 && $thread['lastpostdate'] < time() - (2592000 * Settings::get("oldThreadThreshold")))
		$NewIcon = "old";

	if($NewIcon)
		$NewIcon = "<img src=\"".resourceLink("img/status/".$NewIcon.".png")."\" alt=\"\"/>";

	if($thread['sticky'] == 0 && $haveStickies == 1 && $dostickies)
	{
		$haveStickies = 2;
		$forumList .= "<tr class=\"header1\"><th colspan=\"".($showforum?'8':'7')."\" style=\"height: 6px;\"></th></tr>";
	}
	if($thread['sticky'] && $haveStickies == 0) $haveStickies = 1;

	$poll = ($thread['poll'] ? "<img src=\"".resourceLink("img/poll.png")."\" alt=\"Poll\"/> " : "");


	$n = 4;
	$total = $thread['replies'];

	$ppp = $loguser['postsperpage'];
	if(!$ppp) $ppp = 20;

	$numpages = floor($total / $ppp);
	$pl = "";
	if($numpages <= $n * 2)
	{
		for($i = 1; $i <= $numpages; $i++)
			$pl .= " ".actionLinkTag($i+1, "thread", $thread['id'], "from=".($i * $ppp));
	}
	else
	{
		for($i = 1; $i < $n; $i++)
		$pl .= " ".actionLinkTag($i+1, "thread", $thread['id'], "from=".($i * $ppp));
		$pl .= " &hellip; ";
		for($i = $numpages - $n + 1; $i <= $numpages; $i++)
			$pl .= " ".actionLinkTag($i+1, "thread", $thread['id'], "from=".($i * $ppp));
	}
	if($pl)
		$pl = " <span class=\"smallFonts\">[".
			actionLinkTag(1, "thread", $thread['id']). $pl . "]</span>";

	$lastLink = "";
	if($thread['lastpostid'])
		$lastLink = " ".actionLinkTag("&raquo;", "post", $thread['lastpostid']);

	$threadlink = makeThreadLink($thread);

	$forumcell = "";
	if($showforum)
		$forumcell = " in ".actionLinkTag(htmlspecialchars($thread["f_title"]), "forum", $thread["f_id"]);

	$forumList .= "
	<tr class=\"cell$cellClass\">
		<td>
			$NewIcon
			$poll
			$threadlink $pl<br>
			<small>by ".UserLink($starter).$forumcell." -- ".Plural($thread['replies'], 'reply')."</small>
		</td>
		<td class=\"smallFonts center\">
			".formatdate($thread['lastpostdate'])."<br />
			".__("by")." ".UserLink($last)." {$lastLink}</td>
	</tr>";

	return $forumList;
}


// this post box is totally not Acmlmboard but it fits much better in small resolutions

function makePost($post, $type, $params=array())
{
	global $loguser, $loguserid, $theme, $hacks, $isBot, $blocklayouts, $postText, $sideBarStuff, $sideBarData, $salt, $dataDir, $dataUrl, $libPath;

	$sideBarStuff = "";
	$poster = getDataPrefix($post, "u_");

	if(isset($_GET['pid']))
		$highlight = (int)$_GET['pid'];

	if($post['deleted'] && $type == POST_NORMAL)
	{
		$links = new PipeMenu();

		if(CanMod($loguserid,$params['fid']))
		{
			if (IsAllowed("editPost", $post['id']))
				$links->add(new PipeMenuLinkEntry(__("Undelete"), "editpost", $post['id'], "delete=2&key=".$loguser['token']));
			$links->add(new PipeMenuHtmlEntry("<a href=\"#\" onclick=\"replacePost(".$post['id'].",true); return false;\">".__("View")."</a>"));
		}

		$links->add(new PipeMenuTextEntry('#'.$post['id']));
		write(
"
		<table class=\"outline margin\" id=\"post{0}\">
			<tr class=\"cell0\">
				<td class=\"right\">
					<div style=\"float:left\">
						{1} - <small>deleted</small>
					</div>
					<small>{2}</small>
				</td>
			</tr>
		</table>
",	$post['id'], userLink($poster), $links->build()
);
		return;
	}

	$links = new PipeMenu();
	$links->setClass("toolbarMenu");

	if ($type == POST_SAMPLE)
		$meta = $params['metatext'] ? $params['metatext'] : __("Sample post");
	else
	{
		$forum = $params['fid'];
		$thread = $params['tid'];
		$canmod = CanMod($loguserid, $forum);
		$replyallowed = IsAllowed("makeReply", $thread);
		$editallowed = IsAllowed("editPost", $post['id']);
		$canreply = $replyallowed && ($canmod || (!$post['closed'] && $loguser['powerlevel'] > -1));

		if (!$isBot)
		{
			if ($type == POST_DELETED_SNOOP)
			{
				$links->add(new PipeMenuTextEntry(__("Post deleted.")));
				if ($editallowed)
					$links->add(new PipeMenuLinkEntry(__("Undelete"), "editpost", $post['id'], "delete=2&key=".$loguser['token']));
				$links->add(new PipeMenuHtmlEntry("<a href=\"#\" onclick=\"replacePost(".$post['id'].",false); return false;\">".__("Close")."</a>"));
				$links->add(new PipeMenuHtmlEntry(format(__("ID: {0}"), $post['id'])));
			}
			else if ($type == POST_NORMAL)
			{
				$links->add(new PipeMenuLinkEntry(__("Link"), "thread", "", "pid=".$post['id']."#".$post['id'], 'link'));

				if ($canreply && !$params['noreplylinks'])
					$links->add(new PipeMenuLinkEntry(__("Quote"), "newreply", $thread, "quote=".$post['id'], 'quote-left'));

				if ($editallowed && ($canmod || ($poster['id'] == $loguserid && $loguser['powerlevel'] > -1 && !$post['closed'])))
				{
					$links->add(new PipeMenuLinkEntry(__("Edit"), "editpost", $post['id'], '', 'edit'));
					$link = actionLink('editpost', $post['id'], 'delete=1&key='.$loguser['token']);
					$onclick = $canmod ? " onclick=\"deletePost(this);return false;\"" : ' onclick="if(!confirm(\'Really delete this post?\'))return false;"';
					$links->add(new PipeMenuHtmlEntry("<a href=\"{$link}\"{$onclick}><i class=\"icon-remove\">&nbsp;</i></a>"));
				}

				$bucket = "topbar"; include($libPath . "/pluginloader.php");
			}
		}

		$meta = formatdate($post['date']);

		//Threadlinks for listpost.php
		if ($params['threadlink'])
		{
			$thread = array();
			$thread["id"] = $post["thread"];
			$thread["title"] = $post["threadname"];

			$meta .= " ".__("in")." ".makeThreadLink($thread);
		}
	}

	// OTHER STUFF

	if ($post['mood'] > 0) {
		if (file_exists("${dataDir}avatars/".$poster['id']."_".$post['mood'])) {
			$picture = "<img src=\"${dataUrl}avatars/".$poster['id']."_".$post['mood']."\" alt=\"\" />";
		}
	} else {
		if ($poster["picture"] == "#INTERNAL#") {
			$picture = "<img src=\"${dataUrl}avatars/".$poster['id']."\" alt=\"\" />";
		} else if($poster["picture"]) {
			$picture = "<img src=\"".htmlspecialchars($poster["picture"])."\" alt=\"\" />";
		} else {
			$picture = "&nbsp;";
		}
	}

	if($type == POST_NORMAL) {
		$anchor = "<a name=\"".$post['id']."\"></a>";
	}

	$highlightClass = "";
	if($post['id'] == $highlight)
		$highlightClass = "highlightedPost";

	$postText = makePostText($post);

	//PRINT THE POST!

	$links = $links->build(2);

//	if($links)
//		$links = "<div style=\"text-align:right\"><small>$links</small></div>";
/*	echo "
		{$anchor}
		<table class=\"outline margin $highlightClass\" id=\"post${post['id']}\">
			<tr class=\"cell0\">
				<td>
					".UserLink($poster)." -
					<small><span id=\"meta_${post['id']}\">
						$meta
					</span>
					<span style=\"text-align:left; display: none;\" id=\"dyna_${post['id']}\">
						Hi.
					</span></small>
				</td>
			</tr>
			<tr class=\"cell1\">
				<td class=\"mobile_post\" id=\"post_${post['id']}\">
					$postText
					$links
				</td>
			</tr>
		</table>";*/

	echo "
		{$anchor}
		<table class=\"outline margin mobile_postBox\">
			<tr class=\"header0 mobile_postHeader\">
				<th>
					<div class=\"mobile_userAvatarBox\">
						$picture
					</div>
				</th>
				<th class=\"mobile_postInfoCell\" style=\"width: 99%; overflow: hidden;\">
					<div style=\"position: relative; height: 40px; top: 0; left: 0;\">
						<div style=\"position: absolute; top: 0; left: 0;\">
							" . userLink($poster) . "<br />
							<span class=\"date\">$meta</span>
						</div>
					</div>
					<span style=\"text-align:left; display: none;\" id=\"dyna_${post['id']}\">
						&nbsp;
					</span>
				</th>
				<th>
					$links
				</th>
			</tr>
			<tr>
				<td colspan=\"3\" class=\"cell0 mobile_postBox\">
					$postText
				</td>
			</tr>
		</table>
	";
}

