	<meta http-equiv="Content-Type" content="text/html; CHARSET=utf-8" />
	<meta name="description" content="<?php print Settings::get('metaDescription'); ?>" />
	<meta name="keywords" content="<?php print Settings::get('metaTags'); ?>" />
	<link rel="shortcut icon" type="image/x-icon" href="<?php print $layout_favicon;?>" />
	<link rel="stylesheet" type="text/css" href="<?php print resourceLink("css/common.css");?>" />
	<link rel="stylesheet" type="text/css" href="<?php print resourceLink("js/spectrum.css");?>" />
	<link rel="stylesheet" href="<?php print resourceLink("css/font-awesome.min.css");?>">
	
	<script type="text/javascript" src="<?php print resourceLink("js/jquery.js");?>"></script>
	<script type="text/javascript" src="<?php print resourceLink("js/tricks.js");?>"></script>
	<script type="text/javascript" src="<?php print resourceLink("js/jquery.tablednd_0_5.js");?>"></script>
	<script type="text/javascript" src="<?php print resourceLink("js/jquery.scrollTo-1.4.2-min.js");?>"></script>
	<script type="text/javascript" src="<?php print resourceLink("js/spectrum.js");?>"></script>
	<script type="text/javascript">
		boardroot = <?php print json_encode($boardroot); ?>;
	</script>

	<?php
		if(file_exists("layouts/$layout/style.css"))
			echo '<link rel="stylesheet" href="'.resourceLink("layouts/$layout/style.css").'" type="text/css" />';
		if(file_exists("layouts/$layout/script.js"))
			echo '<script type="text/javascript" src="'.resourceLink("layouts/$layout/script.js").'"></script>';
	?>
	<link rel="stylesheet" type="text/css" id="theme_css" href="<?php print resourceLink($layout_themefile); ?>" />
	<?php
		if(Settings::get("maintenancemode") == 1 && $loguser['powerlevel'] < 1)
			die("The forums are currently undergoing maintenance. Please check back at a later date.");
	?>
	<?php
		$bucket = "pageHeader"; include("./lib/pluginloader.php");
	?>

