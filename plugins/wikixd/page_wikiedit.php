<?php

require 'wikilib.php';

if (isset($_GET['createnew']))
{
	$ptitle = title2url($_POST['title']);
	$page = array(
		'id' => $ptitle,
		'revision' => 0,
		'flags' => 0,
		'text' => '',
		'new' => 2,
		'canedit' => $canedit
	);
}
else
{
	$page = getWikiPage($_GET['id']);
	$ptitle = $page['id'];
}

if (!preg_match('@[^_]@', $ptitle)) Kill('You must enter a page title.');

if (!$page['canedit']) Kill('You may not '.($page['new'] == 2 ? 'create pages.' : 'edit this page.'));
if (($page['flags'] & WIKI_PFLAG_DELETED) && !$canmod) Kill('This page has been deleted.');

$urltitle = $ptitle;//urlencode($ptitle);
$nicetitle = htmlspecialchars(url2title($ptitle));
$title = 'Wiki &raquo; '.($page['new'] == 2 ? 'New page' : 'Editing: '.$nicetitle);

$links = new PipeMenu();

if ($page['new'] != 2)
{
	if ($page['istalk'])
	{
		$links -> add(new PipeMenuLinkEntry('Page', 'wiki', substr($urltitle,5)));
		$links -> add(new PipeMenuTextEntry('Discuss'));
	}
	else
	{
		$links -> add(new PipeMenuTextEntry('Page'));
		$links -> add(new PipeMenuLinkEntry('Discuss', 'wiki', 'Talk:'.$urltitle));
	}

	$links -> add(new PipeMenuLinkEntry('View', 'wiki', $urltitle));
}

makeLinks($links);

if (isset($_POST['saveaction']))
{
	if ($_POST['token'] !== $token) die('No.');

	if ($page['new'] == 2 && !$ptitle) Kill('Enter a title and try again.');

	$rev = $page['revision'];

	$flags = $page['flags'];
	setflag($flags, WIKI_PFLAG_NOCONTBOX, $_POST['nocontbox']);
	if ($canmod)
	{
		setflag($flags, WIKI_PFLAG_SPECIAL, $_POST['special']);
		setflag($flags, WIKI_PFLAG_DELETED, $_POST['deleted']);
	}

	if ($_POST['text'] !== $page['text'])
	{
		$rev++;
		Query("INSERT INTO {wiki_pages_text} (id,revision,date,user,text) VALUES ({0},{1},UNIX_TIMESTAMP(),{2},{3})",
			$page['id'], $rev, $loguserid, $_POST['text']);
	}

	Query("INSERT INTO {wiki_pages} (id,revision,flags) VALUES ({0},{1},{2}) ON DUPLICATE KEY UPDATE revision={1}, flags={2}",
		$page['id'], $rev, $flags);

	$bucket = 'wikixd_pageedit'; include($libPath . "/pluginloader.php");

	die(header('Location: '.actionLink('wiki', $page['id'])));
}

$crumbs = new PipeMenu();
$crumbs->add(new PipeMenuLinkEntry(__("Wiki"), "wiki"));
if (!$page['ismain'])
	$crumbs->add(new PipeMenuLinkEntry($nicetitle, "wiki", $urltitle));


if ($page['new'] == 2)
	$crumbs->add(new PipeMenuLinkEntry("New page", 'wikiedit', '', 'createnew'));
else if ($page['ismain'])
	$crumbs->add(new PipeMenuLinkEntry('Edit main page', 'wikiedit', $urltitle));
else
{
	$crumbs->add(new PipeMenuLinkEntry($nicetitle, 'wiki', $urltitle));
	$crumbs->add(new PipeMenuLinkEntry('Edit', 'wikiedit', $urltitle));
}
makeBreadcrumbs($crumbs);

echo '
		<table class="outline margin">
			<tr class="cell1">
				<td style="padding:0px 1em 1em;">';

if (isset($_POST['previewaction']))
{
	$page['text'] = $_POST['text'];

	setflag($page['flags'], WIKI_PFLAG_NOCONTBOX, $_POST['nocontbox']);
	if ($canmod)
	{
		setflag($page['flags'], WIKI_PFLAG_SPECIAL, $_POST['special']);
		setflag($page['flags'], WIKI_PFLAG_DELETED, $_POST['deleted']);
	}

	echo '
			<h1>Preview: '.$nicetitle.'</h1>'.wikiFilter($page['text'], $page['flags'] & WIKI_PFLAG_NOCONTBOX).'
		</td>
	</tr>
</table>
<table class="outline margin">
	<tr class="cell1">
		<td style="padding:0px 1em 1em;">';
}

$options = '<label><input type="checkbox" name="nocontbox" value="1"'.(($page['flags'] & WIKI_PFLAG_NOCONTBOX) ? ' checked="checked"':'').'/> Disable contents box</label> ';
if ($canmod)
{
	$options .= '<label><input type="checkbox" name="special" value="1"'.(($page['flags'] & WIKI_PFLAG_SPECIAL) ? ' checked="checked"':'').'/> Special page</label> ';
	$options .= '<label><input type="checkbox" name="deleted" value="1"'.(($page['flags'] & WIKI_PFLAG_DELETED) ? ' checked="checked"':'').'/> Deleted</label> ';
}

echo '
<h1>'.($page['new'] == 2 ? 'New page' : 'Editing: '.$nicetitle).'</h1>
<form action="" method="POST" name="editform">
	'.($page['new'] == 2 ? 'Title:<br><input type="text" name="title" value="'.$nicetitle.'" style="width:99.5%;" maxlength="200" /><br><br>' : '').'
	<textarea name="text" id="text" style="width:99.5%; height:30em;" onkeydown="tabfixor(this,event);">'.htmlspecialchars($page['text']).'</textarea><br>
	<input type="submit" name="saveaction" value="Save" /> <input type="submit" name="previewaction" value="Preview" /> '.$options.'
	<input type="hidden" name="token" value="'.$token.'" />
</form>
<script type="text/javascript">
	document.editform.text.focus();
	window.addEventListener("load",  hookUpControls, false);
	function tabfixor(t,e)
	{
		var code = e.keyCode||e.which;
		if (code == 9)
		{
			e.preventDefault();
			t.focus();
			if (document.selection)
				document.selection.createRange().text += \'\t\';
			else
			{
				var oldpos = t.selectionEnd;
				t.value = t.value.substring(0, t.selectionEnd) + \'\t\' + t.value.substring(t.selectionEnd, t.value.length);
				t.selectionStart = t.selectionEnd = oldpos + 1;
			}
		}
	}
</script>';

echo '
				</td>
			</tr>
		</table>';


function setflag(&$flags, $f, $b)
{
	if ($b) $flags |= $f;
	else $flags &= ~$f;
}

?>
