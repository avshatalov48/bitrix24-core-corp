<?php

use Bitrix\Crm\Component\Base;
use Bitrix\Crm\Security\Role\Manage\RoleManagerSelectionFactory;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm'))
{
	ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

class CrmConfigPermsWrapper extends Base
{
	public function executeComponent(): void
	{
		$this->arResult['componentParams'] = $this->arParams;
		$this->arResult['backUrl'] = $this->getSliderBackUrl();

		$this->IncludeComponentTemplate();
	}

	private function getSliderBackUrl(): ?Uri
	{
		$criterion = $this->arParams['criterion'] ?? null;
		$isAutomation = $this->arParams['isAutomation'] ?? false;

		$isExternal = $this->arParams['isExternal'] ?? false;
		$customSectionCode = $isExternal
			? $this->arParams['sectionCode'] ?? null
			: null
		;

		$manager = (new RoleManagerSelectionFactory())
			->setCustomSectionCode($customSectionCode)
			->setAutomation($isAutomation)
			->create($criterion)
		;

		if ($manager === null || !$manager->isAvailableTool())
		{
			return null;
		}

		return $manager->getSliderBackUrl();
	}
}
