<?php
$maps = trim(Settings::pluginGet('maps'));
if (!preg_match('/^(?:\w+ )*\w+$/', $maps))
{
	Kill('Please configure this plugin.');
}
$db = @new mysqli(Settings::pluginGet('dbserv'), Settings::pluginGet('dbuser'), Settings::pluginGet('dbpass'), Settings::pluginGet('dbname')) or Kill('Couldn\'t get information.');
$blocks = require $installationPath . '/plugins/minestats/mcblocks.php';
$maps = explode(' ', $maps);

$properties = array(
	'map' => array(__('Map'), array_combine($maps, $maps)),
	'dir' => array(__('Direction'), array('ASC' => __('Ascending'), 'DESC' => __('Descending'))),
	'order' => array(__('Order by'), array('created' => __('Created'), 'destroyed' => __('Destroyed'), 'SUM(created) + SUM(destroyed)' => __('Sum of created and destroyed'))),
	'player' => array(__('Show'), array('NULL' => __('By player'), 0 => __('By block'))),
);

$map = isset($_GET['map']) && in_array($_GET['map'], $maps) ? $_GET['map'] : $maps[0];
$dir = isset($properties['dir'][1][$_GET['dir']]) ? $_GET['dir'] : 'DESC';
$order = isset($properties['order'][1][$_GET['order']]) ? $_GET['order'] : 'destroyed';

echo '<table class="outline margin width100"><tr class="header0"><th colspan=3>', __('Settings');
$i = 1;
foreach ($properties as $name => $property) {
	$i = ($i + 1) % 2;
	echo "<tr class=cell$i><td style=width:100px>$property[0]:<td>";
	$properties = array();
	foreach ($property[1] as $key => $value) {
		$gets = $_GET;
		if ($name === 'block' || $name === 'map')
		{
			unset($gets['block']);
			unset($gets['player']);
		}
		if ($key === 'NULL')
			unset($gets[$name]);
		else
			$gets[$name] = $key;
		$properties[] = '<a href="?' . htmlspecialchars(http_build_query($gets)) . '">' . $value . '</a>';
	}
	echo implode(' | ', $properties);
}
echo '</table>';
echo '<table class="outline margin width100"><tr class="header0"><th>';
$condition1 = 'type != 0';
$condition2 = 'replaced != 0';
if (isset($_GET['player']))
{
	$arg = 'block';
	$group = 'type';
	$replaced = 'replaced';
	$name = 'type';
	if ($_GET['player']) {
		$condition1 .= ' AND playerid = ' . (int) $_GET['player'];
		$condition2 .= ' AND playerid = ' . (int) $_GET['player'];
	}
	echo __('Block');
}
else {
	$arg = 'player';
	$join = 'INNER JOIN `lb-players` USING (playerid)';
	$group = 'playerid';
	$replaced = 'playerid';
	$name = 'playername';
	$additionalfields = ', `lb-players`.playerid';
	if ((int) $_GET['block']) {
		$condition1 = 'type = ' . (int) $_GET['block'];
		$condition2 = 'replaced = ' . (int) $_GET['block'];
	}
	echo __('Player');
}
echo '<th>', __('Created'), '<th>', __('Destroyed');
$data = $db->query("
	SELECT $name, SUM(created) created, SUM(destroyed) destroyed $additionalfields
	FROM
	(
		(
			SELECT $group, count(*) created, 0 destroyed
			FROM `lb-$map`
			WHERE $condition1
			AND type != replaced
			GROUP BY $group
		)
		UNION
		(
			SELECT $replaced type, 0 created, count(*) destroyed
			FROM `lb-$map`
			WHERE $condition2
			AND type != replaced
			GROUP BY $replaced
		)
	) t
	$join GROUP BY $group ORDER BY $order $dir
");
$i = 1;
$gets = $_GET;
unset($gets['block']);
unset($gets['player']);
while ($row = $data->fetch_array()) {
	$i = ($i + 1) % 2;
	$gets[$arg] = $row[0];
	if (isset($_GET['player'])) {
		$description = isset($blocks[$row[0]]) ? $blocks[$row[0]] : "Block $row[0]";
	}
	else {
		$description = $row[0];
		if ($description == 'WaterFlow') $description = 'Water Flow';
		if ($description == 'LavaFlow') $description = 'Lava Flow';
		$gets[$arg] = $row[3];
	}
	echo "<tr class=cell$i><td><a href='?", htmlspecialchars(http_build_query($gets)), "'>", $description, "</a><td>$row[1]<td>$row[2]";
}
echo '</table>';
?>
