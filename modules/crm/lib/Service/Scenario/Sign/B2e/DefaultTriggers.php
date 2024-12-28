<?php

namespace Bitrix\Crm\Service\Scenario\Sign\B2e;

use Bitrix\Crm\Automation\Trigger\Sign\B2e\CompletedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CoordinationTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\FillingTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningTrigger;

final class DefaultTriggers extends BaseTriggers
{
	protected function getTriggers(): array
	{
		return [
			CoordinationTrigger::class => 'COORDINATION',
			FillingTrigger::class => 'FILLING',
			SigningTrigger::class => 'SIGNING',
			CompletedTrigger::class => 'COMPLETED',
		];
	}
}
