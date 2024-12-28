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

		if ($this->errorCollection->isEmpty())
		{
			$diskObject['rights'] = $rightsResult['rights'];

			return [
				'diskObject'=> $diskObject,
			];
		}

		return null;
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
			\Bitrix\DiskMobile\Controller\Common::class,
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
