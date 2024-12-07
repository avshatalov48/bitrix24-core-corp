<?php

namespace Bitrix\Tasks\Flow\Notification\Query;

use Bitrix\Tasks\Flow\Notification\Config;
use Bitrix\Tasks\Flow\Notification\ConfigRepository;

class GetFlowConfigHandler
{
	private ConfigRepository $readRepository;

	public function __construct()
	{
		$this->readRepository = new ConfigRepository();
	}

	public function __invoke(int $flowId): Config
	{
		return $this->readRepository->readByFlowId($flowId);
	}
}