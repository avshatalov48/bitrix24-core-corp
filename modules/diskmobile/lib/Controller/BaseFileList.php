<?php

namespace Bitrix\DiskMobile\Controller;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Mobile\Provider\UserRepository;
use Psr\Container\NotFoundExceptionInterface;

class BaseFileList extends Base
{
	protected function getRequestedIds(array $extra = []): array
	{
		if (isset($extra['filterParams']['ID']) && is_array($extra['filterParams']['ID']))
		{
			return array_unique(
				array_map('intval', $extra['filterParams']['ID'])
			);
		}

		return [];
	}

	protected function withUsers(array $response): array
	{
		/** @var array<int,array> $items */
		$items = $response['items'] ?? [];
		$selectUpdatedBy = fn(array $item): int => intval($item['updatedBy'] ?? 0);
		$userIds = array_map($selectUpdatedBy, $items);

		$response['users'] = UserRepository::getByIds($userIds);

		return $response;
	}

	protected function withRealStorageIds(array $response): array
	{
		/** @var array<int,array> $items */
		$items = $response['items'] ?? [];

		$realItemIds = [];
		$realStorageIds = [];
		foreach ($items as $key => $item)
		{
			$response['items'][$key]['realStorageId'] = $item['storageId'];

			if ($item['realObjectId'] !== $item['id'])
			{
				$realItemIds[] = $item['realObjectId'];
			}
		}

		if (!empty($realItemIds))
		{
			$rows = \Bitrix\Disk\BaseObject::getList([
				'select' => ['ID', 'STORAGE_ID'],
				'filter' => ['@ID' => $realItemIds],
			]);
			while ($realItem = $rows->fetch())
			{
				$realStorageIds[(int)$realItem['ID']] = (int)$realItem['STORAGE_ID'];
			}

			foreach ($items as $key => $item)
			{
				if (isset($realStorageIds[$item['realObjectId']]))
				{
					$response['items'][$key]['realStorageId'] = $realStorageIds[$item['realObjectId']];
				}
			}
		}

		return $response;
	}

	protected function withStorages(array $response): array
	{
		/** @var array<int,array> $items */
		$items = $response['items'] ?? [];
		$storageIds = [];

		foreach ($items as $item)
		{
			$storageIds[] = intval($item['storageId'] ?? 0);
			$storageIds[] = intval($item['realStorageId'] ?? 0);
		}

		$storageIds = array_unique($storageIds);

		try
		{
			$storages = \Bitrix\Disk\Storage::loadBatchById($storageIds);
		}
		catch (ObjectNotFoundException|NotFoundExceptionInterface $e)
		{
			$storages = [];
		}

		$response['storages'] = $storages;

		return $response;
	}

	/**
	 * @param array[] $items
	 * @return void
	 */
	protected function subscribeToPullEvents(array $items = [], array $tags = []): void
	{
		if (Loader::includeModule('pull'))
		{
			$userId = CurrentUser::get()->getId();

			foreach ($items as $item)
			{
				$objectIdTag = "object_{$item['id']}";
				$realObjectIdTag = "object_{$item['realObjectId']}";

				\CPullWatch::Add($userId, $objectIdTag);

				if ($realObjectIdTag !== $objectIdTag)
				{
					\CPullWatch::Add($userId, $realObjectIdTag);
				}
			}

			foreach ($tags as $tag)
			{
				\CPullWatch::Add($userId, $tag);
			}
		}
	}
}
