<?php
if (!$mobileLayout) echo '<a href="#" onclick="enableMobileLayout(1); return false;" rel="nofollow">Mobile view</a>';
else echo '<a href="#" onclick="enableMobileLayout(-1); return false;" rel="nofollow">Disable mobile view</a>';
?>
<br>
<br>
<?php $bucket = "footer"; include("./lib/pluginloader.php");?>
AcmlmBoard XD <?php print $versionInfo['major']; ?>.<?php print $versionInfo['subversion']; ?>.<?php print $versionInfo['minor']; ?><br />
&copy; 2011-<?php print date("Y"); ?> The ABXD Team<br />
<!-- Dirbaio, Nadia, Arisotura, Kawa, xfix et al. -->
<?php print __("<!-- English translation by The ABXD Team -->")?>

<?php print (isset($footerButtons) ? $footerButtons : "")?>
<?php print (isset($footerExtensionsB) ? $footerExtensionsB : "")?>


