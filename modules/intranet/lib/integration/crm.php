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

		return Container::getInstance()->getRouter()->getItemListUrlInCurrentView($entityTypeId, $categoryId);
	}

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

	public function redirectToFirstAvailableEntity(): never
	{
		LocalRedirect($this->getDefaultRedirectUrl());
	}

	public function canReadSomeItemsInCrm(): bool
	{
		return $this->includeCrm() && Container::getInstance()->getUserPermissions()->entityType()->canReadSomeItemsInCrm();
	}

	private function getDefaultRedirectUrl(): string
	{
		$mainPageUrl = '/';
		if (!$this->includeCrm())
		{
			return $mainPageUrl;
		}

		$defaultEntityTypeId = $this->getFirstAvailableEntityTypeId();
		if ($defaultEntityTypeId)
		{
			return Container::getInstance()->getRouter()->getItemListUrl($defaultEntityTypeId);
		}

		return $mainPageUrl;
	}

	private function getFirstAvailableEntityTypeId(): ?int
	{
		$container = Container::getInstance();

		$userPermissions = $container->getUserPermissions();
		if (\Bitrix\Crm\Settings\LeadSettings::isEnabled() && $userPermissions->entityType()->canReadItems(\CCrmOwnerType::Lead))
		{
			return \CCrmOwnerType::Lead;
		}

		$availableEntityTypeIds = [
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Quote,
		];
		foreach ($availableEntityTypeIds as $availableEntityTypeId)
		{
			if ($userPermissions->entityType()->canReadItems($availableEntityTypeId))
			{
				return $availableEntityTypeId;
			}
		}

		$dynamicTypesMap = $container->getDynamicTypesMap();
		$dynamicTypesMap->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);
		foreach ($dynamicTypesMap->getTypes() as $type)
		{
			if ($userPermissions->entityType()->canReadItems($type->getEntityTypeId()))
			{
				return $type->getEntityTypeId();
			}
		}

		return null;
	}
	//endregion
}
