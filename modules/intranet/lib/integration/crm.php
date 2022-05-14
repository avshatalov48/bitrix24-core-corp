<?php

namespace Bitrix\Intranet\Integration;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\InvoiceSettings;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Uri;

/**
 * All-in-one facade to crm module.
 * Used to avoid direct calls to different module API.
 * Please, keep it simple and provide only bare minimum that is required for intranet functioning.
 * Too extensive API would be hard to maintain.
 */
final class Crm
{
	private function __construct()
	{
	}

	private function __clone()
	{
	}

	public static function getInstance(): self
	{
		$serviceLocator = ServiceLocator::getInstance();
		$code = 'intranet.integration.crm';

		if (!$serviceLocator->has($code))
		{
			$serviceLocator->addInstance($code, new self());
		}

		return $serviceLocator->get($code);
	}

	private function includeCrm(): bool
	{
		return Loader::includeModule('crm');
	}

	public function getItemListUrlInCurrentView(int $entityTypeId, ?int $categoryId = null): ?Uri
	{
		if (!$this->includeCrm())
		{
			return null;
		}

		if (!$this->isCrmServiceApiAvailable())
		{
			return null;
		}

		return Container::getInstance()->getRouter()->getItemListUrlInCurrentView($entityTypeId, $categoryId);
	}

	//region Permissions
	public function checkReadPermissions(int $entityTypeId, int $id = 0, ?int $categoryId = null, ?int $userId = null): bool
	{
		if (!$this->includeCrm())
		{
			return false;
		}

		if (!$this->isCrmServiceApiAvailable())
		{
			return false;
		}

		return Container::getInstance()->getUserPermissions($userId)->checkReadPermissions($entityTypeId, $id, $categoryId);
	}
	//endregion

	//region Get rid of dependency on crm module updates
	public function isOldInvoicesEnabled(): bool
	{
		if (!$this->includeCrm())
		{
			return false;
		}

		//enabled if the new update with smart invoices is not installed
		$isOldInvoicesEnabled = true;
		if (method_exists(InvoiceSettings::getCurrent(), 'isOldInvoicesEnabled'))
		{
			$isOldInvoicesEnabled = InvoiceSettings::getCurrent()->isOldInvoicesEnabled();
		}

		return $isOldInvoicesEnabled;
	}

	public function isSmartInvoicesEnabled(): bool
	{
		if (!$this->includeCrm())
		{
			return false;
		}

		if (
			!defined(\CCrmOwnerType::class . '::SmartInvoice')
			|| !method_exists(InvoiceSettings::getCurrent(), 'isSmartInvoiceEnabled')
		)
		{
			return false;
		}

		return InvoiceSettings::getCurrent()->isSmartInvoiceEnabled();
	}

	private function isCrmServiceApiAvailable(): bool
	{
		return class_exists('\Bitrix\Crm\Service\Container');
	}
	//endregion
}
