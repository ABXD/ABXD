<?php
if ($implications_new_line)
	$this->emitToken(array(
		'name' => 'span',
		'type' => self::ENDTAG,
	));

$implications_new_line = false;
