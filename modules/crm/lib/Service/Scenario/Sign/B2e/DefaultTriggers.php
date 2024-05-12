<?php

namespace Bitrix\Crm\Service\Scenario\Sign\B2e;

use Bitrix\Crm\Automation\Trigger\Sign\B2e\CompletedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CoordinationTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\FillingTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningTrigger;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Scenario;
use Bitrix\Crm\Service\Sign\B2e\StatusService;
use Bitrix\Crm\Service\Sign\B2e\TriggerService;
use Bitrix\Main\Result;

final class DefaultTriggers extends Scenario
{
	private readonly TriggerService $triggerService;
	private readonly StatusService $statusService;
	private const DEFAULT_TRIGGERS = [
		CoordinationTrigger::class => 'COORDINATION',
		FillingTrigger::class => 'FILLING',
		SigningTrigger::class => 'SIGNING',
		CompletedTrigger::class => 'COMPLETED',
	];

	public function __construct(private readonly int $categoryId)
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
		$triggers = $this->statusService->makeTriggerNames($this->categoryId, self::DEFAULT_TRIGGERS);
		$this->triggerService->addTriggers($triggers);
	}
}
