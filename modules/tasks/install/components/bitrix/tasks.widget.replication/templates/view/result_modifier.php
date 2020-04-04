<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = new \Bitrix\Tasks\UI\Component\TemplateHelper('TasksWidgetReplicationView', $this, array(
	'RELATION' => array('tasks_util'),
));
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

$this->__component->tryParseBooleanParameter($arParams['ENABLE_SYNC'], false);
$this->__component->tryParseBooleanParameter($arParams['ENABLE_TEMPLATE_LINK'], true);
$this->__component->tryParseIntegerParameter($arParams['ENTITY_ID'], 0, true);
$this->__component->tryParseBooleanParameter($arParams['REPLICATE'], false);

$arParams['PATH_TO_TEMPLATES_TEMPLATE'] = \Bitrix\Tasks\UI\Task\Template::makeActionUrl($helper->findParameterValue('PATH_TO_TEMPLATES_TEMPLATE'), $arParams['ENTITY_ID'], 'view');

$arResult['JS_DATA'] = array(
	'enabled' => $arParams['REPLICATE'],
	'entityId' => $arParams['ENTITY_ID'],
	'enableSync' => $arParams['ENABLE_SYNC'],
);