<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$helper = new \Bitrix\Tasks\UI\Component\TemplateHelper('TasksReportEffective', $this, array(
	'RELATION' => array('tasks_util', /*etc*/),
	'METHODS' => array(), // this methods will be accessible via "__call()"
));

return $helper;