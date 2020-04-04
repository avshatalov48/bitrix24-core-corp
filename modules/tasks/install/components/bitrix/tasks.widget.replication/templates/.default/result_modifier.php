<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	'RELATION' => array('tasks_util', 'tasks', 'tasks_util_datepicker')
));

$this->__component->tryParseStringParameter($arParams['INPUT_PREFIX'], '');

$arResult['JS_DATA']['data'] = $arParams['DATA'];
$arResult['JS_DATA']['tzOffset'] = $arResult['AUX_DATA']['UTC_TIME_ZONE_OFFSET'];
$arResult['JS_DATA']['timerId'] = md5($helper->getId().'TIME');