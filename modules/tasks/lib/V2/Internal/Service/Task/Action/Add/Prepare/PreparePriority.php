<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Task\Priority;

class PreparePriority implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		$validValues = array_values(Priority::getAll());

		if (!isset($fields['PRIORITY']))
		{
			$fields['PRIORITY'] = Priority::AVERAGE;
		}

		$fields['PRIORITY'] = (int)$fields['PRIORITY'];
		if (!in_array($fields['PRIORITY'], $validValues, true))
		{
			$fields['PRIORITY'] = Priority::AVERAGE;
		}

		return $fields;
	}
}