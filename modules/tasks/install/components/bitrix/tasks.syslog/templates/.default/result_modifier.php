<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	//'RELATION' => array('tasks_util'),
));
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if($helper->checkHasFatals())
{
	return;
}

// you may parse some additional template parameters
$this->__component->tryParseURIParameter($arParams['PARAM1'], '');
$this->__component->tryParseIntegerParameter($arParams['PARAM2'], 0, true);
$this->__component->tryParseBooleanParameter($arParams['PARAM3'], false);

$arResult['TEMPLATE_DATA'] = array(
	// contains data generated in result_modifier.php
);
$arResult['JS_DATA'] = array(
	// everything you put here, will be accessible inside js controller through this.option('keyName')
);