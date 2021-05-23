<?if(!Defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$control_id = $arParams['CONTROL_ID'];
?>
<div class="bx-ius-layout">
<?
$APPLICATION->IncludeComponent(
	'bitrix:main.lookup.input', 
	'crm_'.ToLower($arParams['ENTITY_TYPE']), 
	array(
		'CONTROL_ID' => $control_id,
		'INPUT_NAME' => $arParams['~INPUT_NAME'],
		'INPUT_NAME_STRING' => $arParams['~INPUT_NAME_STRING'],
		'INPUT_VALUE_STRING' => $arParams['~INPUT_VALUE_STRING'],
		'INPUT_NAME_SUSPICIOUS' => $arParams['~INPUT_NAME_SUSPICIOUS'],
		'MULTIPLE' => $arParams['MULTIPLE'],
		'START_TEXT' => $arParams['START_TEXT'],
		'TEXTAREA_MAX_HEIGHT' => $arParams['~TEXTAREA_MAX_HEIGHT'],
		'TEXTAREA_MIN_HEIGHT' => $arParams['~TEXTAREA_MIN_HEIGHT'],	
	), 
	$component, 
	array('HIDE_ICONS' => 'Y')
);
?>
<?
	$name = $APPLICATION->IncludeComponent(
		'bitrix:crm.entity.search',
		'',
		array(
			'ENTITY_TYPE' => ToLower($arParams['ENTITY_TYPE']),
			'ONSELECT' => 'jsCES_'.$control_id.'.AddValue',
			'MULTIPLE' => $arParams['MULTIPLE'],
			'SHOW_BUTTON' => 'N',
			'GET_FULL_INFO' => 'Y',
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
?>
	<a href="javascript:void(0)" onclick="<?=$name?>.SetValue([]); <?=$name?>.Show()" class="bx-ius-structure-link"><?echo GetMessage('CRM_ES_ADD')?></a>

</div>