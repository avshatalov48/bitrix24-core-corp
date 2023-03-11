<?php

namespace Bitrix\Crm\Service\Operation\Action\Compatible\SocialNetwork;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Crm\Settings;
use Bitrix\Main\Result;

class ProcessAdd extends Action
{
	public function process(Item $item): Result
	{
		if (Settings\Crm::isLiveFeedRecordsGenerationEnabled())
		{
			\CCrmSonetSubscription::RegisterSubscription(
				$item->getEntityTypeId(),
				$item->getId(),
				\CCrmSonetSubscriptionType::Responsibility,
				$item->getAssignedById(),
			);
		}

		\CCrmLiveFeed::registerItemAdd($item, $this->getContext());

		return new Result();
	}
}
