<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$control_id = $arParams['CONTROL_ID'];
?>
<div class="bx-ius-layout">
<?
$APPLICATION->IncludeComponent('bitrix:main.lookup.input', 'users', array(
	'CONTROL_ID' => $control_id,
	
	'INPUT_NAME' => $arParams['~INPUT_NAME'],
	'INPUT_NAME_STRING' => $arParams['~INPUT_NAME_STRING'],
	'INPUT_VALUE_STRING' => $arParams['~INPUT_VALUE_STRING'],
	'INPUT_NAME_SUSPICIOUS' => $arParams['~INPUT_NAME_SUSPICIOUS'],
	
	'MULTIPLE' => $arParams['MULTIPLE'],
	'START_TEXT' => GetMessage('IUS_START_TEXT'),
	'TEXTAREA_MAX_HEIGHT' => $arParams['~TEXTAREA_MAX_HEIGHT'],
	'TEXTAREA_MIN_HEIGHT' => $arParams['~TEXTAREA_MIN_HEIGHT'],
	
	//These params will go throught ajax call to ajax.php in template
	"SOCNET_GROUP_ID" => $arParams["~SOCNET_GROUP_ID"],
	"EXTERNAL" => $arParams["EXTERNAL"],
	'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	
), $component, array('HIDE_ICONS' => 'Y'));
?>
<br />
<?
if ($arParams['EXTERNAL'] == 'I' && intval($arParams["SOCNET_GROUP_ID"]) <= 0):

	$name = $APPLICATION->IncludeComponent(
		'bitrix:intranet.user.search',
		'.default',
		array(
			'ONSELECT' => 'jsMLI_'.$control_id.'.AddValue',
			'MULTIPLE' => $arParams['MULTIPLE'],
			'SHOW_BUTTON' => 'N',
			'GET_FULL_INFO' => 'Y',
			'NAME_TEMPLATE' => $arParams["NAME_TEMPLATE"]
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
?>
	<a href="javascript:void(0)" onclick="<?=$name?>.SetValue([]); <?=$name?>.Show()" class="bx-ius-structure-link"><?echo GetMessage('IUS_STRUCT_BUTTON')?></a>
<?
endif;
?>
</div>