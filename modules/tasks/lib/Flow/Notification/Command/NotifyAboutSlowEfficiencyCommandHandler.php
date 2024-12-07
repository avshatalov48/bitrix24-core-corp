<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Notification\Config\Item;
use Bitrix\Tasks\Flow\Notification\Config\When;
use Bitrix\Tasks\Flow\Notification\ConfigRepository;
use Bitrix\Tasks\Flow\Notification\ThrottleProvider;
use Bitrix\Tasks\Flow\Provider\Exception\ProviderException;
use Bitrix\Tasks\Flow\Provider\FlowProvider;
use Bitrix\Tasks\Integration\Bizproc\Flow\Manager;

class NotifyAboutSlowEfficiencyCommandHandler
{
	private ConfigRepository $configRepository;
	private Manager $bizProc;
	private FlowProvider $flowProvider;
	private ThrottleProvider $throttleProvider;

	public function __construct(
		ConfigRepository $repository,
		Manager $bizProc,
		FlowProvider $flowProvider,
		ThrottleProvider $throttleProvider
	)
	{
		$this->configRepository = $repository;
		$this->bizProc = $bizProc;
		$this->flowProvider = $flowProvider;
		$this->throttleProvider = $throttleProvider;
	}

	public function __invoke(NotifyAboutSlowEfficiencyCommand $command): void
	{
		$config = $this->configRepository->readByFlowId($command->getFlowId());

		foreach ($config->getItems() as $item)
		{
			switch ($item->getWhen()->getType())
			{
				case When::SLOW_EFFICIENCY:
					$this->handleSlowEfficiency($item, $command->getFlowId());
					break;
			}
		}
	}

	private function handleSlowEfficiency(Item $item, int $flowId): void
	{
		$procId = $item->getIntegrationId();
		if (!$procId)
		{
			return;
		}

		try
		{
			$flow = $this->flowProvider->getFlow($flowId);
		}
		catch (ProviderException $e)
		{
			return;
		}

		$offset = (int)$item->getWhen()->getValue()['offset'];
		$key = 'NotifyAboutSlowEfficiencyCommand_' . $flowId . '_' . $offset;
		$currentEfficiency = $this->flowProvider->getEfficiency($flow);

		if ($currentEfficiency >= $offset)
		{
			$this->throttleProvider->release($key);
			return;
		}

		$this->throttleProvider->attempt(
			$key,
			fn() => $this->bizProc->runProc($procId, [$flowId])
		);
	}
}