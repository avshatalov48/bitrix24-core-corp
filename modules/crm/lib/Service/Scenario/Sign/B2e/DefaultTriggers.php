<?php

namespace Bitrix\Crm\Service\Scenario\Sign\B2e;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Scenario;
use Bitrix\Crm\Service\Sign\B2e\StatusService;
use Bitrix\Crm\Service\Sign\B2e\TriggerService;
use Bitrix\Main\Result;

final class DefaultTriggers extends Scenario
{
	private TriggerService $triggerService;
	private StatusService $statusService;

	public function __construct(private int $categoryId)
	{
		$this->triggerService = Container::getInstance()->getSignB2eTriggerService();
		$this->statusService = Container::getInstance()->getSignB2eStatusService();
	}

	public function play(): Result
	{
		$this->addDefaultTriggers();

		return new Result();
	}

	private function addDefaultTriggers(): void
	{
		$triggers = $this->statusService->makeTriggerNames($this->categoryId, $this->triggerService->getDefaultTriggers());
		$this->triggerService->addTriggers($triggers);
	}
}
