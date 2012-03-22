<?php

function Cynthetiq_Apply($tag = "", $id = 0)
{
	global $user;
	if($tag != "userData")
		return;
	if("0401" == gmdate('md'))
	{
		$user['displayname'] = "Bruce";
		$user['sex'] = 0;
	}

	if($id == 8)
		$user['lastknownbrowser'] = "Cynthetiq 0.31 on Windows 7 <!-- Cynthetiq/0.31 (Windows NT 6.1; U; en; .NET CLR 3.5.30729) -->";
	elseif($id == 1)
	{
		$rndTitles = array(
			"12 herbs and spices!",
			":3",
			"A bit of a failure",
			"A very very naughty boy!",
			"Affected by a raging stiffie",
			"Always checks behind the chair",
			"Amateur Vidya Dev'r",
			"Ambidextrous Sprite",
			"Badass Furry",
			"Bec Noir",
			"Better than Blackhole",
			"Better with Bacon",
			"Better with Chocolate",
			"BL4R",
			"Board Programmer",
			"Can Breathe in Space",
			"Catgirl Fanboi",
			"Censor Decoy",
			"Ceci n'est pas une random title!",
			"Coke&trade; Addict",
			"Contractually Immortal",
			"Don't. Even. Blink.",
			"Draco in Hotpants",
			"Draco in Leather Pants",
			"Ensemble Darkhorse",
			"FMOD user",
			"H3H3H3H",
			"Hard to label!",
			"Has a random title",
			"Has been working on the railroad",
			"I Am Iron Man",
			"I can't use these things together!",
			"I found Forrester and all I got was a random title",
			"I said, put the bunny back in the box!",
			"I was frozen today!",
			"I'm the one with the most talent here.",
			"Is gonna need more trope",
			"It's a long story that involves a pi&ntilde;ata and a gun and a very naughty doggie&hellip;",
			"Keyboard Compatible!",
			"Large and In Charge",
			"Large Ham",
			"Local source of MSU1 information",
			"Lonely Man",
			"Lotus Eater",
			"Loves the whole world and all its' sights and sounds",
			"Lying Dutchman",
			"May contain nuts",
			"Mythbuster",
			"Nazi science sneers at your custom titles!",
			"None of this makes any sense.",
			"Not Richard Pryor",
			"Not the Messiah",
			"Now then, let's have a nice naked talk.",
			"OBJECTION!!",
			"Off Like a Shot",
			"Off-Model",
			"One of a kind!",
			"Put the bunny back in the box.",
			"Random Number God",
			"Random title!",
			"Rated M for Magikarp",
			"Rated M for Manly",
			"Rated M for Mental",
			"Rated M for Monkey",
			"Really the Metaknight",
			"Secretly, I'm Alan Rickman",
			"Secretly, I'm Alex Workman",
			"Secretly, I'm Andrew Hussie",
			"Secretly, I'm Charles Darwin",
			"sEcReTlY, I'm GaMzEe MaKaRa",
			"Secretly, I'm Kevin Flynn",
			"Secretly, I'm Patrick Stewart",
			"Secretly, I'm Robin Williams",
			"Secretly, I'm William Shatner",
			"Shinji Ikari",
			"Tall, Dark and Bishoujo",
			"The Brickshitter&trade;",
			"The Snarkmeister",
			"The Thirteenth Doctor",
			"There's suspension of disbelief, and then there's insulting my fucking intelligence.",
			"TV Troper",
			"Universally genre savvy",
			"Upper Class Twit",
			"What do you mean, it's not a random title?",
			"Why couldn't you put the bunny back in the box?",
			"XNA user",
			"YEEEEAAAAH~!",
			"You spin me right round baby right round like a record baby",
			"You're really messing with my Zen thing, man",
		);
		$user['title'] = $rndTitles[array_rand($rndTitles)];
	}
	else if($id == 13)
	{
		$t = "";
		for($a = 0; $a < 7; $a++)
			$t .= chr(rand(65, 90));
		$t[1] = "A";
		$t[4] = "A";
		$user['title'] = "I AM A ".$t;
	}
}

function COPS_Write()
{
	global $footerExtensions, $ScriptName;
	if(strrpos($_SERVER['SCRIPT_NAME'], "memberlist.php") !== FALSE)
		$footerExtensions .= "<p class=\"faq smallFonts cell0\">Use caution in apprehending.</div>";
}

function IRC_Header($tag)
{
	if($tag == "top")
		Write(
"
	<li>
		<a href=\"irc.php\">IRC chat</a>
	</li>
	<li>
		<a href=\"../thepile/\">Wiki</a>
	</li>
");
}

function Niko_FooterButtons()
{
	global $footerExtraButtons;
	$footerExtraButtons .= format(
"
			<a href=\"http://home.comcast.net/~SupportCD/FirefoxMyths.html\">
				<img src=\"../fxmyths.png\" alt=\"Firefox Myths\" />
			</a>
			<a href=\"http://www.opera.com\">
				<img src=\"../mini_opera.png\" alt=\"Get Opera 11\" />
			</a>
");
}

register("headers", "IRC_Header", 1);

register("manglers", "Cynthetiq_Apply", 2);
register("footers", "COPS_Write");
register("footerButtons", "Niko_FooterButtons");

function test($tag)
{
	write(
"
	Hello from the writers bucket. Tag is $tag.
");
}

?>