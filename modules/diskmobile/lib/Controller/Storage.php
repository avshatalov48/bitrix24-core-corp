<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;

class Storage extends Base
{
	public function configureActions(): array
	{
		return [
			'getPersonalStorage' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getBySocialGroup' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getSharedStorage' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/** @param bool $showRights
	 * @return array|null
	 * @see \Bitrix\Disk\Controller\Storage::getPersonalStorageAction()
	 */
	public function getPersonalStorageAction(bool $showRights = true): ?array
	{
		$result = $this->forward(
			\Bitrix\Disk\Controller\Storage::class,
			'getPersonalStorage',
		);

		if ($this->errorCollection->isEmpty())
		{
			$storage = $result['storage'];

			return [
				'storage' => $storage,
				'rootFolderRights' => $showRights ? $this->getFolderRights($storage->getRootObjectId()) : null,
			];
		}

		return null;
	}

	public function getSharedStorageAction(bool $showRights = true): ?array
	{
		$result = $this->forward(
			\Bitrix\Disk\Controller\Storage::class,
			'getCommonStorage',
		);

		if ($this->errorCollection->isEmpty())
		{
			$storage = $result['storage'];

			return [
				'storage' => $storage,
				'rootFolderRights' => $showRights ? $this->getFolderRights($storage->getRootObjectId()) : null,
			];
		}

		return null;
	}

	public function getBySocialGroupAction(int $groupId, bool $showRights = true): ?array
	{
		$result = $this->forward(
			\Bitrix\Disk\Controller\Storage::class,
			'getBySocialGroup',
		);

		if ($this->errorCollection->isEmpty())
		{
			$storage = $result['storage'];

			return [
				'storage' => $storage,
				'rootFolderRights' => $showRights ? $this->getFolderRights($storage->getRootObjectId()) : null,
			];
		}

		return null;
	}

	private function getFolderRights(int $id): ?array
	{
		$result = $this->forward(
			\Bitrix\Disk\Controller\CommonActions::class,
			'getRights',
			['objectId' => $id],
		);

		if ($this->errorCollection->isEmpty())
		{
			return $result['rights'];
		}

		return null;
	}
}