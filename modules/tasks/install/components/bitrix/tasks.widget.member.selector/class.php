<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

//use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Type;

//Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetMemberSelectorComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		if(!Type::isIterable($this->arParams['DATA']))
		{
			$this->arParams['DATA'] = array();
		}

		static::tryParseArrayParameter($this->arParams['INPUT_TEMPLATE_SET']);
		static::tryParseIntegerParameter($this->arParams['MIN'], 0);
		static::tryParseIntegerParameter($this->arParams['MAX'], 99999);

		static::tryParseStringParameter($this->arParams['CHECK_ABSENCE'], 'Y');

		$supportedTypes = array('USER', 'USER.EXTRANET', 'USER.MAIL', 'PROJECT', 'DEPARTMENT');
		static::tryParseArrayParameter($this->arParams['TYPES'], $supportedTypes);
		$this->arParams['TYPES'] = array_map(function(){return true;}, array_flip(array_intersect($this->arParams['TYPES'], $supportedTypes)));

		$supportedAttributes = array('ID', 'NAME', 'LAST_NAME', 'EMAIL', 'VALUE');
		static::tryParseArrayParameter($this->arParams['ATTRIBUTE_PASS'], $supportedAttributes);
		$this->arParams['ATTRIBUTE_PASS'] = array_intersect($this->arParams['ATTRIBUTE_PASS'], $supportedAttributes);

		return $this->errors->checkNoFatals();
	}
}