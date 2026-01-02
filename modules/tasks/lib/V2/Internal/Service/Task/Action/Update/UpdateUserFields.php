<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Exception\TaskUpdateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\UserFieldTrait;
use Bitrix\Tasks\Util\UserField\Task;

class UpdateUserFields
{
	use ConfigTrait;
	use UserFieldTrait;

	public function __invoke(array $fields, int $taskId): bool
	{
		if ($this->checkContainsUfKeys($fields))
		{
			return $this->getUfManager()->Update(Task::getEntityCode(), $taskId, $fields, $this->config->getUserId());
		}

		return false;
	}
}