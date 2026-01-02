<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\UserOption\Task;

class AddUserOptions
{
	use ConfigTrait;

	public function __invoke(array $fields): void
	{
		Task::onTaskAdd($fields);
	}
}