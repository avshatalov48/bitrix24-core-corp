<?php

namespace Bitrix\Crm\Order\EventsHandler;

use Bitrix\Crm\Order;
use Bitrix\Main;
use Bitrix\Sale\Cashbox;

Main\Loader::includeModule('sale');

final class Check
{
	/**
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function OnCheckCollateDocuments(Main\Event $event) : Main\EventResult
	{
		$entities = $event->getParameter('ENTITIES');
		if (is_array($entities))
		{
			foreach ($entities as $entity)
			{
				if (
					$entity instanceof Order\Payment
					&&
					(
						Cashbox\Manager::canPaySystemPrint($entity)
						||
						(
							$entity->getFields()->isChanged('PAID')
							&& $entity->isPaid()
						)
					)
				)
				{
					return new Main\EventResult(
						Main\EventResult::SUCCESS,
						[
							[
								'TYPE' => Cashbox\SellCheck::getType(),
								'ENTITIES' => [$entity],
								'RELATED_ENTITIES' => []
							]
						]
					);
				}
			}
		}

		return new Main\EventResult(Main\EventResult::ERROR);
	}
}