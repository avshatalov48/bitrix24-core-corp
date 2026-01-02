<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Main\Validation\Rule\ElementsType;
use Bitrix\Main\Validation\Rule\Enum\Type;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Internal\Access\Task\Attachment;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Entity\DiskFileCollection;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Provider\DiskFileProvider;
use Bitrix\Tasks\V2\Public\Command\Task\Attachment\AttachFilesCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Attachment\DetachFilesCommand;

class File extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.File.list
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
		return $diskFileProvider->getTaskAttachmentsByIds(
			ids: $ids,
			taskId: (int)$task->id,
			userId: $this->userId,
		);
	}

	/**
	 * @ajaxAction tasks.V2.File.attach
	 *
	 * @param string[] $ids
	 * e.g. ['n1', 'n2']
	 */
	public function attachAction(
		#[Attachment\Permission\Attach]
		Entity\Task $task,
		array $ids,
	): ?bool
	{
		$result = (new AttachFilesCommand(
			taskId: $task->id,
			userId: $this->userId,
			fileIds: $ids,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.File.detach
	 *
	 * @param (int|string)[] $ids
	 * e.g. ['n1', 2]
	 */
	public function detachAction(
		#[Attachment\Permission\Detach]
		Entity\Task $task,
		array $ids,
	): ?bool
	{
		$result = (new DetachFilesCommand(
			taskId: $task->id,
			userId: $this->userId,
			fileIds: $ids,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}
}
