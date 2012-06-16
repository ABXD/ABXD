<?php

$ajaxPage = true;

if(!isset($_GET['u']))
	die("No user specified!");

$u = (int)$_GET['u'];

$user = Fetch(Query("select regdate from {$dbpref}users where id = ".$u));

$vd = date("m-d-y", $user['regdate']);
$dd = mktime(0, 0, 0, substr($vd, 0, 2), substr($vd, 3, 2), substr($vd, 6, 2));
$dd2 = mktime(0, 0, 0, substr($vd, 0, 2), substr($vd, 3, 2) + 1, substr($vd, 6, 2));

$nn = Query("select from_unixtime(date, '%Y%m%d') ymd, floor(date / 86400) d, count(*) c, max(num) m from {$dbpref}posts where user = ".$u." group by ymd order by ymd");

while($n = Fetch($nn))
{
	$p[$n[$d]] = $n[c];
	$t[$n[$d]] = $n[m];
}

for($i = 0; $dd + $i * 86400 < time(); $i++)
{
	$ps = Query("select count(*),max(num) from {$dbpref}posts where user = $u and date >= ".$dd." + ".$i." * 86400 and date < ".$dd2." + ".$i." * 86400");
	$p[$i] = Result($ps, 0, 0);
	$t[$i] = Result($ps, 0, 1);
}

$days = floor((time() - $dd) / 86400);
$m = max($p);

header('Content-type:image/png');
$img = imagecreatetruecolor($days, $m);
imagesavealpha($img, true);

$c['bk'] = imagecolorallocatealpha($img, 0, 0, 0, 127);
$c['bg1'] = imagecolorallocatealpha($img, 0, 0, 0, 127);
$c['bg2'] = imagecolorallocatealpha($img, 0, 0, 0, 100);
$c['bg3'] = imagecolorallocatealpha($img, 0, 0, 0, 64);
$c['mk1'] = imagecolorallocate($img, 110, 110, 160);
$c['mk2'] = imagecolorallocate($img, 70, 70, 130);
$c['bar'] = imagecolorallocatealpha($img, 250, 190, 40, 64);
$c['pt'] = imagecolorallocate($img, 250, 190, 40);

imagefill($img, 0, 0, $c['bk']);

for($i = 0; $i < $days; $i++)
{
	$num = date("m", $dd + $i * 86400) % 2 + 1;
	if(date("m-d", $dd + $i * 86400) == "01-01")
		$num = 3;
	imageline($img, $i, $m, $i, 0, $c['bg'.$num]);
}

for($i = 0; $i < $days; $i++)
{
	imageline($img, $i, $m, $i, $m - $p[$i], $c['bar']);
	imagesetpixel($img, $i, $m - $t[$i] / ($i + 1), $c['pt']);
}

imagepng($img);
imagedestroy($img);

?>
