<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity\Favorites\Favorites;
use Bitrix\Booking\Entity\Favorites\FavoritesType;
use Bitrix\Booking\Entity\Resource\ResourceCollection;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Model\FavoritesTable;
use Bitrix\Booking\Internals\Model\ResourceTable;
use Bitrix\Booking\Internals\Repository\FavoritesRepositoryInterface;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\FavoritesMapper;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ResourceMapper;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Query\Query;

class FavoritesRepository implements FavoritesRepositoryInterface
{
	private ResourceMapper $resourceMapper;

	public function __construct(FavoritesMapper $mapper)
	{
		$this->resourceMapper = Container::getResourceRepositoryMapper();
	}

	public function getList(int $managerId): Favorites
	{
		$addedResourceQuery = FavoritesTable::query()
			->setSelect(['RESOURCE_ID'])
			->where('TYPE', FavoritesType::Added->value)
			->where('MANAGER_ID', '=', $managerId)
			->getQuery()
		;

		$removedResourceQuery = FavoritesTable::query()
			->setSelect(['RESOURCE_ID'])
			->where('TYPE', FavoritesType::Removed->value)
			->where('MANAGER_ID', '=', $managerId)
			->getQuery()
		;

		$ormResourceCollection = ResourceTable::query()
			->setSelect([
				'*',
				'TYPE',
				'DATA',
				'SETTINGS',
				'NOTIFICATION_SETTINGS',
			])
			->where(Query::filter()
				->logic('OR')
				->where(Query::filter()
					->logic('AND')
					->where('IS_MAIN', '=', 'Y')
					->whereNotIn('ID', new SqlExpression($removedResourceQuery))
				)
				->where(Query::filter()
					->logic('AND')
					->where('IS_MAIN', '=', 'N')
					->whereIn('ID', new SqlExpression($addedResourceQuery))
				)
			)
			->exec()
			->fetchCollection()
		;

		$resources = [];
		foreach ($ormResourceCollection as $ormResource)
		{
			$resources[] = $this->resourceMapper->convertFromOrm($ormResource);
		}

		return (new Favorites())
			->setResources(new ResourceCollection(...$resources))
			->setManagerId($managerId)
		;
	}

	public function removePrimary(int $managerId, array $resourcesIds): void
	{
		if (empty($resourcesIds))
		{
			return;
		}

		FavoritesTable::deleteByFilter([
			'=MANAGER_ID' => $managerId,
			'@RESOURCE_ID' => $resourcesIds,
		]);

		FavoritesTable::insertIgnoreMulti(
			array_map(
				static fn(int $resourceId) => [
					'MANAGER_ID' => $managerId,
					'RESOURCE_ID' => $resourceId,
					'TYPE' => FavoritesType::Removed->value,
				],
				$resourcesIds,
			),
		);
	}

	public function removeSecondary(int $managerId, array $resourcesIds): void
	{
		if (empty($resourcesIds))
		{
			return;
		}

		FavoritesTable::deleteByFilter([
			'=MANAGER_ID' => $managerId,
			'@RESOURCE_ID' => $resourcesIds,
		]);
	}

	public function addPrimary(int $managerId, array $resourceIds): void
	{
		if (empty($resourceIds))
		{
			return;
		}

		FavoritesTable::deleteByFilter([
			'=MANAGER_ID' => $managerId,
			'@RESOURCE_ID' => $resourceIds,
		]);
	}

	public function addSecondary(int $managerId, array $resourceIds): void
	{
		if (empty($resourceIds))
		{
			return;
		}

		FavoritesTable::deleteByFilter([
			'=MANAGER_ID' => $managerId,
			'@RESOURCE_ID' => $resourceIds,
		]);

		FavoritesTable::addMulti(
			array_map(
				static fn(int $resourceId) => [
					'MANAGER_ID' => $managerId,
					'RESOURCE_ID' => $resourceId,
					'TYPE' => FavoritesType::Added->value,
				],
				$resourceIds,
			),
			true
		);
	}

	public function filterSecondary(array $resourceIds, array $primaryResourceIds): array
	{
		if (empty($primaryResourceIds))
		{
			return $resourceIds;
		}

		return array_filter($resourceIds, function($resourceId) use ($primaryResourceIds) {
			return !in_array($resourceId, $primaryResourceIds);
		});
	}

	public function pushPrimary(array $resourceIds): void
	{
		if (empty($resourceIds))
		{
			return;
		}

		FavoritesTable::deleteByFilter([
			'@RESOURCE_ID' => $resourceIds,
		]);
	}
}
