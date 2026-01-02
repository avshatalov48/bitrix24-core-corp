<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;

class UpdateRelatedTasks
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): void
	{
		if (!is_array($fields['DEPENDS_ON'] ?? null))
		{
			return;
		}

		$repository = Container::getInstance()->getRelatedTaskRepository();

		$repository->deleteByTaskId((int)$fields['ID']);
		$repository->save((int)$fields['ID'], $fields['DEPENDS_ON']);
	}
}
