<?php

namespace Bitrix\Mobile\Controller\Catalog;

use Bitrix\Main\Localization\Loc;

trait CatalogPermissions
{
	/**
	 * @return string
	 */
	private function getInsufficientPermissionsError(): string
	{
		return Loc::getMessage('MOBILE_CONTROLLER_CATALOG_PERMISSIONS_ACCESS_DENIED');
	}
}
