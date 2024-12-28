<?php

namespace Bitrix\Crm\Service\Scenario\Sign\B2e;

use Bitrix\Crm\Automation\Trigger\Sign\B2e\CompletedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CoordinationTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningTrigger;

final class DefaultEmployeeTriggers extends BaseTriggers
{
	protected function getTriggers(): array
	{
		return [
			CoordinationTrigger::class => 'EMPLOYEE_COORDINATION',
			SigningTrigger::class => 'EMPLOYEE_SIGNING',
			CompletedTrigger::class => 'EMPLOYEE_COMPLETED',
		];
	}
}
