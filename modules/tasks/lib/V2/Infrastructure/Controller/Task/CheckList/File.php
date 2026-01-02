<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task\CheckList;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Validation\Rule\ElementsType;
use Bitrix\Main\Validation\Rule\Enum\Type;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Entity\DiskFileCollection;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Provider\DiskFileProvider;

class File extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.CheckList.File.list
	 */
	#[CloseSession]
	public function listAction(
		#[Permission\Read]
		Entity\Task $task,
		#[ElementsType(Type::Integer)]
		array $ids,
		DiskFileProvider $diskFileProvider,
	): DiskFileCollection
	{
		return $diskFileProvider->getCheckListsAttachmentsByIds(
			ids: $ids,
			taskId: (int)$task->id,
			userId: $this->userId,
		);
	}
}
