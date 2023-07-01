<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

//use Bitrix\Main\Localization\Loc;
//
//Loc::loadMessages(__FILE__);
use Bitrix\Tasks\Util\Type;

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetTagSelectorComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		if(!Type::isIterable($this->arParams['DATA']))
		{
			$this->arParams['DATA'] = array();
		}

		if (
			!array_key_exists('TASK_ID', $this->arParams)
			|| !$this->arParams['TASK_ID']
			|| !is_integer($this->arParams['TASK_ID'])
		)
		{
			$this->arParams['TASK_ID'] = 0;
		}

		$this->arResult['GROUP_ID'] = (int)($this->arParams['GROUP_ID'] ?? null);
		$this->arResult['IS_SCRUM_TASK'] = (($this->arParams['IS_SCRUM_TASK'] ?? null) ? 'Y' : 'N');

		return $this->errors->checkNoFatals();
	}
}
