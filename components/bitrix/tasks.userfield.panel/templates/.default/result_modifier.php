<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['HELPER'] = new \Bitrix\Tasks\UI\Component\TemplateHelper('UserFieldPanel', $this, array(
	'RELATION' => array('tasks_util', 'tasks_util_draganddrop', 'popup', 'tasks_util_scrollpane', 'tasks_util_itemset')
));

if($arResult['HELPER']->checkHasFatals())
{
	return;
}

$types = $arResult['AUX_DATA']['FIELD_TYPE'];

// $arResult['JS_DATA'] will appear in js controller as options,
// so be careful, do not publicise smth insecure
$arResult['JS_DATA']['typesToCreate'] = array(
	'string' => $types['string']['DESCRIPTION'],
	'double' => $types['double']['DESCRIPTION'],
	'datetime' => $types['datetime']['DESCRIPTION'],
	'boolean' => $types['boolean']['DESCRIPTION'],
);
$publicScheme = array();
$id2code = array();
foreach($arResult['DATA']['FIELDS'] as $code => $uf)
{
	$id = $uf['ID'];

	if(!isset($arResult['DATA']['STATE'][$id]) || in_array($code, $arParams['EXCLUDE']))
	{
		continue;
	}

	$state = $arResult['DATA']['STATE'][$id];
	$publicScheme[$uf['ID']] = array(
		'ID' => $uf['ID'],
		'CODE' => $code,
		'NAME' => $uf['FIELD_NAME'],
		'MANDATORY' => $uf['MANDATORY'] == 'Y',
		'USER_TYPE_ID' => $uf['USER_TYPE_ID'],
		'MULTIPLE' => $uf['MULTIPLE'] == 'Y',
		'LABEL' => (string) $uf['LABEL'] == '' ? $uf['FIELD_NAME'] : $uf['LABEL'],
		'STATE' => $state,

		'NO_FIELD_HTML' => !$state['D'], // to tell js to pre-load field html when loading
	);

	$id2code[$id] = $code;
}

$arResult['TEMPLATE_DATA']['CAN_EDIT'] = $arResult['AUX_DATA']['USER']['IS_SUPER'];
$arResult['TEMPLATE_DATA']['CAN_USE'] = $arResult['TEMPLATE_DATA']['CAN_EDIT'] || count($id2code);

$arResult['JS_DATA']['scheme'] = $publicScheme;
$arResult['JS_DATA']['entityCode'] = $arParams['ENTITY_CODE'];
$arResult['JS_DATA']['relatedEntities'] = $arParams['RELATED_ENTITIES'];
$arResult['JS_DATA']['entityId'] = $arParams['DATA']['ID'];
$arResult['JS_DATA']['inputPrefix'] = $arParams['INPUT_PREFIX'];
$arResult['JS_DATA']['restriction'] = $arResult['COMPONENT_DATA']['RESTRICTION'];
$arResult['JS_DATA']['defaceable'] = array(
	'employee', 'crm', 'crm_status', 'disk_file', 'video', 'hlblock', 'string', 'integer', 'double', 'datetime', 'date', 'boolean', 'enumeration', 'iblock_section', 'iblock_element'
);

$arResult['TEMPLATE_DATA']['ID2CODE'] = $id2code;