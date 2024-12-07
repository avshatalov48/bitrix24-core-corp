<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Flow\Notification\ConfigRepository;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Integration\Bizproc\Flow\Manager;

class NotifyAboutSwitchToManualDistributionAbsentCommandHandler
{

	public function __construct(
		private readonly ConfigRepository $configRepository,
		private readonly Manager $bizProc,
		private readonly FlowProvider $flowProvider,
	)
	{}

	public function __invoke(NotifyAboutSwitchToManualDistributionAbsentCommand $command): void
	{
		try
		{
			$flow = $this->flowProvider->getFlow($command->getFlowId());
		}
		catch (ProviderException $exception)
		{
			return;
		}

		$config = $this->configRepository->readByFlowId($flow->getId());

		foreach ($config->getItems() as $item)
		{
			switch ($item->getWhen()->getType())
			{
				case When::FORCED_FLOW_SWITCH_TO_MANUAL_DISTRIBUTION_ABSENT:
					$this->bizProc->runProc($item->getIntegrationId(), [$flow->getId()]);
					break;
			}
		}
	}
}