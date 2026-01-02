<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Controller\DataProviders\ChildrenDataProvider;
use Bitrix\DiskMobile\SearchEntity;
use Bitrix\DiskMobile\SearchType;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\PageNavigation;

use Bitrix\Disk;
use Bitrix\Disk\Controller\Types;
use Bitrix\Main\Engine\Response;

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
		string $showRights = 'Y',
		array $context = [],
		string $search = null,
		array $searchContext = null,
		array $extra = [],
		PageNavigation $pageNavigation = null,
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

		return $this->respondAll($id, $order, $showRights === 'Y', $context, $search, $searchContext, $pageNavigation);
	}

	/**
	 * Returns children of folder.
	 * @param string|null $search Search string.
	 * @param bool $showRights Should be true if you want to show rights for each element.
	 * @param array $context Additional context for recognizing folderType. Necessary when user deep in folder tree.
	 * @param array $order How to sort elements. For example: ['NAME' => 'ASC']
	 * @return Page|null
	 * @see \Bitrix\Disk\Controller\Folder::getChildrenAction()
	 */
	protected function getChildren(
		int $id,
		array $order = [],
		bool $showRights = true,
		array $context = [],
		string $search = null,
		?array $searchContext = null,
		PageNavigation $pageNavigation = null,
	): ?Response\DataType\Page
	{

		$searchScope = (string)$search !== '' ? 'subfolders' : 'currentFolder';
		$searchOrder = (string)$search !== '' ? ['UPDATE_TIME' => 'DESC'] : null;
		$searchFolderId = $this->getRequestedSearchFolderId($search, $searchContext);

		return $this->forward(
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

		$response = $this->getDefaultResponse();;

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

		$response = $this->getDefaultResponse();

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
		PageNavigation $pageNavigation = null,
	): ?array
	{
		/** @var Page|null $page */
		$page = $this->getChildren(
			$id,
			$order,
			$showRights,
			$context,
			$search,
			$searchContext,
			$pageNavigation,
		);

		if (!$this->errorCollection->isEmpty())
		{
			return null;
		}

		$response = [
			...$this->getDefaultResponse(),
			'currentFolderRights' => [],
		];

		if ($page)
		{
			$response['items'] = $page->getItems();
			$response = $this->withUsers($response);
			$response = $this->withRealStorageIds($response);
			$response = $this->withStorages($response);
			$response['items'] = $this->withExternalLink($response['items']);

			$tag = "object_$id";
			$this->subscribeToPullEvents($response['items'], [ $tag ]);

			if ($showRights)
			{
				$response['currentFolderRights'] = $this->getRights($id);
			}
		}

		return $response;
	}

	protected function getRights(int $id): ?array
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

		return $rightsResult['rights'] ?? [];
	}
}
