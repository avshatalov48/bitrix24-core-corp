<?php

namespace Bitrix\Crm\Terminal\EventsHandler;

use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Crm;

class Check
{
	/**
	 * Don't need to print a check for terminal payment
	 *
	 * @param Main\Event $event
	 * @return Main\EventResult|null
	 */
	public static function onCheckCollateDocuments(Main\Event $event): ?Main\EventResult
	{
		$entities = $event->getParameter('ENTITIES');
		if (is_array($entities))
		{
			foreach ($entities as $entity)
			{
				if (
					$entity instanceof Crm\Order\Payment
					&& (
						Container::getInstance()->getTerminalPaymentService()->isTerminalPayment($entity->getId())
						&& !$entity->getOrder()->getEntityBinding()
					)
				)
				{
					return new Main\EventResult(Main\EventResult::ERROR);
				}
			}
		}

		return null;
	}
}
