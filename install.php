<?php

	// Make the board paths for installer and board use.
	$installationPath = dirname(__FILE__);
	$libPath = dirname(__FILE__) . "/lib";

	include ($installationPath . "/install/installer.php");

	$sqlConfigured = file_exists($installationPath . "/config/database.php");
	$upgrade = false;

	if($sqlConfigured)
	{
		include($installationPath . "/config/database.php");
		if(!sqlConnect())
			$sqlConfigured = false;
		else if(getInstalledVersion() != -1)
			$upgrade = true;
	}

?>

<!DOCTYPE html>
<html>
	<head>
		<title>ABXD installation</title>
		<link rel="stylesheet" href="css/common.css" type="text/css" />
		<link rel="stylesheet" href="themes/gold/style.css" type="text/css" />
		<script src="js/jquery.js"></script>
		<script src="install/installer.js"></script>
		<script type="text/javascript">
			var upgrade=<?php echo $upgrade?"true":"false";?>;
			var sqlConfigured=<?php echo $sqlConfigured?"true":"false";?>;
		</script>
		<style>
			.page {
				padding: 4px;
			}
		</style>
	</head>
	<body style="width:100%;">
	<div id="body">
	<div id="body-wrapper" style="max-width:900px">
	<div id="main" style="padding:8px;">
		<img src="img/logo.png" alt="ABXD" style="margin: 2em auto; display: block;" />
		<table class="outline margin" id="installUI" style="display: none;">
			<tr class="cell0">
				<td style="max-height: 500px; height: 500px; vertical-align: top;" id="installPager">
					<div class="page" id="page1">
						<h3>Welcome to ABXD</h3>
						<p>
							Welcome to the installer for ABXD.
							We've done a few changes in 3.0 to make using ABXD even better, including:
							<ul>
								<li>Large codebase cleanups &mdash; the board should now work better than ever!</li>
								<li>A new, flexible permissions system that lets you define user groups or per-user permissions, such as forum access.</li>
								<li>Easier theme installation for administrators</li>
								<li>[ devs add stuff here ]</li>
							</ul>
						<p>
							Thanks for using ABXD!<br />
							- The ABXD developers
					</div>
					<div class="page" id="page2">
						<div class="install-only sql-configured">
							<h3>
								SQL setup
							</h3>
							<p>The SQL credentials for your board are already configured, but the board doesn't seem to be installed. Click Install below to install it</p>
							<p>If you wish to edit your SQL credentials, edit or remove the file config/database.php</p>
						</div>
						<div class="install-only sql-not-configured">
							<h3>
								SQL setup
							</h3>
							<p>Let's get going. Enter the SQL credentials for the SQL server you want to connect to and click "Check connection" to make sure it works.</p>
							<table style="width: 50%;margin-left:auto;margin-right:auto;margin-top:50px; margin-bottom:50px;" class="outline margin">
								<tr class="header1">
									<th colspan="2">
										SQL credentials
									</th>
								</tr>
								<tr class="cell0">
									<td>
										Server address
									</td>
									<td>
										<input type="text" name="sqlServerAddress" id="sqlServerAddress" value="localhost" />
									</td>
								</tr>
								<tr class="cell1">
									<td>
										Username
									</td>
									<td>
										<input type="text" name="sqlUserName" id="sqlUserName" value="abxd" />
									</td>
								</tr>
								<tr class="cell0">
									<td>
										Password
									</td>
									<td>
										<input type="password" name="sqlPassword" id="sqlPassword" value="" />
									</td>
								</tr>
								<tr class="cell1">
									<td>
										Database name
									</td>
									<td>
										<input type="text" name="sqlDbName" id="sqlDbName" value="abxd" />
									</td>
								</tr>
								<tr class="cell0">
									<td>
										Table prefix
									</td>
									<td>
										<input type="text" name="sqlTablePrefix" id="sqlTablePrefix" value="" />
									</td>
								</tr>
							</table>
							<p>When you're done, click Install below.</p>
						</div>
						<div class="upgrade-only">
							<h3>Upgrade</h3>
							<p>The board is already installed. Click the button below to upgrade it.</p>
							<p>If you wish to reinstall instead, delete all the tables in your MySQL database and come back again.</p>
						</div>
					</div>
					<div class="page" id="page3">
						<div class="install-only">
							<h3>Install</h3>
						</div>
						<div class="upgrade-only">
							<h3>Upgrade</h3>
						</div>
						<div id="install-output" style="white-space: pre-wrap; font-family: monospace; margin:50px;">Installing...</div>
					</div>
					<div class="page" id="page4">
						<div class="install-only">
							<h3>Installed!</h3>
							<p>The board is installed now. Now you should go and register an account. The first account registered will be the root admin.</p>
							<p><a href="./">Click here to go to your new board!</a></p>
						</div>
						<div class="upgrade-only">
							<h3>Upgraded!</h3>
							<p>The board is upgraded now. Congratulations!</p>
							<p><a href="./">Click here to go to your new board!</a></p>
						</div>
					</div>
				</td>
			</tr>
			<tr class="cell0" id="installProgress">
				<td>
					<div class="pollbarContainer" style="width: 98%; margin: 4pt auto; display: block;">
						<div class="pollbar" id="progress" >
							[ Actual step number is put here by JS ]
						</div>
					</div>
				</td>
			</tr>
			<tr class="cell1">
				<td style="text-align: right;">
					<button disabled id="prevPageButton">&larr; Previous</button>
					<button id="nextPageButton">Next &rarr;</button>
				</td>
			</tr>
		</table>
	</div>
	</div>
	</div>
	</body>
</html>
