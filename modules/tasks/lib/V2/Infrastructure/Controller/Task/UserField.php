<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\TaskUserFieldsRepository;

class UserField extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.UserField.get
	 */
	#[CloseSession]
	public function getAction(
		#[Permission\Read]
		Entity\Task $task,
		TaskUserFieldsRepository $fieldsRepository,
	): Entity\UserFieldCollection
	{
		return $fieldsRepository->getByTaskId($task->getId());
	}
}
