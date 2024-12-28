<?php
declare(strict_types=1);

namespace Bitrix\Disk\Controller\DataProviders;

use Bitrix\Disk;
use Bitrix\Disk\Controller\Types\Folder\ContextTypeResolver;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Error;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Result;

final class ChildrenDataProvider
{
	public function getChildren(
		Disk\Folder $folder,
		CurrentUser $currentUser,
		string $search = null,
		string $searchScope = 'currentFolder',
		bool $showRights = false,
		array $context = [],
		array $order = [],
		PageNavigation $pageNavigation = null
	): Result
	{
		$result = new Result();

		$orderResult = $this->validateOrder($order);
		if (!$orderResult->isSuccess())
		{
			return $result->addErrors($orderResult->getErrors());
		}

		$order = [
			'TYPE' => 'ASC',
			...$order,
		];
		if (empty($order['NAME']))
		{
			$order['NAME'] = 'ASC';
		}

		$storage = $folder->getStorage();
		if (!$storage)
		{
			return $result->addError(new Error('Could not find storage for folder.'));
		}

		$search = $search ?: '';
		$searchFilterBuilder = new Disk\Search\SearchFilterBuilder();
		$searchFilter = $searchFilterBuilder->buildFilter($search);

		$limit = $pageNavigation?->getLimit() ?: 50;
		$offset = $pageNavigation?->getOffset() ?: 0;

		$securityContext = $storage->getSecurityContext($currentUser->getId());

		$filter = [
			'PARENT_ID' => $folder->getRealObjectId(),
			'DELETED_TYPE' => Disk\Internals\ObjectTable::DELETED_TYPE_NONE,
		];
		if ($searchScope === 'subfolders')
		{
			$filter['PATH_CHILD.PARENT_ID'] = $folder->getRealObjectId();
			$filter['!PATH_CHILD.OBJECT_ID'] = $folder->getRealObjectId();
			unset($filter['PARENT_ID']);
		}

		$queryParameters = [
			'select' => ['ID'],
			'filter' => array_merge($filter, $searchFilter),
			'order' => $order,
			'limit' => $limit,
			'offset' => $offset,
		];

		$driver = Disk\Driver::getInstance();
		$queryParameters = $driver->getRightsManager()->addRightsCheck(
			$securityContext,
			$queryParameters,
			['ID', 'CREATED_BY']
		);

		$childIds = [];
		$childResult = Disk\Folder::getList($queryParameters);
		foreach ($childResult as $child)
		{
			$childIds[] = (int)$child['ID'];
		}

		if (empty($childIds))
		{
			$result->setData([
				'children' => [],
				'total' => 0,
			]);

			return $result;
		}

		if ($showRights)
		{
			$folder->preloadOperationsForSpecifiedObjects($childIds, $securityContext);
		}

		$children = array_flip($childIds);

		$contextTypeResolver = null;
		$contextStorageId = $context['storageId'] ?? null;
		if ($contextStorageId)
		{
			$contextTypeResolver = new ContextTypeResolver((int)$contextStorageId);
		}

		$childDataResult = Disk\BaseObject::loadBatchById($childIds);
		foreach ($childDataResult as $childObject)
		{
			$contextType = null;
			if ($contextTypeResolver && ($childObject instanceof Disk\Folder))
			{
				$contextType = $contextTypeResolver->resolveByLazyContextType($childObject);
			}

			if ($showRights)
			{
				$rights = $driver->getRightsManager()->getAvailableActions($childObject, $securityContext);
				$children[(int)$childObject->getId()] = [
					...$childObject->jsonSerialize(),
					'rights' => $rights,
					'folderContextType' => $contextType,
				];
			}
			else
			{
				$children[(int)$childObject->getId()] = [
					...$childObject->jsonSerialize(),
					'folderContextType' => $contextType,
				];
			}
		}

		$contextTypeResolver?->resolveAllPending();

		$result->setData([
			'children' => array_values($children),
			'total' => 0,
		]);

		return $result;
	}

	private function validateOrder(array $order): Result
	{
		$whitelistColumns = [
			'NAME', 'CREATE_TIME', 'UPDATE_TIME', 'SIZE', 'ID',
		];

		$result = new Result();
		foreach ($order as $column => $direction)
		{
			if (!\in_array($column, $whitelistColumns, true))
			{
				$result->addError(new Disk\Internals\Error\Error("Column '{$column}' is not allowed for sorting."));
			}
			if (!\in_array($direction, ['ASC', 'DESC'], true))
			{
				$result->addError(new Error("Direction '{$direction}' is not allowed for sorting."));
			}
		}

		return $result;
	}
}
