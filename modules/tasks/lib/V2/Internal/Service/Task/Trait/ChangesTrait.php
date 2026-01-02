<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Trait;

use CTaskLog;

trait ChangesTrait
{
	private function getChanges(array $fields, array $fullTaskData): array
	{
		if (isset($fullTaskData['DURATION_PLAN']))
		{
			unset($fullTaskData['DURATION_PLAN']);
		}

		if (isset($fields['DURATION_PLAN']))
		{
			// at this point, $arFields['DURATION_PLAN'] in seconds
			$fields['DURATION_PLAN_SECONDS'] = $fields['DURATION_PLAN'];
			unset($fields['DURATION_PLAN']);
		}

		return CTaskLog::GetChanges($fullTaskData, $fields);
	}
}