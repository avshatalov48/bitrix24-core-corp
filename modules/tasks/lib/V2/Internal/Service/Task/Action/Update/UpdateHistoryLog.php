<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\OccurredUserTrait;

class UpdateHistoryLog
{
	use ConfigTrait;
	use OccurredUserTrait;

	public function __invoke(array $fullTaskData, array $changes): void
	{
		// todo
		foreach ($changes as $key => $value)
		{
			$arLogFields = [
				"TASK_ID" => $fullTaskData['ID'],
				"USER_ID" => $this->getOccurredUserId($this->config->getUserId()),
				"CREATED_DATE" => $fullTaskData["CHANGED_DATE"],
				"FIELD" => $key,
				"FROM_VALUE" => $value["FROM_VALUE"],
				"TO_VALUE" => $value["TO_VALUE"],
			];

			$log = new \CTaskLog();
			$log->Add($arLogFields);
		}
	}
}