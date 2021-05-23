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

		return $this->errors->checkNoFatals();
	}
}