<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Result;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetOptionBarComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		if(!is_array($this->arParams['OPTIONS']))
		{
			$this->arParams['OPTIONS'] = array();
		}

		return $this->errors->checkNoFatals();
	}
}