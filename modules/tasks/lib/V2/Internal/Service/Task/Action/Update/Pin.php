<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\Service\Task\Trait\PinTrait;
use Bitrix\Tasks\Kanban\StagesTable;

class Pin
{
	use PinTrait;

	public function __invoke(array $fullTaskData, array $sourceTaskData): void
	{
		$this->pin($fullTaskData);

		if (
			!$fullTaskData['GROUP_ID']
			|| (int)$fullTaskData['GROUP_ID'] === (int)$sourceTaskData['GROUP_ID']
		)
		{
			return;
		}

		StagesTable::pinInStage(
			$fullTaskData['ID'],
			[
				'CREATED_BY' => $sourceTaskData['CREATED_BY'],
			],
			true
		);
	}
}