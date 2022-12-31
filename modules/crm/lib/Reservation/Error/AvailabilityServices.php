<?php

namespace Bitrix\Crm\Reservation\Error;

use Bitrix\Main;

final class AvailabilityServices
{
	public const AVAILABILITY_SERVICES_ERROR_CODE = 'AVAILABILITY_SERVICES_ERROR';

	public static function create(): Main\Error
	{
		return new Main\Error(
			Main\Localization\Loc::getMessage('CRM_RESERVATION_AVAILABILITY_SERVICES_ERROR'),
			self::AVAILABILITY_SERVICES_ERROR_CODE
		);
	}
}