<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class PrepareParents implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		if (!isset($fields['PARENT_ID']))
		{
			return $fields;
		}

		$parentId = (int)($fields['PARENT_ID'] ?? 0);
		if ($parentId <= 0)
		{
			// todo
			$fields['PARENT_ID'] = false;

			return $fields;
		}

		$fields['PARENT_ID'] = $parentId;

		$parentTask = TaskRegistry::getInstance()->getObject($parentId);
		if ($parentTask === null || $parentTask->isDeleted())
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_PARENT_ID'));
		}

		return $fields;
	}
}