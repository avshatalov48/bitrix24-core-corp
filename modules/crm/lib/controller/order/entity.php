<?php

namespace Bitrix\Crm\Controller\Order;

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Catalog;

Main\Loader::requireModule('catalog');
Main\Loader::requireModule('sale');

abstract class Entity extends Main\Engine\Controller
{
	protected bool $automationWasTemporarilyDisabled = false;

	protected function temporarilyDisableAutomationIfNeeded(): void
	{
		$isUsedInventoryManagement = Catalog\Config\State::isUsedInventoryManagement();
		$isAutomationEnabled = Sale\Configuration::isEnableAutomaticReservation();

		if ($isUsedInventoryManagement && $isAutomationEnabled)
		{
			Sale\Configuration::disableAutomaticReservation();
			$this->automationWasTemporarilyDisabled = true;
		}
	}

	protected function restoreDisabledAutomationIfNeeded(): void
	{
		if ($this->automationWasTemporarilyDisabled)
		{
			Sale\Configuration::enableAutomaticReservation();
		}
	}
}
