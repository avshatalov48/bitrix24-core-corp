<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

// create template controller with js-dependency injections
$helper = new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, [
	'RELATION' => [
		'tasks_util',
		'tasks_util_draganddrop',
		'tasks_util_template',
	],
	'METHODS' => [],
]);

return $helper;