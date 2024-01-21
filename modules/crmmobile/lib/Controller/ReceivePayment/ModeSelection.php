<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Crm\Item;
use Bitrix\Crm\Terminal\AvailabilityManager;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Loader;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\CrmManager;

Loader::requireModule('salescenter');

class ModeSelection extends Base
{
	public function configureActions()
	{
		return [
			'getModeSelectionParamsAction' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getModeSelectionParamsAction(Item $entity): array
	{
		$contactHasPhone = false;
		$entityHasContact = (bool)$entity->getPrimaryContact();
		if ($entityHasContact)
		{
			$contactHasPhone = $entity->getPrimaryContact()->getHasPhone();
		}
		$isPaymentLimitReached = Bitrix24Manager::getInstance()->isPaymentsLimitReached();
		$isOrderLimitReached = CrmManager::getInstance()->isOrderLimitReached();

		return [
			'entityHasContact' => $entityHasContact,
			'contactHasPhone' => $contactHasPhone,
			'isPaymentLimitReached' => $isPaymentLimitReached,
			'isOrderLimitReached' => $isOrderLimitReached,
			'isTerminalAvailable' => AvailabilityManager::getInstance()->isAvailable(),
		];
	}
}
