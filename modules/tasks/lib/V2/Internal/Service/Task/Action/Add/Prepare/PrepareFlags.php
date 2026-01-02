<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class PrepareFlags implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$flags = [
			'ALLOW_CHANGE_DEADLINE',
			'TASK_CONTROL',
			'ADD_IN_REPORT',
			'MATCH_WORK_TIME',
			'REPLICATE',
		];

		foreach ($flags as $flag)
		{
			if (
				!isset($fields[$flag])
				|| ($fields[$flag] !== 'Y' && $fields[$flag] !== true)
			)
			{
				$fields[$flag] = false;
			}
			else
			{
				$fields[$flag] = true;
			}
		}

		return $fields;
	}
}