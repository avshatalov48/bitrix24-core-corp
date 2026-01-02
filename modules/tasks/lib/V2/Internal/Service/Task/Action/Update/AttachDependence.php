<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;

class AttachDependence
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): void
	{
		if (!isset($fields['PARENT_ID']))
		{
			return;
		}

		// PARENT_ID changed, reattach subtree from previous location to new one
		Dependence::attach((int)$fullTaskData['ID'], (int)$fields['PARENT_ID']);
	}
}
