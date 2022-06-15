<?php

namespace Bitrix\Crm\Integration\Sale\Reservation\Event;

use Bitrix\Sale\Reservation\Configuration\ReservationSettingsBuildEvent;
use CCrmSaleHelper;

/**
 * Handler 'OnReservationSettingsBuild' event.
 *
 * If the reservation settings are enabled in `crm`,
 * then the reservation settings in `sale` will be disabled.
 */
class ReservationSettingsBuildEventHandler
{
	/**
	 * Is enabled reservation automation in `crm`.
	 *
	 * @return bool
	 */
	private static function isEnabledCrmReservation(): bool
	{
		return !CCrmSaleHelper::isWithOrdersMode();
	}

	/**
	 * Event handler.
	 *
	 * @param ReservationSettingsBuildEvent $event
	 *
	 * @return void
	 */
	public static function OnReservationSettingsBuild(ReservationSettingsBuildEvent $event)
	{
		if (self::isEnabledCrmReservation())
		{
			$event->getSettings()->setReserveCondition(null);
		}
	}
}
