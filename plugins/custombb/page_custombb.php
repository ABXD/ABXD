<?php

include_once($installationPath . '/plugins/custombb/defines.php');

if(file_exists(BB_FILE)){
	$bbcodes=unserialize(file_get_contents(BB_FILE));
}
else{
	$bbcodes=array();
}

$title = __("Custom BBCode");

$key = hash('sha256', "{$loguserid},{$loguser['pss']},{$salt}");

if((isset($_GET['delete']) || $_GET['id']) && $key != $_GET['key']) {
	Kill(__('Sorry, but no...'));
}

if($loguser['powerlevel'] < 3)
		Kill(__("You're not an administrator. There is nothing for you here."));

$hardcodedbb=array(
	'source','user','code','b','i','u','s','spoiler','url','img','quote','reply',''
);

function checked($value,$what){
	global $bbcodes;
	if($_GET['id'] && $bbcodes[$_GET['id'] - 1][$what] == $value){
		echo ' selected';
	}
}


function prepareBB($bbcode){
	$examples=array(
		BB_TEXT   => 'Lorem ipsum ABXD',
		BB_ID     => 'KzyXH2mWkU4',
		BB_NUMBER => 9001,
		BB_URL    => 'http://abxd.dirbaio.net/',
		BB_COLOR  => '#f00',
	);

	$bb='[' . $bbcode['name'];
	if($bbcode['value'] != BB_NULL){
		$bb .= "={$examples[$bbcode['value']]}";
	}
	$bb .= ']';
	if($bbcode['text'] != BB_NULL){
		$bb .= "{$examples[$bbcode['text']]}[/$bbcode[name]]";
	}
	return $bb;
}

$cell = 1;

if(isset($_GET['delete'])){
	unset($bbcodes[(int) $_GET['id']]);
	Alert(__('BBCode was removed correctly'));
	file_put_contents(BB_FILE,serialize($bbcodes));
}

if(isset($_POST['name'])){
	if(in_array($_POST['name'],$hardcodedbb)){
		Kill(__('This BBCode is hardcoded into board. Sorry...'));
	}
	$prepare=array(
		'name'        => $_POST['name'],
		'value'       => $_POST['value'],
		'text'        => $_POST['text'],
		'category'    => $_POST['category'],
		'description' => $_POST['description'],
		'html'        => $_POST['html'],
	);
	if($_GET['id'])
		$bbcodes[$_GET['id'] - 1] = $prepare;
	else
		$bbcodes[] = $prepare;
	file_put_contents(BB_FILE,serialize($bbcodes));
}

?>
<script>
$(document).ready(function(){
	function makeDisabled(){
		$('#description').attr('disabled', !$('#category').val())
	}
	makeDisabled()
	$('#category').change(function(){
		makeDisabled()
	})
})
</script>
<form action="<?php echo htmlspecialchars(actionLink("custombb", $_GET['id'], "key=$key")); ?>" method=post>
<table class='outline margin'>
	<tr class=header0>
		<th colspan=2>
			<?php
			if($_GET['id'])
				echo __('Modify BBCode');
			else
				echo __('Add new BBCode'); ?>
	<tr class=cell<?php $cell++; $cell%=2; echo $cell; ?>>
		<td style="width:250px">
			<?php echo __('BBCode Name'); ?>
		<td>
			<input type=text name=name value="<?php echo htmlentities($bbcodes[$_GET['id'] - 1]['name']); ?>">
	<tr class=cell<?php $cell++; $cell%=2; echo $cell; ?>>
		<td>
			<?php echo __('Type of value after ='); ?>
		<td>
			<select name=value>
				<option value=0<?php checked(0,'value');?>><?php echo __('Nothing'); ?></option>
				<option value=1<?php checked(1,'value');?>><?php echo __('Full text'); ?></option>
				<option value=2<?php checked(2,'value');?>><?php echo __('ID (for example Youtube)'); ?></option>
				<option value=3<?php checked(3,'value');?>><?php echo __('Number'); ?></option>
				<option value=5<?php checked(5,'value');?>><?php echo __('Color (#f00 or #f00f00)'); ?></option>
			</select>
	<tr class=cell<?php $cell++; $cell%=2; echo $cell; ?>>
		<td>
			<?php echo __('Type of text inside BBCode'); ?>
		<td>
			<select name=text>
				<option value=0<?php checked(0,'text');?>><?php echo __('Nothing'); ?></option>
				<option value=1<?php checked(1,'text');?>><?php echo __('Full text'); ?></option>
				<option value=2<?php checked(2,'text');?>><?php echo __('ID (for example Youtube)'); ?></option>
				<option value=3<?php checked(3,'text');?>><?php echo __('Number'); ?></option>
				<option value=5<?php checked(5,'text');?>><?php echo __('Color (#f00 or #f00f00)'); ?></option>
			</select>
	<tr class=cell<?php $cell++; $cell%=2; echo $cell; ?>>
		<td>
			<?php echo __('Category'); ?>
		<td>
			<select name=category id=category>
				<option value=0<?php checked(0,'category');?>><?php echo __('None'); ?></option>
				<option value=1<?php checked(1,'category');?>><?php echo __('Presentation'); ?></option>
				<option value=2<?php checked(2,'category');?>><?php echo __('Links'); ?></option>
				<option value=3<?php checked(3,'category');?>><?php echo __('Quotations'); ?></option>
				<option value=4<?php checked(4,'category');?>><?php echo __('Embeds'); ?></option>
			</select>
	<tr class=cell<?php $cell++; $cell%=2; echo $cell; ?>>
		<td>
			<?php echo __('Category description'); ?>
		<td>
			<input type=text name=description id=description value="<?php echo htmlentities($bbcodes[$_GET['id'] - 1]['description']); ?>">
	<tr class=cell<?php $cell++; $cell%=2; echo $cell; ?>>
		<td>
			<?php echo __('HTML code inserted by BBCode'); ?> <img src="img/icons/icon5.png" title="<?php echo htmlentities(__('(Type {V} for value and {T} for text - it will be replaced by board.)'));?>"><br>
		<td>
			<textarea style="width:98%" rows=8 name=html><?php echo htmlentities($bbcodes[$_GET['id'] - 1]['html']); ?></textarea>
<tr class=cell<?php $cell++; $cell%=2; echo $cell; ?>>
		<td>
			<?php echo __('Submit'); ?><br>
		<td>
			<input type=submit>
</table>

<input type=hidden value=new>
</form>
<table class='outline margin'>
	<tr class=header0>
		<th colspan=4>
			<?php echo __('Custom BBCode'); ?>
	<tr class=header1>
		<th>
			#
		<th style="width:280px">
			BBCode
		<th>
			<?php echo __('Result'); ?>
		<th style="width:150px">
			<?php echo __('Actions'); ?>

<?php
$cell=0;

foreach($bbcodes as $id=>$bbcode){
$cell %= 2;
echo '<tr class=cell'.$cell.'>';
echo '<td>', $id + 1;
echo '<td>', prepareBB($bbcode);
echo '<td>', htmlentities($bbcode['html']);
echo '<td><a href="', actionLink("custombb", $id + 1, "key=$key"), '">',__('Modify'),
     '</a> | <a href="', actionLink("custombb", $id, "delete=1&key=$key"),
     '" onclick="return confirm(\''.__('Are you sure you want to remove that BBCode?'),
     '\') && confirm(\'', __('Seriously?'), '\')">', __('Delete'), '</a>';
$cell++;
}
?>

</table>
