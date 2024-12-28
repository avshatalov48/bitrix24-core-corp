<?php

namespace Bitrix\Crm\Service\Scenario\Sign\B2e;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Scenario;
use Bitrix\Crm\Service\Sign\B2e\StatusService;
use Bitrix\Crm\Service\Sign\B2e\TriggerService;
use Bitrix\Main\Result;

abstract class BaseTriggers extends Scenario
{
	private readonly TriggerService $triggerService;
	private readonly StatusService $statusService;

	public function __construct(private readonly int $categoryId)
	{
		$this->triggerService = Container::getInstance()->getSignB2eTriggerService();
		$this->statusService = Container::getInstance()->getSignB2eStatusService();
	}

	abstract protected function getTriggers(): array;

	public function play(): Result
	{
		$this->addDefaultTriggers();

		return new Result();
	}

	private function addDefaultTriggers(): void
	{
		$triggers = $this->statusService->makeTriggerNames($this->categoryId, $this->getTriggers());
		$this->triggerService->addTriggers($triggers);
	}
}
