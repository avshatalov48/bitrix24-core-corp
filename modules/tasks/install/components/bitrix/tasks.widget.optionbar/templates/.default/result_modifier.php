<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if($helper->checkHasFatals())
{
	return;
}

$this->__component->tryParseStringParameter($arParams['INPUT_PREFIX'], '');

foreach($arParams['OPTIONS'] as $k => $option)
{
	$arParams['OPTIONS'][$k] = array_merge(array(
		'YES_VALUE' => 'Y',
		'NO_VALUE' => 'N',
		'VALUE' => '',
		'DISABLED' => false,
		'HINT_ENABLED' => false,
		'TEXT' => '',
		'HELP_TEXT' => '',
		'HINT_TEXT' => '',
		'HINT_CLASS' => '',
		'FLAG_CLASS' => '',
		'LINK' => array(),
	), $option);

	$option['CODE'] = trim((string) $option['CODE']);
	if(!$option['CODE'])
	{
		unset($arParams['OPTIONS'][$k]);
	}
}

//_print_r($arParams['OPTIONS']);

//$arResult['TEMPLATE_DATA'] = array(
//	// contains data generated in result_modifier.php
//);
//$arResult['JS_DATA'] = array(
//	// everything you put here, will be accessible inside js controller through this.option('keyName')
//);