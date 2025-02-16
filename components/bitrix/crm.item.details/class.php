<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Component\EntityDetails\FactoryBased;
use Bitrix\Crm\Integration\Analytics\Dictionary;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loader::includeModule('crm');

class CrmItemDetailsComponent extends FactoryBased
{
	public function executeComponent()
	{
		$this->init();

		if ($this->getErrors())
		{
			if ($this->tryShowCustomErrors())
			{
				return;
			}
			$this->includeComponentTemplate();

			return;
		}

		$this->executeBaseLogic();

		$this->setBizprocStarterConfig();

		$this->includeComponentTemplate();
	}

	protected function getDeleteMessage(): string
	{
		return (string)Loc::getMessage('CRM_TYPE_ITEM_DELETE');
	}

	protected function getExtras(): array
	{
		$extras = parent::getExtras();

		$entityTypeId = $this->getEntityTypeID();
		$section = (
			IntranetManager::isEntityTypeInCustomSection($entityTypeId)
				? Dictionary::SECTION_CUSTOM
				: Dictionary::SECTION_DYNAMIC
		);

		$extras['ANALYTICS'] = [
			'c_section' => $section,
			'c_sub_section' => \Bitrix\Crm\Integration\Analytics\Dictionary::SUB_SECTION_DETAILS,
		];

		return $extras;
	}
}
