<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\DI\Container;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;

class AddRelatedTasks
{
	use ConfigTrait;

	public function __invoke(array $fields): void
	{
		if (!is_array($fields['DEPENDS_ON'] ?? null))
		{
			return;
		}

		$repository = Container::getInstance()->getRelatedTaskRepository();

		$repository->save((int)$fields['ID'], $fields['DEPENDS_ON']);
	}
}
