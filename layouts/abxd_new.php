<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
	<title><?php print $layout_title?></title>
	<?php include("header.php"); ?>
	<link rel="stylesheet" href="./layouts/abxd_new.css" type="text/css" />
</head>
<body style="width:100%; font-size: <?php print $loguser['fontsize']; ?>%;">
	<div id="main" style="padding:8px;">
		<div class="outline margin" id="header">
			<table class="outline margin">
				<tr>
					<td colspan="3" class="cell0">
						<!-- Board header goes here -->
						<table>
							<tr>
								<td style="border: 0px none; text-align: left;">
									<a href="<?php echo $boardroot;?>">
										<img id="theme_banner" src="<?php print htmlspecialchars($layout_logopic); ?>" alt="" title="<?php print htmlspecialchars($layout_title); ?>" style="padding: 8px;" />
									</a>
								</td>
								<?php if($layout_pora) { ?>
								<td style="border: 0px none;">
									<?php print $layout_pora; ?>
								</td>
								<?php } ?>
							</tr>
						</table>
					</td>
				</tr>
				<tr class="cell1 mainMenuContainer">
					<td>
						<div class="userDropdownContainer">
						<?php print userLink($loguser, true);
							if ($loguserid) {
								$layout_userpanel->shift();
								$layout_userpanel->setClass("userMenu");
							}
							print $layout_userpanel->build();
							$layout_navigation->setClass("mainMenu");
							?>
						</div>
						<?php print $layout_navigation->build(); ?>
					</td>
				</tr>
			</table>
		</div>
	<div style="text-align: right;" class="nOnlineUsers">
		<?php print $layout_onlineusers; ?>
	</div>
	<form action="<?php print actionLink('login'); ?>" method="post" id="logout">
		<input type="hidden" name="action" value="logout" />
	</form>

	<?php print $layout_bars; ?>
	<?php print $layout_crumbs;?>
	<?php print $layout_contents;?>
	<?php print $layout_crumbs;?>

	</div>
	<div class="footer" style='clear:both;'>
	<?php print $layout_footer;?>
	</div>
</body>
</html>
