<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Provider;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\V2\Internal\Entity\CheckList\Type;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Access\Service\DiskFileAccessService;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Entity\DiskFileCollection;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Repository\DiskFileRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\CheckListRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskReadRepositoryInterface;

class DiskFileProvider
{
	public function __construct(
		private readonly DiskFileAccessService $diskFileAccessService,
		private readonly DiskFileRepositoryInterface $diskFileRepository,
		private readonly CheckListRepositoryInterface $checkListRepository,
		private readonly TaskReadRepositoryInterface $taskRepository,
	)
	{

	}

	public function getTaskAttachmentsByIds(array $ids, int $taskId, int $userId): DiskFileCollection
	{
		Collection::normalizeArrayValuesByInt($ids);

		if (empty($ids) || $taskId <= 0 || $userId <= 0)
		{
			return new DiskFileCollection();
		}

		if (!Loader::includeModule('disk'))
		{
			return new DiskFileCollection();
		}

		if (!$this->diskFileAccessService->canReadTaskAttachments($taskId, $userId))
		{
			return new DiskFileCollection();
		}

		$ids = $this->filterByTask($ids, $taskId);
		if (empty($ids))
		{
			return new DiskFileCollection();
		}

		return $this->diskFileRepository->getByIds($ids);
	}

	public function getCheckListsAttachmentsByIds(array $ids, int $taskId, int $userId): DiskFileCollection
	{
		Collection::normalizeArrayValuesByInt($ids);

		if (empty($ids) || $taskId <= 0 || $userId <= 0)
		{
			return new DiskFileCollection();
		}

		if (!Loader::includeModule('disk'))
		{
			return new DiskFileCollection();
		}

		if (!$this->diskFileAccessService->canReadTaskAttachments($taskId, $userId))
		{
			return new DiskFileCollection();
		}

		$ids = $this->filterByCheckList($ids, $taskId);
		if (empty($ids))
		{
			return new DiskFileCollection();
		}

		return $this->diskFileRepository->getByIds($ids);
	}

	private function filterByTask(array $ids, int $taskId): array
	{
		$diskFileIds = $this->taskRepository->getAttachmentIds($taskId);

		return array_filter($ids, static fn (int $id): bool => in_array($id, $diskFileIds, true));
	}

	private function filterByCheckList(array $ids, int $taskId): array
	{
		$diskFileIds = $this->checkListRepository->getAttachmentIdsByEntity($taskId, Type::Task);

		return array_filter($ids, static fn (int $id): bool => in_array($id, $diskFileIds, true));
	}
}
