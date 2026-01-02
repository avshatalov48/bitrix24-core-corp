<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Task\Priority;

class PreparePriority implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		if (!isset($fields['PRIORITY']))
		{
			return $fields;
		}

		$validValues = array_values(Priority::getAll());
		$fields['PRIORITY'] = (int)$fields['PRIORITY'];

		if (!in_array($fields['PRIORITY'], $validValues, true))
		{
			$fields['PRIORITY'] = Priority::AVERAGE;
		}

		return $fields;
	}
}