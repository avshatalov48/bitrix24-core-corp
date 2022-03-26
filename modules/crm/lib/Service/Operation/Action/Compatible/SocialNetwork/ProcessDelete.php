<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class ProcessDelete extends Action
{
	public function process(Item $item): Result
	{
		$result = new Result();

		$itemBeforeSave = $this->getItemBeforeSave();
		if (!$itemBeforeSave)
		{
			$result->addError(
				new Error('itemBeforeSave is required in ' . static::class),
			);

			return $result;
		}

		\CCrmSonetSubscription::UnRegisterSubscriptionByEntity($itemBeforeSave->getEntityTypeId(), $itemBeforeSave->getId());
		\CCrmLiveFeed::DeleteLogEvents(
			[
				'ENTITY_TYPE_ID' => $itemBeforeSave->getEntityTypeId(),
				'ENTITY_ID' => $itemBeforeSave->getId(),
			],
		);

		return $result;
	}
}
