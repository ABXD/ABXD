<?php

//$user['postheader'] = "";
//$user['signature'] = "";
if($loguserid)
	$user = $loguser;
else
{
	$user = Fetch(Query("select * from {$dbpref}users where id=1"));
	Alert("You are not logged in. The test page will be displayed using ".$user['name']."'s layout and statistics.");
}

$preview =
"Regular text.
One break

Two breaks
Sentence with a<br />HTML BR tag inbetween

[b]BB Bold[/b], [i]BB Italic[/i], <strong>HTML Strong</strong>, <em>HTML Emphasis</em>, <b>HTML Bold</b>, <u>HTML Underline</u> (should become a styled span), [s]BB Strikethrough[/s] and <del>HTML Deleted</del>.

&lt;tag> with the opening bracket escaped

/me does an IRC action

[url]http://helmet.kafuka.org[/url]
[url=http://helmet.kafuka.org]Titled URL[/url]
[img]img/avatars/1[/img]

<span style=\"display: none\">If you can see this line, \"display:\" is filtered into nonfunctionality.</span>

<script>alert(\"Scripts should be filtered.\");</script>

[quote]Quote block[quote][quote][quote]Sub-quote[/quote]Sub-quote[/quote]Sub-quote[/quote][/quote]
[quote=Ryuzaki]Quote with attribution[/quote]
[quote=\"Ryuzaki\" id=\"52\"]Quote with attribution and link[/quote]
[reply=\"Ryuzaki\"]Reply, attribution and link mandatory.[/quote]

[code]BB Code
Second line
<tag> in the middle

Fifth line, after empty fourth[/code]

<pre>HTML Pre tag
  Second line, has two spaces.
<tag> in the middle, tag should not be visible, leaving one space.

Fifth line, after empty fourth
	Sixth line, after tab (probably 8 characters, starting at the 'n' in \"line\")</pre>

HTML Pre tag around a BB Code:
<pre>[code]".'//Smilies
if(!$noSmilies)
{
	for($i = 0; $i < count($smilies); $i++)
		$s = str_replace($smilies[$i][\'code\'], "«".$smilies[$i][\'image\']."»", $s);
	for($i = 0; $i < count($smilies); $i++)
		$s = str_replace("«".$smilies[$i][\'image\']."»", "<img src=\\\"img/smilies/".$smilies[$i][\'image\'].
			"\\\" alt=\\\"".$smilies[$i][\'code\']."\\\" />", $s);
}'."[/code]</pre>

GeSHi colorcoding:
[source]void Function()
{
  Console.Write(\"No language given, C# assumed.\");
  someVar += 2;
  // Note that this introduces several new CSS classes:
  // .geshi      main block -- all others are below this: \".geshi .kw0\"
  // .kw0 to 4   several kinds of keywords
  // .st0        string literals
  // .nu0        number literals
  // .br0        brackets
  // .sy0        operators and such
  // .co1        comments
}[/source]
[source=C]void Function()
{
  printf(\"This is plain C.\");
  someVar += 2;
}[/source]
[source=html4strict]<p>
  This is a paragraph.
  This is <b>bold</b>
</p>[/source]

Linebreak type tests:
\\r\\n \r\n Should have a break
\\n        \n Should have a break
\\r      \r   Should not break

Single spoiler: [spoiler]This is a spoiler.[/spoiler]
Double spoiler: [spoiler]This is a spoiler [spoiler]containing another spoiler[/spoiler] which may not work properly[/spoiler]
Spoiler in a quote: [quote=Ryuzaki][spoiler]I'm L.[/spoiler][/quote]

Youtube embed:
[youtube]8l0IbaOr6go[/youtube]

Video tag:
[video]../over9000.ogv[/video]

Flash tag:
[swf 300 200]../houseloop.swf[/swf]

SVG tag:
[svg 256 256]
".'
<defs id="definitions">
	<linearGradient id="whiteToGrayPage">
		<stop id="whiteStop" style="stop-color: #ffffff; stop-opacity: 1" offset="0" />
		<stop id="grayStop" style="stop-color: #d1d1d1; stop-opacity: 1" offset="1" />
	</linearGradient>

	<linearGradient x1="310.13199" y1="463.93274" x2="339.66388" y2="470.00906" id="foldGradient" xlink:href="#whiteToGrayPage" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1.0209107,-0.1038824,0.1180689,1.1291473,-216.13394,-446.10298)" />

	<linearGradient x1="249.01784" y1="484.65646" x2="282.82748" y2="617.49152" id="pageGradient" xlink:href="#whiteToGrayPage" gradientUnits="userSpaceOnUse" gradientTransform="matrix(1.0209107,-0.1038824,0.1180689,1.1291473,-216.13394,-446.10298)" />

	<filter height="1.2003479" y="-0.10017395" width="1.0631233" x="-0.031561639" id="foldFilter">
		<feGaussianBlur id="foldBlur" stdDeviation="0.4848732" />
	</filter>
	<filter id="shadowFilter">
		<feGaussianBlur id="pageBlur" stdDeviation="2.8426648" />
	</filter>
</defs>

<g id="page">
	<path d="M 44.428599,35.937565 L 153.22815,24.86671 L 196.19462,61.993562 L 212.7728,220.53848 L 65.300428,235.54448 L 44.428599,35.937565 z" id="pageShadow" style="fill: #000000; fill-opacity: 0.41489366; stroke: none; stroke-width: 1; stroke-linecap: round; stroke-linejoin: round; stroke-miterlimit: 4; stroke-dasharray: none; stroke-opacity: 1; filter: url(#shadowFilter)" />

	<path d="M 45.428599,31.937565 L 154.22815,20.86671 L 197.19462,57.993562 L 213.7728,216.53848 L 66.300428,231.54448 L 45.428599,31.937565 z" id="pageSheet" style="fill: url(#pageGradient); fill-opacity: 1; stroke: #000000; stroke-width: 1; stroke-linecap: round; stroke-linejoin: round; stroke-miterlimit: 4; stroke-dasharray: none; stroke-opacity: 1" />
	
	<text x="60.40575" y="91.327873" transform="matrix(0.9458458,-9.6244218e-2,0.1093876,1.0461241,0,0)" id="writing" xml:space="preserve" style="font-size: 21.09502411px; font-style: normal; font-variant: normal; font-weight: normal; font-stretch: normal; text-align: start; line-height: 125%; writing-mode: lr-tb; text-anchor: start; fill: #008800; fill-opacity: 1; stroke: none; stroke-width: 1px; stroke-linecap: butt; stroke-linejoin: miter; stroke-opacity: 1; font-family: Calibri">
		<tspan x="60.40575" y="91.327873" id="line1">/* This is a</tspan>
		<tspan x="60.40575" y="117.69666" id="line2">document! */</tspan>
		<!-- line missing :) -->
		<tspan x="60.40575" y="170.43422" id="line4" style="fill: #000000">Yes it is, sirs!</tspan>
	</text>

	<path d="M 312.62856,486.03622 L 310.06421,441.06293 L 347.99755,478.59554 L 312.62856,486.03622 z" transform="matrix(1.0209107,-0.1038824,0.1127021,1.0778224,-212.68155,-422.15791)" id="foldShadow" style="fill: #000000; fill-opacity: 0.20212767; fill-rule: evenodd; stroke: none; stroke-width: 1px; stroke-linecap: butt; stroke-linejoin: miter; stroke-opacity: 1; filter: url(#foldFilter)" />

	<path d="M 154.2281,20.866714 L 197.19479,57.993145 L 158.52168,61.928311 L 154.2281,20.866714 z" id="pageFold" style="fill: url(#foldGradient); fill-opacity: 1; stroke: #000000; stroke-width: 1; stroke-linecap: round; stroke-linejoin: round; stroke-miterlimit: 4; stroke-dasharray: none; stroke-opacity: 1" />
</g>
'."
[/svg]
";

$previewPost['text'] = $preview;

$previewPost['num'] = "???";
$previewPost['id'] = "???";
$previewPost['uid'] = $user['id'];
$copies = explode(",","title,name,picture,sex,powerlevel,avatar,postheader,signature,posts,regdate,lastactivity,lastposttime");
foreach($copies as $toCopy)
	$previewPost[$toCopy] = $user[$toCopy];
MakePost($previewPost, POST_SAMPLE);

?>
