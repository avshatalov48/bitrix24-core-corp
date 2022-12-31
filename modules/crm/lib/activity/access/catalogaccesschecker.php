<?php

namespace Bitrix\Crm\Activity\Access;

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/**
 * Checker on catalog rights for bizproc activities.
 */
class CatalogAccessChecker
{
	/**
	 * Checks has access current user to catalog, or not.
	 *
	 * @return bool
	 */
	public static function hasAccess(): bool
	{
		return
			Loader::includeModule('catalog')
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
		;
	}

	/**
	 * Callback for dialod renderer.
	 *
	 * @see \Bitrix\Bizproc\Activity\PropertiesDialog::setRenderer
	 *
	 * @return callable
	 */
	public static function getDialogRenderer(): callable
	{
		return static function () {
			$message = Loc::getMessage('CRM_ACTIVITY_ACCESS_CATALOG_ACCESS_CHECKER_ERROR_MESSAGE');

			return "<div class=\"ui-alert ui-alert-danger\">
				<span class=\"ui-alert-message\">{$message}</span>
			</div>";
		};
	}
}
