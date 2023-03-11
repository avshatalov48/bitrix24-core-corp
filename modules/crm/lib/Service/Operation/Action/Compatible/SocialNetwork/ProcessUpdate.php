<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Crm\Settings;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class ProcessUpdate extends Action
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

		if (
			Settings\Crm::isLiveFeedRecordsGenerationEnabled()
			&& $itemBeforeSave->remindActual(Item::FIELD_NAME_ASSIGNED) !== $item->getAssignedById()
		)
		{
			\CCrmSonetSubscription::ReplaceSubscriptionByEntity(
				$item->getEntityTypeId(),
				$item->getId(),
				\CCrmSonetSubscriptionType::Responsibility,
				$item->getAssignedById(),
				$itemBeforeSave->remindActual(Item::FIELD_NAME_ASSIGNED),
			);
		}

		\CCrmLiveFeed::registerItemUpdate($itemBeforeSave, $item, $this->getContext());

		return $result;
	}
}
