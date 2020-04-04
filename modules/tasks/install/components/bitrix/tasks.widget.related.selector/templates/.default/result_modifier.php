<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');

$this->__component->tryParseEnumerationParameter($arParams['DISPLAY'], array('inline', 'block'), 'block');
$this->__component->tryParseIntegerParameter($arParams['MAX_WIDTH'], 0);
$this->__component->tryParseStringParameter($arParams['INPUT_PREFIX'], '');
$this->__component->tryParseStringParameter($arParams['SOLE_INPUT_TASK_POSTFIX'], '');
$this->__component->tryParseStringParameter($arParams['SOLE_INPUT_TASK_TEMPLATE_POSTFIX'], '');
$this->__component->tryParseBooleanParameter($arParams['READ_ONLY'], false);
$this->__component->tryParseBooleanParameter($arParams['SOLE_INPUT_IF_MAX_1'], false);

$ids = array();
foreach($arParams['DATA'] as $k => $task)
{
	$ids[] = $task['ID'];
}

$arResult['TEMPLATE_DATA']['IDS'] = $ids;
$pathTask = str_replace(array('#action#', '#ACTION#'), 'view', $helper->findParameterValue('PATH_TO_TASKS_TASK'));
$pathTaskTemplate = str_replace(array('#action#', '#ACTION#'), 'view', $helper->findParameterValue('PATH_TO_TEMPLATES_TEMPLATE'));

// unify id placeholder
$pathTask = str_replace(array('#task_id#', '#TASK_ID#'), '#id#', $pathTask);
$pathTaskTemplate = str_replace(array('#template_id#', '#TEMPLATE_ID#'), '#id#', $pathTaskTemplate);

$arResult['TEMPLATE_DATA']['PATH_TO_TASKS_TASK'] = $pathTask;
$arResult['TEMPLATE_DATA']['PATH_TO_TEMPLATES_TEMPLATE'] = $pathTaskTemplate;

// parse data, define additional fields for server-side rendering
// see: BX.Tasks.UserItemSet.prepareData, BX.Tasks.UserItemSet.extractItemValue, BX.Tasks.UserItemSet.extractItemDisplay
// for client-side implementation of the same code
$data = array();
foreach($arParams['DATA'] as $i => $item)
{
	$arParams['DATA'][$i]['ENTITY_TYPE'] = $item['ENTITY_TYPE'] == 'TT' ? 'TT' : 'T';

	$arParams['DATA'][$i]['DISPLAY'] = $item['TITLE'];
	$arParams['DATA'][$i]['VALUE'] = $arParams['DATA'][$i]['ENTITY_TYPE'].$item['ID'];
	$arParams['DATA'][$i]['ITEM_SET_INVISIBLE'] = '';

	if($item['ENTITY_TYPE'] == 'TT')
	{
		$url = str_replace('#id#', $item['ID'], $pathTaskTemplate);
	}
	else
	{
		$url = str_replace('#id#', $item['ID'], $pathTask);
	}

	$arParams['DATA'][$i]['URL'] = $url;
}

$arResult['JS_DATA'] = array(
	'types' => $arParams['TYPES'],
	'data' => $arParams['DATA'],
	'min' => $arParams['MIN'],
	'max' => is_infinite($arParams['MAX']) ? 99999 : $arParams['MAX'],
	'selectorCodeTask' => "taskSelector".md5($helper->getId()),
	'selectorCodeTaskTemplate' => "templateSelector".md5($helper->getId()),
	'pathTask' => $arResult['TEMPLATE_DATA']['PATH_TO_TASKS_TASK'],
	'pathTaskTemplate' => $arResult['TEMPLATE_DATA']['PATH_TO_TEMPLATES_TEMPLATE'],
	'inputSpecial' => $arParams['SOLE_INPUT_IF_MAX_1'] && $arParams['MAX'] == 1,
	'inputPrefix' => $arParams['INPUT_PREFIX'],
	'inputPostfixTask' => $arParams['SOLE_INPUT_TASK_POSTFIX'],
	'inputPostfixTaskTemplate' => $arParams['SOLE_INPUT_TASK_TEMPLATE_POSTFIX'],
);