<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Item\SystemLog;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksSysLogComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		$this->tryParseIntegerParameter($this->arParams['ENTITY_TYPE'], 0);
		if(!$this->arParams['ENTITY_TYPE'])
		{
			$this->errors->add('INVALID_PARAMETER.ENTITY_TYPE', 'Illegal entity type');
		}

		$this->tryParseIntegerParameter($this->arParams['ENTITY_ID'], 0);
		if(!$this->arParams['ENTITY_ID'])
		{
			$this->errors->add('INVALID_PARAMETER.ENTITY_ID', 'Illegal entity id');
		}

		$this->tryParseIntegerParameter($this->arParams['PAGE_SIZE'], 5);

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		// todo: pagenav and lazyload here
		$items = SystemLog::find(array(
			'filter' => array(
				'=ENTITY_TYPE' => $this->arParams['ENTITY_TYPE'],
				'=ENTITY_ID' => $this->arParams['ENTITY_ID'],
			),
			'order' => array(
				'CREATED_DATE' => 'desc'
			),
			'limit' => $this->arParams['PAGE_SIZE'],
		));

		$this->arResult['DATA']['ITEMS'] = $items;

		$this->returnData = array(
			'COUNT' => count($items)
		);
	}
}