<?php

namespace Bitrix\Crm\Reservation;

use Bitrix\Main;

final class InventoryManagementError
{
	public const INVENTORY_MANAGEMENT_ERROR_CODE = 'INVENTORY_MANAGEMENT_ERROR';

	public static function create(): Main\Error
	{
		return new Main\Error(
			Main\Localization\Loc::getMessage('CRM_RESERVATION_INVENTORY_MANAGEMENT_ERROR'),
			self::INVENTORY_MANAGEMENT_ERROR_CODE
		);
	}
}