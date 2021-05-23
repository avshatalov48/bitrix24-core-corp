<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = new \Bitrix\Tasks\UI\Component\TemplateHelper('UtilProcess', $this, array(
	'RELATION' => array('tasks_util', 'tasks_util_etc')
));

// $arResult['TEMPLATE_DATA'] // contains data generated in result_modifier.php
// $arResult['JS_DATA'] // everything you put here, will be accessible inside js controller through this.option('keyName')