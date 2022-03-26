<?php

namespace Bitrix\Mobile\Controller\Catalog;

use Bitrix\Main\Localization\Loc;

trait CatalogPermissions
{
	/**
	 * @return bool
	 */
	private function hasWritePermissions(): bool
	{
		return $this->getCurrentUser()->canDoOperation('catalog_store');
	}

	/**
	 * @return bool
	 */
	private function hasReadPermissions(): bool
	{
		return $this->getCurrentUser()->canDoOperation('catalog_read');
	}

	/**
	 * @return string
	 */
	private function getInsufficientPermissionsError(): string
	{
		return Loc::getMessage("MOBILE_CONTROLLER_CATALOG_PERMISSIONS_ACCESS_DENIED");
	}
}
