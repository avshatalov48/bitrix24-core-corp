<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Disk\BaseObject;
use Bitrix\DiskMobile\SearchEntity;
use Bitrix\DiskMobile\SearchType;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Response\DataType\Page;

class Folder extends BaseFileList
{
	public function configureActions(): array
	{
		return [
			'getChildren' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/** @see \Bitrix\Disk\Controller\Folder::getChildrenAction() */
	public function getChildrenAction(
		int $id,
		array $order = [],
		bool $showRights = true,
		array $context = [],
		string $search = null,
		array $searchContext = null,
		array $extra = [],
	): ?array
	{
		$onlyIds = $this->getRequestedIds($extra);

		if (!empty($onlyIds))
		{
			return $this->respondWithSpecificIds($onlyIds);
		}

		if ($this->getRequestedSearchType($search, $searchContext) === SearchType::Global)
		{
			return $this->respondWithGlobalSearchResults((string)$search, $searchContext);
		}

		return $this->respondAll($id, $order, $showRights, $context, $search, $searchContext);
	}

	private function getRequestedSearchType(?string $search = null, ?array $searchContext = null): ?SearchType
	{
		if (mb_strlen((string)$search) > 0)
		{
			$type = (string)($searchContext['type'] ?? '');

			return SearchType::tryFrom($type) ?? SearchType::Directory;
		}

		return null;
	}

	private function getRequestedSearchFolderId(?string $search = null, ?array $searchContext = null): ?int
	{
		if (mb_strlen((string)$search) > 0 && isset($searchContext['folderId']) && (int)$searchContext['folderId'] > 0)
		{
			return (int)$searchContext['folderId'];
		}

		return null;
	}

	private function respondWithGlobalSearchResults(string $search, ?array $searchContext = null): ?array
	{
		$entities = $searchContext['entities'] ?? [];
		$entities = is_array($entities) ? $entities : [];

		$allowedEntities = [
			SearchEntity::User->value,
			SearchEntity::Group->value,
			SearchEntity::Common->value,
		];

		$entityTypes = array_map(
			fn(string $value) => SearchEntity::from($value)->entityType(),
			array_intersect($allowedEntities, $entities),
		);

		$storageFileFinder = new \Bitrix\Disk\Search\StorageFileFinder(
			userId: $this->getCurrentUser()->getId(),
			entityTypes: $entityTypes,
		);
		$items = $storageFileFinder->findModelsByText($search);

		$response = [
			'items' => [],
			'users' => [],
			'storages' => [],
		];

		if (!empty($items))
		{
			$response['items'] = array_map(fn(BaseObject $item): array => $item->jsonSerialize(), $items);
			$response = $this->withUsers($response);
			$response = $this->withStorages($response);
		}

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
			$response = $this->withStorages($response);
		}

		return $response;
	}

	private function respondAll(
		int $id,
		array $order = [],
		bool $showRights = true,
		array $context = [],
		string $search = null,
		?array $searchContext = null,
	): ?array
	{
		$searchScope = mb_strlen((string)$search) > 0 ? 'subfolders' : 'currentFolder';
		$searchOrder = mb_strlen((string)$search) > 0 ? ['UPDATE_TIME' => 'DESC'] : null;
		$searchFolderId = $this->getRequestedSearchFolderId($search, $searchContext);

		/** @var Page|null $page */
		$page = $this->forward(
			\Bitrix\Disk\Controller\Folder::class,
			'getChildren',
			[
				'id' => $searchFolderId ?? $id,
				'search' => $search,
				'searchScope' => $searchScope,
				'showRights' => $showRights,
				'context' => $context,
				'order' => $searchOrder ?? $order,
			]
		);

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		$response = [
			'items' => [],
			'users' => [],
			'storages' => [],
			'currentFolderRights' => [],
		];

		if ($page)
		{
			$response['items'] = $page->getItems();
			$response = $this->withUsers($response);
			$response = $this->withRealStorageIds($response);
			$response = $this->withStorages($response);

			$tag = "object_$id";
			$this->subscribeToPullEvents($response['items'], [ $tag ]);

			if ($showRights)
			{
				$rightsResult = $this->forward(
					\Bitrix\Disk\Controller\CommonActions::class,
					'getRights',
					['objectId' => $id],
				);

				if (!$this->errorCollection->isEmpty())
				{
					return null;
				}

				$response['currentFolderRights'] = $rightsResult['rights'] ?? [];
			}
		}

		return $response;
	}
}
