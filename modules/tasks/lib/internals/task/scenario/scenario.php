<?php

namespace Bitrix\Tasks\Internals\Task\Scenario;

use Bitrix\Tasks\Internals\Task\EO_Scenario;

class Scenario extends EO_Scenario
{
	public function isCrm(): bool
	{
		return $this->getScenario() === \Bitrix\Tasks\V2\Internal\Entity\Task\Scenario::Crm->value;
	}
}
