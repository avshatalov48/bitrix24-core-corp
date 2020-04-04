<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

//use Bitrix\Main\Localization\Loc;
//
//Loc::loadMessages(__FILE__);

use Bitrix\Tasks\Util\Type;

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetRelatedSelectorComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		$this->arParams['DATA'] = Type::normalizeArray($this->arParams['DATA']);
		static::tryParseIntegerParameter($this->arParams['MIN'], 0);
		static::tryParseIntegerParameter($this->arParams['MAX'], INF);

		$supportedTypes = array('TASK', 'TASK_TEMPLATE');
		static::tryParseArrayParameter($this->arParams['TYPES'], $supportedTypes);
		$this->arParams['TYPES'] = array_map(function(){return true;}, array_flip(array_intersect($this->arParams['TYPES'], $supportedTypes)));

		return $this->errors->checkNoFatals();
	}
}