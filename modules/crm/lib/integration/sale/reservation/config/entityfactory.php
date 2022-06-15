<?php

namespace Bitrix\Crm\Integration\Sale\Reservation\Config;

use Bitrix\Crm\Integration\Sale\Reservation\Config\Entity\Invoice;
use Bitrix\Crm\Integration\Sale\Reservation\Config\Entity\Deal;
use Bitrix\Crm\Integration\Sale\Reservation\Config\Entity\Entity;
use Bitrix\Crm\Integration\Sale\Reservation\Config\Entity\Offer;
use Bitrix\Crm\Integration\Sale\Reservation\Config\Entity\SmartProcess;
use Bitrix\Main\SystemException;

/**
 * Class EntityFactory
 *
 * @package Bitrix\Crm\Integration\Sale\Reservation\Config
 */
class EntityFactory
{
	/**
	 * @param string $code
	 * @return Entity
	 * @throws SystemException
	 */
	public static function make(string $code): Entity
	{
		$class = null;

		switch ($code)
		{
			case Deal::CODE:
				$class = Deal::class;
				break;
			case Invoice::CODE:
				$class = Invoice::class;
				break;
			case Offer::CODE:
				$class = Offer::class;
				break;
			case SmartProcess::CODE:
				$class = SmartProcess::class;
				break;
		}

		if (is_null($class))
		{
			throw new SystemException(sprintf('Unknown reservation entity code: %s', $code));
		}

		return new $class();
	}

	/**
	 * @return Entity[]
	 */
	public static function makeAllKnown(): array
	{
		return [
			self::make(Deal::CODE),
		];
	}
}
