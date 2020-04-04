<?
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type\ArrayOption;
use Bitrix\Tasks\Util\Type\StructureChecker;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Integration\CRM;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
return new \Bitrix\Tasks\UI\Component\TemplateHelper(null, (isset($this)? $this : null), array(
	'RELATION' => array('tasks_util', 'tasks_util_itemset'),
	'METHODS' => array(
		'templateActionGetSelector' => function($type, array $parameters)
		{
			$componentParameters = array();
			if(is_array($parameters['COMPONENT_PARAMETERS']))
			{
				$componentParameters = $parameters['COMPONENT_PARAMETERS'];
			}

			$componentParameters = array_merge(array_intersect_key($componentParameters, array_flip(array(
				// component parameter white-list place here
				'MULTIPLE',
				'NAME',
				'VALUE',
			))), array(
				// component force-to parameters place here
				'SITE_ID' => SITE_ID,
			));

			return \Bitrix\Tasks\Dispatcher\PublicAction::getComponentHTML(
				"bitrix:tasks.".($type == 'T' ? 'task' : 'template').".selector",
				"",
				$componentParameters
			);
		},
	),
));