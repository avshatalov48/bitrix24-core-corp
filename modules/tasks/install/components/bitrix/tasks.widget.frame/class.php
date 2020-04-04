<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Result;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetFrameComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		static::tryParseStringParameter($this->arParams['FRAME_ID'], '');
		if(!$this->arParams['FRAME_ID'])
		{
			$this->errors->add('ILLEGAL_PARAMETER.FRAME_ID', 'Frame id not set');
		}

		return $this->errors->checkNoFatals();
	}
}