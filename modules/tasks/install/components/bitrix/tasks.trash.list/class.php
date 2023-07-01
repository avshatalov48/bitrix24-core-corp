<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Result;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksSkeletonComponent extends TasksBaseComponent
{
	public static function getAllowedMethods()
	{
		// todo
		return array(
			'sampleCreateTask'
		);
	}

	public static function sampleCreateTask($arg)
	{
		$result = new Result();

		// todo

		return $result;
	}

	protected function checkParameters()
	{
		// todo

		/*
		// sample:
		static::tryParseEnumerationParameter($this->arParams['ENTITY_CODE'], array('TASK', 'TASK_TEMPLATE'), false);
		if(!$this->arParams['ENTITY_CODE'])
		{
			$this->errors->add('INVALID_PARAMETER.ENTITY_CODE', 'Unknown entity code');
		}
		static::tryParseArrayParameter($this->arParams['EXCLUDE']);
		*/

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		// todo
		// $this->arResult['COMPONENT_DATA'] // some data related to the component mechanics
		// $this->arResult['AUX_DATA'] // some reference data, data from other modules, etc ...
		// $this->arResult['DATA'] // component primary arResult data
	}
}

if (CModule::IncludeModule('tasks'))
{
	final class TasksSkeletonComponentAdditionalController
	{
		// todo
	}
}