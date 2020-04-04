<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	'RELATION' => array('tasks_util', /*etc*/)
));

$this->__component->tryParseStringParameter($arParams['INPUT_PREFIX'], '');

// $arResult['TEMPLATE_DATA'] // contains data generated in result_modifier.php
// $arResult['JS_DATA'] // everything you put here, will be accessible inside js controller through this.option('keyName')