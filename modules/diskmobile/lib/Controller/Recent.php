<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Disk\BaseObject;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Response\DataType\Page;

class Recent extends BaseFileList
{
	public function configureActions(): array
	{
		return [
			'get' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
			'getByCollabId' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/**
	 * @see \Bitrix\Disk\Controller\TrackedObject::listAction()
	 * @restMethod diskmobile.Recent.get
	 */
	public function getAction(string $search = null, array $extra = []): ?array
	{
		$onlyIds = $this->getRequestedIds($extra);

		if (!empty($onlyIds))
		{
			return $this->respondWithSpecificIds($onlyIds);
		}

		/** @var array|null $page */
		$page = $this->forward(
			\Bitrix\Disk\Controller\TrackedObject::class,
			'list',
		);

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		$response = [
			'items' => [],
			'users' => [],
			'storages' => [],
		];

		if (isset($page['items']))
		{
			$response['items'] = array_map(fn(array $item): array => $item['trackedObject']['file'], $page['items']);
			$response = $this->withUsers($response);
			$response = $this->withRealStorageIds($response);
			$response = $this->withStorages($response);
		}

		$tag = "disk_user_{$this->getCurrentUser()->getId()}_recents";
		$this->subscribeToPullEvents($response['items'], [ $tag ]);

		return $response;
	}

	/**
	 * @see \Bitrix\Disk\Controller\Collab\Recents::listAction()
	 * @restMethod diskmobile.Recent.getByCollabId
	 */
	public function getByCollabIdAction(int $groupId, string $search = null, array $extra = []): ?array
	{
		$onlyIds = $this->getRequestedIds($extra);

		if (!empty($onlyIds))
		{
			return $this->respondWithSpecificIds($onlyIds);
		}

		/** @var Page|null $page */
		$page = $this->forward(
			\Bitrix\Disk\Controller\Collab\Recents::class,
			'list',
		);

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		$response = [
			'items' => [],
			'users' => [],
			'storages' => [],
		];

		if ($page)
		{
			/** @var BaseObject[] $items */
			$items = $page->getItems();
			$response['items'] = array_map(fn($item): array => $item->jsonSerialize(), $items);
			$response = $this->withUsers($response);
			$response = $this->withRealStorageIds($response);
			$response = $this->withStorages($response);
		}

		$tag = "disk_user_{$this->getCurrentUser()->getId()}_recents";
		$this->subscribeToPullEvents($response['items'], [ $tag ]);

		return $response;
	}

	private function respondWithSpecificIds(
		array $ids = [],
	): ?array
	{
		/** @var array $page */
		$page = $this->forward(
			\Bitrix\Disk\Controller\CommonActions::class,
			'getByIds',
			['objectCollection' => $ids],
		);

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		$response = [
			'items' => [],
			'users' => [],
			'storages' => [],
		];

		if (isset($page['items']))
		{
			$response['items'] = array_map(fn(array $item): array => $item['object'], $page['items']);
			$response = $this->withUsers($response);
			$response = $this->withRealStorageIds($response);
			$response = $this->withStorages($response);
		}

		$this->subscribeToPullEvents($response);

		return $response;
	}
}
