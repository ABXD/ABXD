<?php
header("Content-Type: text/css");

$curtime = getdate(time());
$min = $curtime['hours'] * 60 + $curtime['minutes'];

$hue = ($min / 2) % 360;
$sat = 50;
$hs = $hue.", ".$sat."%";

$css = "/* AcmlmBoard XD - Daily Cycle */
@import url('../../css/roundcorners.css');

body
{
	background: hsl([huesat], 15%) url(background.png);
}

.outline
{
	outline-color: hsl([huesat], 20%);
}

.cell0, table.post td.post
{
	background: hsl([huesat], 16%) url(cellgradient.png) repeat-x top;
}

.cell1, table.post, .faq, .errorc, .post_content
{
	background: hsl([huesat], 20%) url(cellgradient.png) repeat-x top;
}

.cell2
{
	background: hsl([huesat], 28%) url(cellgradient.png) repeat-x top;
}

.header0 th
{
	background: hsl([huesat], 32%) url(headergradient.png) repeat-x bottom;
	color: #fff;
	text-shadow: 1px 1px 0px #000;
}

.header1 th, .errort
{
	background: hsl([huesat], 40%) url(headergradient.png) repeat-x bottom;
	color: #fff;
	text-shadow: 1px 1px 0px #000;
}

.errort, .errorc
{
	padding: 0px 2px;
}

.errort
{
	text-align: center;
}

h3
{
	border-top: 0px none;
	border-bottom-color: hsl([huesat], 48%);
}

#pmNotice
{
	background: hsla([huesat], 48%, 0.75);
}

#pmNotice:hover
{
	background: hsl([huesat], 48%);
}

.swf
{
	border-color: hsl([huesat], 24%);
	background: hsl([huesat], 24%);
}

.swfmain
{
	border-color: hsl([huesat], 24%);
}

.swfbuttonon, .swfbuttonoff
{
	border-color: hsl([huesat], 48%);
	background: hsl([huesat], 48%);
}

button, input[type=submit]
{
	border: 1px solid hsl([huesat], 20%);
	background-color: hsl([huesat], 30%);
	color: white;
	text-shadow: 1px 1px 0px #000;
	border-radius: 8px;
}

input[type=text], input[type=password], input[type=file], input[type=email], textarea, select
{
	border-radius: 6px;
	border: 1px solid hsl([huesat], 20%);
	background-color: hsl([huesat], 10%);
	color: white;
}

input[type=checkbox], input[type=radio]
{
	border: 1px solid hsl([huesat], 20%);
	background-color: hsl([huesat], 10%);
	color: white;
}
input[type=radio]
{
	border-radius: 8px;
}

.pollbarContainer
{
	border: 1px solid hsl([huesat], 30%);
}


.post_about, .post_topbar
{
	background: hsl([huesat], 16%) url(cellgradient.png) repeat-x top;
}
.post_about, .post_topbar, .post_content
{
	border: 1px solid hsl([huesat], 20%);
}

table.post
{
	border: 1px solid hsl([huesat], 20%);
}

table.outline
{
	border: 1px solid hsl([huesat], 20%);
}

div#tabs button
{
	border-top-left-radius: 8px;
	border-top-right-radius: 32px;
	border-bottom-left-radius: 0px;
	border-bottom-right-radius: 0px;
	padding-right: 16px;
	background: hsl([huesat], 30%);
}

div#tabs button.selected
{
	position: static;
	z-index: -100;
	border-bottom: 1px solid hsl([huesat], 20%);
	background: hsl([huesat], 40%);
}

";

print str_replace("[huesat]", $hs, $css);

?>
