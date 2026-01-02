<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\Helper\Task\Dependence;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Internals\Task\ProjectDependenceTable;

class PrepareParents implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		if (!isset($fields['PARENT_ID']))
		{
			return $fields;
		}

		$parentId = (int)$fields['PARENT_ID'];
		if (!$parentId)
		{
			$fields['PARENT_ID'] = false;

			return $fields;
		}

		$fields['PARENT_ID'] = $parentId;

		$parentTask = TaskRegistry::getInstance()->getObject($parentId);
		if ($parentTask === null || $parentTask->isDeleted())
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_BAD_PARENT_ID'));
		}

		$taskId = (int)$fullTaskData['ID'];

		$this->checkLink($taskId, $parentId);

		$this->checkCanAttach($taskId, $parentId);

		return $fields;
	}

	private function checkLink(int $taskId, int $parentId): void
	{
		if (!ProjectDependenceTable::checkLinkExists($taskId, $parentId, ['BIDIRECTIONAL' => true]))
		{
			return;
		}

		throw new TaskFieldValidateException(Loc::getMessage('TASKS_IS_LINKED_SET_PARENT'));
	}

	private function checkCanAttach(int $taskId, int $parentId): void
	{
		$result = Dependence::canAttach($taskId, $parentId);
		if ($result->isSuccess())
		{
			return;

		}

		$errors = $result->getErrors();
		if ($errors === null || $errors->isEmpty())
		{
			return;
		}

		$messages = $errors->getMessages();

		throw new TaskFieldValidateException(array_shift($messages));
	}
}