<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Notification\ConfigRepository;
use Bitrix\Tasks\Flow\Notification\SyncAgent;

class DeleteConfigCommandHandler
{
	private ConfigRepository $repository;
	private SyncAgent $syncAgent;

	public function __construct(ConfigRepository $repository, SyncAgent $syncAgent)
	{
		$this->repository = $repository;
		$this->syncAgent = $syncAgent;
	}

	public function __invoke(DeleteConfigCommand $command): void
	{
		// remove existing configs
		$this->repository->removeByFlowId($command->getFlowId());
		// run sync agent
		$this->syncAgent->addAgent($command->getFlowId());
	}
}