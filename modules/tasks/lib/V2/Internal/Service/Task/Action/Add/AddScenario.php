<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\Entity\Task\Scenario;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class AddScenario
{
	use ConfigTrait;

	public function __invoke(array $fields): void
	{
		if (empty($fields['SCENARIO_NAME']))
		{
			$scenarios =[];
			$scenarios[] = Scenario::Default->value;
		}
		else
		{
			$scenarios = is_array($fields['SCENARIO_NAME'])
				? $fields['SCENARIO_NAME']
				: [$fields['SCENARIO_NAME']];
		}

		$taskId = (int)($fields['ID']);

		(new Async\Message\AddScenario($taskId, $scenarios))->sendByTaskId($taskId);
	}
}