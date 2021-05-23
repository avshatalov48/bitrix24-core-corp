<?
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type\ArrayOption;
use Bitrix\Tasks\Util\Type\StructureChecker;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Integration\CRM;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$helper = new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	'RELATION' => array(
		'tasks_util',
		'tasks_util_draganddrop',
		'tasks_util_itemset',
		'tasks_util_template',
		'tasks_util_widget'
	),
));

return $helper;