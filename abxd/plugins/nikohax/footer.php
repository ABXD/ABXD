<?php

//Slowbans and such bullshit
$footerExtensionsB .= "<!-- powerlevel: ".$loguser['powerlevel']." -->";
if($loguser['powerlevel'] == -2)
{
	if(rand(0, 100) <= 25)
	{
		ob_end_clean();	
		die();
	}
	else
		usleep(10000000 + (rand(0, 20000) * 1000));
}

if(strrpos($_SERVER['SCRIPT_NAME'], "memberlist.php") !== FALSE)
	$footerExtensionsA .= "<p class=\"header0 outline smallFonts cell2\">Use caution in apprehending.</div>";

$footerButtons .= format(
"
			<a href=\"../abxd/\">
				<img src=\"../abxd/devblog.png\" alt=\"ABXD Devblog\" />
			</a>
			<a href=\"http://home.comcast.net/~SupportCD/FirefoxMyths.html\">
				<img src=\"../fxmyths.png\" alt=\"Firefox Myths\" />
			</a>
			<a href=\"http://www.opera.com\">
				<img src=\"../mini_opera.png\" alt=\"Get Opera 11\" />
			</a>
");

?>