<?php
declare(strict_types=1);

namespace Bitrix\Disk\Controller\Collab;

use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Integration\Collab\CollabService;
use Bitrix\Disk\Internals\Engine\Controller;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Disk\Internals\FileTable;
use Bitrix\Disk\Search\SearchFilterBuilder;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\Response\DataType\Page;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\UI\PageNavigation;

final class Recents extends Controller
{
	/**
	 * Returns list of recent files by group storage.
	 * @param int $groupId Group id, should be collab group.
	 * @param CurrentUser $currentUser Current user, autowired automatically.
	 * @param string|null $search Search string.
	 * @param PageNavigation|null $pageNavigation Page navigation, autowired automatically.
	 * @return Page|null
	 * @throws NotImplementedException
	 */
	public function listAction(
		int $groupId,
		CurrentUser $currentUser,
		string $search = null,
		PageNavigation $pageNavigation = null
	): ?Page
	{
		$storage = Driver::getInstance()->getStorageByGroupId($groupId);
		if (!$storage)
		{
			$this->addError(new Error("Could not find storage by group id {$groupId}"));

			return null;
		}

		$collabService = new CollabService();
		if (!$collabService->isCollabStorage($storage))
		{
			$this->addError(new Error('Storage is not collab storage'));

			return null;
		}

		$search = $search ?: '';
		$searchFilterBuilder = new SearchFilterBuilder();
		$filter = $searchFilterBuilder->buildFilter($search);

		$limit = $pageNavigation?->getLimit() ?: 50;
		$offset = $pageNavigation?->getOffset() ?: 0;

		$securityContext = $storage->getSecurityContext($currentUser->getId());

		$queryParameters = [
			'select' => ['ID'],
			'filter' => array_merge(
				[
					'STORAGE_ID' => $storage->getId(),
					'TYPE' => FileTable::TYPE,
					'DELETED_TYPE' => FileTable::DELETED_TYPE_NONE,
				],
				$filter,
			),
			'order' => ['UPDATE_TIME' => 'DESC'],
			'limit' => $limit,
			'offset' => $offset,
		];

		$driver = Driver::getInstance();
		$queryParameters = $driver->getRightsManager()->addRightsCheck(
			$securityContext,
			$queryParameters,
			['ID', 'CREATED_BY']
		);

		$itemIds = [];
		$result = File::getList($queryParameters);
		foreach ($result as $item)
		{
			$itemIds[] = $item['ID'];
		}

		if (empty($itemIds))
		{
			return new Page('items', [], 0);
		}

		$items = array_flip($itemIds);

		$dataResult = File::loadBatchById($itemIds);
		foreach ($dataResult as $child)
		{
			$items[(int)$child->getId()] = $child;
		}

		return new Page('items', array_values($items), 0);
	}
}