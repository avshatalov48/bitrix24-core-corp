<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Notification\Config;
use Bitrix\Tasks\Flow\Notification\ConfigRepository;
use Bitrix\Tasks\Flow\Notification\SyncAgent;

class SaveConfigCommandHandler
{
	private ConfigRepository $repository;
	private SyncAgent $syncAgent;

	public function __construct(ConfigRepository $repository, SyncAgent $syncAgent)
	{
		$this->repository = $repository;
		$this->syncAgent = $syncAgent;
	}

	public function __invoke(SaveConfigCommand $command): void
	{
		$config = new Config($command->getFlowId(), $command->getItems());
		$existingConfig = $this->repository->readByFlowId($command->getFlowId());

		if ($config->isEqual($existingConfig))
		{
			if (!$command->isForceSync())
			{
				return;
			}
		}

		// remove existing configs
		$this->repository->removeByFlowId($command->getFlowId());
		// save new config in local db
		$this->repository->saveNewConfig($config);
		// run sync agent
		$this->syncAgent->addAgent($command->getFlowId());
	}
}