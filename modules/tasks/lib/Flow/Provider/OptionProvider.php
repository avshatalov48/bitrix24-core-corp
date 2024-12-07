<?php

namespace Bitrix\Tasks\Flow\Provider;

use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Flow\Option\OptionDictionary;
use Bitrix\Tasks\Flow\Option\OptionService;

final class OptionProvider
{
	public function getManualDistributorId(int $flowId): ?int
	{
		$flow = FlowRegistry::getInstance()->get($flowId);

		if (!$flow || $flow->getDistributionType() !== Flow::DISTRIBUTION_TYPE_MANUALLY)
		{
			return null;
		}

		foreach (OptionService::getInstance()->getOptions($flowId) as $option)
		{
			if ($option->getName() === OptionDictionary::MANUAL_DISTRIBUTOR_ID->value)
			{
				return (int)$option->getValue();
			}
		}

		return null;
	}

	public function getResponsibleQueueLatestId(int $flowId): ?int
	{
		$flow = FlowRegistry::getInstance()->get($flowId);

		if (!$flow || $flow->getDistributionType() !== Flow::DISTRIBUTION_TYPE_QUEUE)
		{
			return null;
		}

		foreach (OptionService::getInstance()->getOptions($flowId) as $option)
		{
			if ($option->getName() === OptionDictionary::RESPONSIBLE_QUEUE_LATEST_ID->value)
			{
				return (int)$option->getValue();
			}
		}

		return null;
	}
}
