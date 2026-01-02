<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;

class Common extends Base
{
	public function configureActions(): array
	{
		return [
			'getByIdsWithRights' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getTrackedByIdWithRights' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getFolderByPath' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	public function getByIdWithRightsAction(int $id): ?array
	{
		/** @var array $page */
		$page = $this->forward(
			\Bitrix\Disk\Controller\CommonActions::class,
			'getByIds',
			['objectCollection' => [$id]],
		);

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		$diskObject = $page['items'][0]['object'];

		$rightsResult = $this->forward(
			\Bitrix\Disk\Controller\CommonActions::class,
			'getRights',
			['objectId' => $id],
		);

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		$diskObject['rights'] = $rightsResult['rights'];

		$externalLinkResult = $this->forward(
			\Bitrix\Disk\Controller\CommonActions::class,
			'getExternalLink',
			['objectId' => $id],
		);

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		$diskObject['links']['external'] = $externalLinkResult['externalLink'];

		return [
			'diskObject'=> $diskObject,
		];
	}

	public function getTrackedByIdWithRightsAction(int $id, bool $showRights = true): array
	{
		$trackedDiskObject = $this->forward(
			\Bitrix\Disk\Controller\TrackedObject::class,
			'get',
			['id' => $id, 'showRights' => $showRights],
		);

		$diskObject = $this->trackedToItem($trackedDiskObject);


		return [
			'diskObject'=> $diskObject,
		];
	}

	public function getFolderByPathAction(string $entityType, string $entityId, string $path): ?array
	{
		/** @var array $page */
		$resolvedPath = $this->forward(
			\Bitrix\Disk\Controller\CommonActions::class,
			'resolveFolderPath',
		);

		if (!$this->errorCollection->isEmpty() || !isset($resolvedPath['targetFolder']))
		{
			return null;
		}

		$targetFolderId = $resolvedPath['targetFolder']->getId();

		$targetFolder = $this->forward(
			self::class,
			'getByIdWithRights',
			['id' => $targetFolderId],
		);

		if ($this->errorCollection->isEmpty())
		{
			return $targetFolder;
		}

		return null;
	}
}
