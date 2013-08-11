<?php
$genericInt = "int(11) NOT NULL DEFAULT '0'";
$smallerInt = "int(8) NOT NULL DEFAULT '0'";
$bool = "tinyint(1) NOT NULL DEFAULT '0'";
$notNull = " NOT NULL DEFAULT ''";
$text = "text DEFAULT ''"; //NOT NULL breaks in certain versions/settings.
$var128 = "varchar(128)".$notNull;
$var256 = "varchar(256)".$notNull;
$var1024 = "varchar(1024)".$notNull;
$AI = "int(11) NOT NULL AUTO_INCREMENT";
$keyID = "primary key (`id`)";

function Import($sqlFile)
{
	global $dblink, $dbpref;
	$res = $dblink->multi_query(str_replace('{$dbpref}', $dbpref, file_get_contents($sqlFile)));

	$i = 0;
	if ($res) {
		do {
			$i++;
		} while ($dblink->more_results() && $dblink->next_result());
	}
	if ($dblink->errno) {
		echo "MySQL Error when importing file $sqlFile at statement $i: \n";
		echo $dblink->error, "\n";
		die();
	}
}

function Upgrade()
{
	global $dbname, $dbpref, $installationPath;

	//Load the board tables.
	include($installationPath . "/installSchema.php");

	//Allow plugins to add their own tables!
	if (NumRows(Query("show table status from $dbname like '{enabledplugins}'")))
	{
		$rPlugins = Query("select * from {enabledplugins}");
		while($plugin = Fetch($rPlugins))
		{
			$plugin = $plugin["plugin"];
			$path = $installationPath . "/plugins/$plugin/installSchema.php";
			if(file_exists($path))
				include($path);
		}
	}

	foreach($tables as $table => $tableSchema)
	{
		print "<li>";
		print $dbpref.$table."&hellip;";
		$tableStatus = Query("show table status from $dbname like '{".$table."}'");
		$numRows = NumRows($tableStatus);
		if($numRows == 0)
		{
			print " creating&hellip;";
			$create = "create table `{".$table."}` (\n";
			$comma = "";
			foreach($tableSchema['fields'] as $field => $type)
			{
				$create .= $comma."\t`".$field."` ".$type;
				$comma = ",\n";
			}
			if(isset($tableSchema['special']))
				$create .= ",\n\t".$tableSchema['special'];
			$create .= "\n) ENGINE=MyISAM;";
			//print "<pre>".$create."</pre>";
			Query($create);
		}
		else
		{
			$primaryKey = "";
			$changes = 0;
			$foundFields = array();
			$scan = Query("show columns from `{".$table."}`");
			while($field = $scan->fetch_assoc())
			{
				$fieldName = $field['Field'];
				$foundFields[] = $fieldName;
				$type = $field['Type'];
				if($field['Null'] == "NO")
					$type .= " NOT NULL";
				//if($field['Default'] != "")
				if($field['Extra'] == "auto_increment")
					$type .= " AUTO_INCREMENT";
				else
					$type .= " DEFAULT '".$field['Default']."'";
				if($field['Key'] == "PRI")
					$primaryKey = $fieldName;
				if(array_key_exists($fieldName, $tableSchema['fields']))
				{
					$wantedType = $tableSchema['fields'][$fieldName];
					if(strcasecmp($wantedType, $type))
					{
						print " \"".$fieldName."\" not correct type: was $type, wanted $wantedType &hellip;<br>";
						if($fieldName == "id")
						{
							print_r($field);
							print "{ ".$type." }";
						}
						Query("ALTER TABLE {".$table."} CHANGE `$fieldName` `$fieldName` $wantedType");
						$changes++;
					}
				}
			}
			foreach($tableSchema['fields'] as $fieldName => $type)
			{
				if(!in_array($fieldName, $foundFields))
				{
					print " \"".$fieldName."\" missing&hellip;";
					Query("ALTER TABLE {".$table."} ADD `$fieldName` $type");
					$changes++;
				}
			}
			$newindexes = array();
			preg_match_all('@((primary|unique|fulltext)\s*)?key\s+(`(\w+)`\s+)?\(([\w`,\s]+)\)@si', $tableSchema['special'], $idxs, PREG_SET_ORDER);
			foreach ($idxs as $idx)
			{
				$name = $idx[4] ? $idx[4] : 'PRIMARY';
				$newindexes[$name]['type'] = $idx[2];
				$newindexes[$name]['fields'] = preg_replace('@\s+@s', '', $idx[5]);
			}
			$curindexes = array();
			$idxs = Query("SHOW INDEX FROM `{".$table."}`");
			while ($idx = Fetch($idxs))
			{
				$name = $idx['Key_name'];

				if ($name == 'PRIMARY')
					$curindexes[$name]['type'] = 'primary';
				else if ($idx['Non_unique'] == 0)
					$curindexes[$name]['type'] = 'unique';
				else if ($idx['Index_type'] == 'FULLTEXT')
					$curindexes[$name]['type'] = 'fulltext';
				else
					$curindexes[$name]['type'] = '';

				$curindexes[$name]['fields'] = ($curindexes[$name]['fields'] ? $curindexes[$name]['fields'].',' : '').'`'.$idx['Column_name'].'`';
			}
			if (!compareIndexes($curindexes, $newindexes))
			{
				$changes++;
				print "<br>Recreating indexes...<br>";
				foreach ($curindexes as $name=>$idx)
				{
					if ($newindexes[$name]['type'] == $idx['type'] && $newindexes[$name]['fields'] == $idx['fields'])
					{
						unset($newindexes[$name]);
						continue;
					}

					print " - removing index {$name} ({$idx['type']}, {$idx['fields']})<br>";
					if ($idx['type'] == 'primary')
						Query("ALTER TABLE `{".$table."}` DROP PRIMARY KEY");
					else
						Query("ALTER TABLE `{".$table."}` DROP INDEX `".$name."`");
				}
				foreach ($newindexes as $name=>$idx)
				{
					print " - adding index {$name} ({$idx['type']}, {$idx['fields']})<br>";
					if ($idx['type'] == 'primary')
						$add = 'PRIMARY KEY';
					else if ($idx['type'] == 'unique')
						$add = 'UNIQUE `'.$name.'`';
					else if ($idx['type'] == 'fulltext')
						$add = 'FULLTEXT `'.$name.'`';
					else
						$add = 'INDEX `'.$name.'`';

					Query("ALTER TABLE `{".$table."}` ADD ".$add." (".$idx['fields'].")");
				}
			}
			if($changes == 0)
				print " OK.";
		}
		print "</li>";
	}
}

function compareIndexes($a, $b)
{
	if (count($a) != count($b)) return false;

	foreach ($b as $k=>$v)
	{
		if (!array_key_exists($k, $a)) return false;
	}

	foreach ($a as $k=>$v)
	{
		if (!array_key_exists($k, $b)) return false;
		if ($v['type'] != $b[$k]['type'] ) return false;
		if ($v['fields'] != $b[$k]['fields']) return false;
	}

	return true;
}
