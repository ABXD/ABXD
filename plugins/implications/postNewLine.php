<?php
if ($this->stream->data[$this->stream->char] === '>')
{
	$this->emitToken(array(
		'name' => 'span',
		'type' => self::STARTTAG,
		'attr' => array(
			array(
				'name'  => 'class',
				'value' => 'implication',
			),
			array(
				'name'  => 'style',
				'value' => 'color:green',
			),
		),
	));
	$implications_new_line = true;
}
