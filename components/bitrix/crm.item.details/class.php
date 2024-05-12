<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Component\EntityDetails\FactoryBased;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('crm');

class CrmItemDetailsComponent extends FactoryBased
{
	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$this->executeBaseLogic();

		$this->includeComponentTemplate();
	}

	protected function getDeleteMessage(): string
	{
		return (string)Loc::getMessage('CRM_TYPE_ITEM_DELETE');
	}
}
