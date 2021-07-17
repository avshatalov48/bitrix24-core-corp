<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

use Bitrix\Sale\Delivery\Services\EmptyDeliveryService;

/**
 * Class Factory
 * @package Bitrix\SalesCenter\Delivery\Handlers
 */
class Factory
{
	/**
	 * @param string $className
	 * @return HandlerContract|null
	 */
	public static function make(string $className)
	{
		switch($className)
		{
			case '\\' . \Sale\Handlers\Delivery\YandextaxiHandler::class:
				return new YandexTaxi();
				break;
			case '\\' . EmptyDeliveryService::class:
				return new NoDelivery();
				break;
		}

		return null;
	}

	/**
	 * @param string $code
	 * @return HandlerContract|RestDelivery
	 */
	public static function makeRest(string $code)
	{
		return new RestDelivery($code);
	}
}
