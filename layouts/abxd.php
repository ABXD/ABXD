<!doctype html>
<html>

<head>
	<title><?php print $layout_title?></title>
	<?php include("header.php"); ?>
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
				<tr class="cell1">
					<td rowspan="3" class="smallFonts" style="text-align: center; width: 10%;">
						<?php print $layout_views; ?>
					</td>
					<td class="smallFonts" style="text-align: center; width: 80%;">
						<?php print $layout_navigation->build();?>
					</td>
					<td rowspan="3" class="smallFonts" style="text-align: center; width: 10%;">
						<?php print $layout_time; ?>
					</td>
				</tr>
				<tr class="cell2">
					<td class="smallFonts" style="text-align: center">
						<?php print $layout_userpanel->build(); ?>
					</td>
				</tr>
				<tr class="cell2">
					<td colspan="1" class="smallFonts" style="text-align: center">
						<?php print $layout_onlineusers; ?>
					</td>
				</tr>
			</table>
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
