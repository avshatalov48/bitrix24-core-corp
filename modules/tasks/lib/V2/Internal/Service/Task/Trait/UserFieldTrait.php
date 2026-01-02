<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Trait;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Exception\TaskAddException;
use Bitrix\Tasks\Util\UserField;
use Bitrix\Tasks\Util\UserField\Task;
use CUserTypeManager;

trait UserFieldTrait
{
	use ApplicationErrorTrait;

	private function checkFields(int $taskId, array $fields, int $userId): bool
	{
		return $this->getUfManager()->CheckFields(Task::getEntityCode(), $taskId, $fields, $userId);
	}

	private function checkContainsUfKeys(array $fields): bool
	{
		return UserField::checkContainsUFKeys($fields);
	}

	private function getUfManager(): CUserTypeManager
	{
		global $USER_FIELD_MANAGER;

		return $USER_FIELD_MANAGER;
	}
}