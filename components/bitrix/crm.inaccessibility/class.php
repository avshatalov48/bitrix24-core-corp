<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Integration\Intranet\ToolsManager;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

if (!Loader::includeModule('crm'))
{
	ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));

	return;
}

class CrmInaccessibilityComponent extends CBitrixComponent
{
	public function executeComponent()
	{
		$this->prepareParams();

		$this->includeComponentTemplate();
	}

	private function prepareParams(): void
	{
		$entityTypeId = $this->arParams['ENTITY_TYPE_ID'] ?? null;
		$sliderCode = $this->arParams['SLIDER_CODE'] ?? null;

		$availabilityManager = AvailabilityManager::getInstance();

		$locationHref = Container::getInstance()->getRouter()->getDefaultRoot();
		if ($entityTypeId && \CCrmOwnerType::isCorrectEntityTypeId($entityTypeId))
		{
			$script = $availabilityManager->getEntityTypeAvailabilityLock($entityTypeId);
		}
		elseif ($sliderCode && $sliderCode === ToolsManager::DYNAMIC_SLIDER_CODE)
		{
			$script = $availabilityManager->getDynamicAvailabilityLock();
		}
		elseif ($sliderCode && $sliderCode === ToolsManager::EXTERNAL_DYNAMIC_SLIDER_CODE)
		{
			$script = $availabilityManager->getExternalDynamicAvailabilityLock();
		}
		elseif ($sliderCode && $sliderCode === ToolsManager::REPORTS_CONSTRUCT_SLIDER_CODE)
		{
			$script = $availabilityManager->getReportsConstructAvailabilityLock();
		}
		elseif ($sliderCode && $sliderCode === ToolsManager::QUOTE_SLIDER_CODE)
		{
			$script = $availabilityManager->getEntityTypeAvailabilityLock(\CCrmOwnerType::Quote);
		}
		elseif ($sliderCode && $sliderCode === ToolsManager::INVOICE_SLIDER_CODE)
		{
			$script = $availabilityManager->getEntityTypeAvailabilityLock(\CCrmOwnerType::SmartInvoice);
		}
		elseif ($sliderCode && $sliderCode === ToolsManager::ROBOTS_SLIDER_CODE)
		{
			$script = $availabilityManager->getRobotsAvailabilityLock();
		}
		elseif ($sliderCode && $sliderCode === ToolsManager::BIZPROC_SLIDER_CODE)
		{
			$script = $availabilityManager->getBizprocAvailabilityLock();
		}
		elseif ($sliderCode && $sliderCode === ToolsManager::TERMINAL_SLIDER_CODE)
		{
			$script = $availabilityManager->getTerminalAvailabilityLock();
		}
		else
		{
			$script = $availabilityManager->getCrmAvailabilityLock();
			$locationHref = '/';
		}

		$this->arResult['sliderScript'] = $script;
		$this->arResult['locationHref'] = $locationHref;
	}
}
