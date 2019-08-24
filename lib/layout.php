<?php

if($mobileLayout)
	include("layout_mobile.php");
else
	include("layout_nomobile.php");

function gfxnumber($num)
{
	return $num;
	// 0123456789/NA-
	
	$sign = '';
	if ($num < 0)
	{
		$sign = '<span class="gfxnumber" style="background-position:-104px 0px;"></span>';
		$num = -$num;
	}
	
	$out = '';
	while ($num > 0)
	{
		$out = '<span class="gfxnumber" style="background-position:-'.(8*($num%10)).'px 0px;"></span>'.$out;
		$num = floor($num / 10);
	}
	
	return '<span style="white-space:nowrap;">'.$sign.$out.'</span>';
}

function makeLinks($links)
{
	global $layout_links;
	$bucket = "links"; include("lib/pluginloader.php");
	$layout_links = $links;
}

function makeForumCrumbs($crumbs, $forum)
{
	while(true)
	{
		$crumbs->addStart(new PipeMenuLinkEntry($forum['title'], "forum", $forum["id"]));
		if($forum["catid"] >= 0) break;
		$forum = Fetch(Query("SELECT * from {forums} WHERE id={0}", -$forum["catid"]));
	}
}

function makeBreadcrumbs($path)
{
	global $layout_crumbs;
	$path->addStart(new PipeMenuLinkEntry(Settings::get("breadcrumbsMainName"), "board"));
	$path->setClass("breadcrumbs");
	$bucket = "breadcrumbs"; include("lib/pluginloader.php");
	$layout_crumbs = $path;
	
	/*
	if(count($path) != 0)
	{
		$pathPrefix = array(Settings::get("breadcrumbsMainName") => actionLink(0));
		$pathPostfix = array(); //Not sure how this could be used, but...

		$bucket = "breadcrumbs"; include("lib/pluginloader.php");

		$path = $pathPrefix + $path + $pathPostfix;
	}

	$first = true;

	$crumbs = "";
	foreach($path as $text=>$link)
	{
		if(is_array($link))
		{
			$dalink = $text;
			$tags = $link[1];
			$text = $link[0];
			$link = $dalink;
		}
		else
			$tags = "";

		$link = str_replace("&","&amp;",$link);

		if(!$first)
			$crumbs .= " &raquo; ";
		$first = false;

		if(!$tags)
			$crumbs .= "<a href=\"".$link."\">".$text."</a>";
		else if (Settings::get("tagsDirection") === 'Left')
			$crumbs .= $tags." <a href=\"".$link."\">".$text."</a>";
		else
			$crumbs .= "<a href=\"".$link."\">".$text."</a> ".$tags;
	}

	if($links)
		$links = "<ul class=\"pipemenu smallFonts\">
			$links
		</ul>";

	$layout_crumbs = "
<div class=\"margin\">
	<div style=\"float: right;\">
		$links
	</div>
	$crumbs&nbsp;
</div>";*/
}
function mfl_forumBlock($fora, $catid, $selID, $indent)
{
	$ret = '';
	
	foreach ($fora[$catid] as $forum)
	{
		$ret .=
'				<option value="'.$forum['id'].'"'.($forum['id'] == $selID ? ' selected="selected"':'').'>'
	.str_repeat('&nbsp; &nbsp; ', $indent).htmlspecialchars($forum['title'])
	.'</option>
';
		if (!empty($fora[-$forum['id']]))
			$ret .= mfl_forumBlock($fora, -$forum['id'], $selID, $indent+1);
	}
	
	return $ret;
}

function makeForumList($fieldname, $selectedID)
{
	global $loguserid, $loguser;

	$pl = $loguser['powerlevel'];
	if($pl < 0) $pl = 0;
	
	$rCats = Query("SELECT id, name FROM {categories} ORDER BY corder, id");
	$cats = array();
	while ($cat = Fetch($rCats))
		$cats[$cat['id']] = $cat;

	$rFora = Query("	SELECT
							f.id, f.title, f.catid
						FROM
							{forums} f
						WHERE ".forumAccessControlSQL().(($pl < 1) ? " AND f.hidden=0" : '')."
						ORDER BY f.forder, f.id");
						
	$fora = array();
	while($forum = Fetch($rFora))
		$fora[$forum['catid']][] = $forum;

	$theList = '';
	foreach ($cats as $cid=>$cat)
	{
		if (empty($fora[$cid]))
			continue;
			
		$cname = $cat['name'];
			
		$theList .= 
'			<optgroup label="'.htmlspecialchars($cname).'">
'.mfl_forumBlock($fora, $cid, $selectedID, 0).
'			</optgroup>
';
	}

	return "<select id=\"$fieldname\" name=\"$fieldname\">$theList</select>";
}


function doLastPosts($compact, $limit)
{
	global $mobileLayout, $loguser;
	if($mobileLayout)
		$compact = true;
		
	$hours = 72;

	$rPosts = Query("SELECT
						p.id, p.date,
						u.(_userfields),
						t.title AS ttit, t.id AS tid,
						f.title AS ftit, f.id AS fid
					FROM {posts} p
						LEFT JOIN {users} u on u.id = p.user
						LEFT JOIN {threads} t on t.id = p.thread
						LEFT JOIN {forums} f on t.forum = f.id
					WHERE ".forumAccessControlSql()." AND p.date >= {0}
					ORDER BY date DESC LIMIT 0, {1u}", 
			(time() - ($hours * 60*60)), $limit);

	while($post = Fetch($rPosts))
	{
		$thread = array();
		$thread["title"] = $post["ttit"];
		$thread["id"] = $post["tid"];

		$c = ($c+1) % 2;
		if($compact)
		{
			$theList .= format(
			"
				<tr class=\"cell{5}\">
					<td>
						{3} &raquo; {4}
						<br>{2}, {1} 
						<span style=\"float:right\">&raquo; {6}</span>
					</td>
				</tr>
			", $post['id'], formatdate($post['date']), UserLink(getDataPrefix($post, "u_")), 
				actionLinkTag($post["ftit"], "forum", $post["fid"], "", $post["ftit"]), makeThreadLink($thread), $c, 
				actionLinkTag($post['id'], "post", $post['id']));
		}
		else
		{
			$theList .= format(
			"
				<tr class=\"cell{5}\">
					<td>
						{3}
					</td>
					<td>
						{4}
					</td>
					<td>
						{2}
					</td>
					<td>
						{1}
					</td>
					<td>
						&raquo; {6}
					</td>
				</tr>
			", $post['id'], formatdate($post['date']), UserLink(getDataPrefix($post, "u_")), 
				actionLinkTag($post["ftit"], "forum", $post["fid"], "", $post["ftit"]), makeThreadLink($thread), $c,
				actionLinkTag($post['id'], "post", $post['id']));
		}
	}

	if(!isset($theList))
		$theList = format(
	"
		<tr class=\"cell1\">
			<td colspan=\"5\" style=\"text-align: center\">
				".__("Nothing has been posted in the last {0}.")."
			</td>
		</tr>
	", Plural($hours, __("hour")));

	if($compact)
		write(
		"
		<table class=\"margin outline\">
			<tr class=\"header0\">
				<th colspan=\"5\">".__("Last posts")."</th>
			</tr>
			{0}
		</table>
		", $theList);
	else
		write(
		"
		<table class=\"margin outline\">
			<tr class=\"header0\">
				<th colspan=\"5\">".__("Last posts")."</th>
			</tr>
			<tr class=\"header1\">
				<th>".__("Forum")."</th>
				<th>".__("Thread")."</th>
				<th>".__("User")."</th>
				<th>".__("Date")."</th>
				<th></th>
			</tr>
			{0}
		</table>
		", $theList);
}

function doPostForm($form)
{
	global $mobileLayout;
	
	if($mobileLayout)
		echo $form;
	else
	{
		print "
			<table style=\"width: 100%;\">
				<tr>
					<td style=\"vertical-align: top; border: none;\">
						$form
					</td>
					<td style=\"width: 20%; vertical-align: top; border: none;\">";

		DoSmileyBar();
		DoPostHelp();

		echo "
					</td>
				</tr>
			</table>";
	}

	echo "
		<script type=\"text/javascript\">
			document.postform.text.focus();
		</script>
	";
}
