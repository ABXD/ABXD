<?php
	$cell = ($cell + 1) % 2;
	write("
		<tr class=\"cell{0}\">
			<td style=\"vertical-align: top\">
				Active plugins
			</td>
			<td>
", $cell);

	foreach($plugins as $p)
	{
		$d = $p['description'];
		if($p['author'])
			$d = "By ".$p['author'].". ".$d; 
		write("
				&bull; <span title=\"{1}\">{0}</span><br />
", $p['name'], $d);
	}
	
	write("
			</td>
		</tr>
");

?>
