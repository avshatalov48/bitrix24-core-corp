<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');
$arParams =& $helper->getComponent()->arParams;

$this->__component->tryParseStringParameter($arParams['INPUT_PREFIX'], '');
$this->__component->tryParseBooleanParameter($arParams["COMPATIBILITY_MODE"], false);
$this->__component->tryParseBooleanParameter($arParams['ENABLE_SYNC'], false);
$this->__component->tryParseStringParameter($arParams['ENTITY_ROUTE'], ''); // todo: more strict check here
$this->__component->tryParseIntegerParameter($arParams['ENTITY_ID'], true, 0);
$this->__component->tryParseBooleanParameter($arParams['CAN_REORDER'], true);

$k = 1;
$total = 0;
$checked = 0;

foreach ($arParams['DATA'] as $i => &$item)
{
	$title = \Bitrix\Tasks\UI::convertBBCodeToHtmlSimple(htmlspecialcharsbx($item['TITLE']));
	$item['TITLE'] = str_replace('&nbsp;', ' ', $title);
	$item['DISPLAY'] = htmlspecialcharsBack($title); // textParser returns escaped data, we want unescaped

	if($item['ID'])
	{
		$item['VALUE'] = $item['ID'];
	}
	else
	{
		$item['VALUE'] = 'n'.abs(Bitrix\Tasks\Util::hashCode(rand(100, 999).rand(100, 999)));
	}

	// flatterize to make templates work
	$item['ACTION_UPDATE'] = (array_key_exists('MODIFY', $item['ACTION'])? $item['ACTION']['MODIFY'] : $item['ACTION']['UPDATE']);
	$item['ACTION_DELETE'] = (array_key_exists('REMOVE', $item['ACTION'])? $item['ACTION']['REMOVE'] : $item['ACTION']['DELETE']);
	$item['ACTION_TOGGLE'] = (array_key_exists('TOGGLE', $item['ACTION'])? $item['ACTION']['TOGGLE'] : $item['ACTION']['TOGGLE']);
	unset($item['ACTION']);

	$isSeparator = \Bitrix\Tasks\UI\Task\CheckList::checkIsSeparatorValue($item['TITLE']);
	if (!$isSeparator)
	{
		$total++;
		$item['NUMBER'] = $k++;
	}

	$item['APPEARANCE'] = ($isSeparator? 'a-separator' : 'a-generic');
	$item['READONLY'] = ($item['ACTION_UPDATE']? '' : 'noedit');
	$item['CHECKED_ATTRIBUTE'] = ($item['CHECKED']? 'checked' : '');
	$item['DISABLED_ATTRIBUTE'] = ($item['ACTION_TOGGLE']? '' : 'disabled');
	$item['STROKE_CSS'] = ($item['CHECKED']? 'stroke-out' : '');
	$item['ITEM_SET_INVISIBLE'] = '';
	$item['CAN_SHOW_URL'] = 'Y';
	$item['IS_COMPLETE'] = ($item['CHECKED']? 'Y' : 'N');
	$item['SORT_INDEX'] = $item['SORT'];

	if ($item['CHECKED'])
	{
		$checked++;
	}
}
unset($item);

$arResult['TEMPLATE_DATA']['COUNTER_CHECKED'] = $checked;
$arResult['TEMPLATE_DATA']['COUNTER_TOTAL'] = $total;

$sortField = 'SORT';
$checkedField = 'CHECKED';

if ($arParams['COMPATIBILITY_MODE'])
{
	$sortField = 'SORT_INDEX';
	$checkedField = 'IS_COMPLETE';
}

$arResult['TEMPLATE_DATA']['FIELDS'] = array(
	'SORT' => $sortField,
	'CHECKED' => $checkedField,
);

$arResult['JS_DATA'] = array(
	'data' => $arParams['DATA'],
	'entityId' => $arParams['ENTITY_ID'],
	'entityRoute' => $arParams['ENTITY_ROUTE'],
	'confirmDelete' => $arParams['CONFIRM_DELETE'],
	'compatibilityMode' => $arParams['COMPATIBILITY_MODE'],
	'enableSync' => $arParams['ENABLE_SYNC']
);