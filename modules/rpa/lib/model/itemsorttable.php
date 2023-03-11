<?php

namespace Bitrix\Rpa\Model;

use Bitrix\Main\Application;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Result;
use Bitrix\Rpa\Driver;

/**
 * Class ItemSortTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION
 * @method static EO_ItemSort_Query query()
 * @method static EO_ItemSort_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_ItemSort_Result getById($id)
 * @method static EO_ItemSort_Result getList(array $parameters = [])
 * @method static EO_ItemSort_Entity getEntity()
 * @method static \Bitrix\Rpa\Model\EO_ItemSort createObject($setDefaultValues = true)
 * @method static \Bitrix\Rpa\Model\EO_ItemSort_Collection createCollection()
 * @method static \Bitrix\Rpa\Model\EO_ItemSort wakeUpObject($row)
 * @method static \Bitrix\Rpa\Model\EO_ItemSort_Collection wakeUpCollection($rows)
 */
class ItemSortTable extends ORM\Data\DataManager
{
	protected const DEFAULT_MAX_SORT = 1000000;

	public static function getTableName(): string
	{
		return 'b_rpa_item_sort';
	}

	public static function getMap(): array
	{
		return [
			(new ORM\Fields\IntegerField('ID'))
				->configureAutocomplete()
				->configurePrimary(),
			(new ORM\Fields\IntegerField('USER_ID'))
				->configureRequired(),
			(new ORM\Fields\IntegerField('TYPE_ID'))
				->configureRequired(),
			(new ORM\Fields\IntegerField('ITEM_ID'))
				->configureRequired(),
			(new ORM\Fields\IntegerField('SORT'))
				->configureRequired(),
		];
	}

	public static function setSortForItem(Item $item, int $userId, int $sort): ORM\Data\Result
	{
		$record = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=ITEM_ID' => $item->getId(),
				'=TYPE_ID' => $item->getType()->getId(),
				'=USER_ID' => $userId,
			],
			'limit' => 1,
		])->fetch();

		if($record)
		{
			$result = static::update($record['ID'], [
				'SORT' => $sort,
			]);
		}
		else
		{
			$result = static::add([
				'ITEM_ID' => $item->getId(),
				'TYPE_ID' => $item->getType()->getId(),
				'USER_ID' => $userId,
				'SORT' => $sort,
			]);
		}

		return $result;
	}

	public static function removeForItem(int $typeId, int $itemId): Result
	{
		$result = new Result();

		$list = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=ITEM_ID' => $itemId,
				'=TYPE_ID' => $typeId,
			],
		]);

		while($record = $list->fetch())
		{
			$deleteResult = static::delete($record['ID']);
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	public static function removeByTypeId(int $typeId): Result
	{
		$result = new Result();

		$list = static::getList([
			'select' => ['ID'],
			'filter' => [
				'=TYPE_ID' => $typeId,
			],
		]);

		while($record = $list->fetch())
		{
			$deleteResult = static::delete($record['ID']);
			if(!$deleteResult->isSuccess())
			{
				$result->addErrors($deleteResult->getErrors());
			}
		}

		return $result;
	}

	// todo refactoring
	public static function getSort(int $userId, Type $type, int $stageId, int $previousItemId = null, bool $isResort = true): int
	{
		$nextSort = $previousSort = null;
		$previousItem = $type->getItem($previousItemId);
		$typeId = $type->getId();
		if($previousItem)
		{
			$record = static::getList([
				'select' => ['SORT'],
				'filter' => [
					'=ITEM_ID' => $previousItem->getId(),
					'=TYPE_ID' => $typeId,
					'=USER_ID' => $userId,
				],
			])->fetch();
			if($record)
			{
				$previousSort = (int)$record['SORT'];
			}
			else
			{
				static::fillItemsSortInStageBeforeItem($userId, $previousItem);
				$previousSort = static::getMaxSort($userId, $previousItem->getStage());
			}
		}

		$nextItem = static::getList([
			'select' => ['ID', 'SORT'],
			'order' => [
				'SORT' => 'ASC',
			],
			'filter' => [
				'=TYPE_ID' => $typeId,
				'=USER_ID' => $userId,
				'>=SORT' => $previousSort ?: 0,
				'=ITEM.STAGE_ID' => $stageId,
				'!=ITEM_ID' => $previousItemId,
			],
			'runtime' => [
				static::getItemReferenceField($type)
			],
			'limit' => 1,
		])->fetch();
		if($nextItem)
		{
			$nextSort = (int)$nextItem['SORT'];
		}

		if(!$nextSort)
		{
			$sort = $previousSort + static::DEFAULT_MAX_SORT;
		}
		else
		{
			$sort = (int) (floor(($nextSort - $previousSort) / 2) + $previousSort);
		}

		if($sort === 0 || $previousSort === $sort || $nextSort === $sort)
		{
			if($isResort)
			{
				static::resortItems($userId, $type, $stageId);
				$sort = static::getSort($userId, $type, $stageId, $previousItemId, false);
			}
		}

		return $sort;
	}

	protected static function resortItems(int $userId, Type $type, int $stageId): void
	{
		$itemIds = [];
		$items = static::getList([
			'select' => [
				'ITEM_ID',
			],
			'order' => [
				'sort' => 'ASC',
			],
			'filter' => [
				'=USER_ID' => $userId,
				'=TYPE_ID' => $type->getId(),
				'=ITEM.STAGE_ID' => $stageId,
			],
			'runtime' => [
				static::getItemReferenceField($type)
			],
		]);
		while($item = $items->fetch())
		{
			$itemIds[] = $item['ITEM_ID'];
		}

		if(empty($itemIds))
		{
			return;
		}

		$sort = static::DEFAULT_MAX_SORT;
		$items = static::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'=USER_ID' => $userId,
				'=TYPE_ID' => $type->getId(),
				'=ITEM_ID' => $itemIds,
			],
		]);
		while($item = $items->fetch())
		{
			static::update($item['ID'], ['SORT' => $sort]);
			$sort += static::DEFAULT_MAX_SORT;
		}
	}

	/**
	 * Fills user sort for all items in the stage up to $previousItem
	 *
	 * @param int $userId
	 * @param Item $beforeItem
	 */
	public static function fillItemsSortInStageBeforeItem(int $userId, Item $beforeItem): void
	{
		$stage = $beforeItem->getStage();
		if(!$stage)
		{
			return;
		}
		$maxSort = static::getMaxSort($userId, $stage);
		$items = $stage->getUserSortedItems([
			'select' => ['ID', 'MOVED_TIME'],
			'filter' => [
				'USORT' => null,
				[
					'LOGIC' => 'OR',
					[
						'>MOVED_TIME' => $beforeItem->getMovedTime(),
					],
					[
						'=MOVED_TIME' => null,
						'<ID' => $beforeItem->getId(),
					],
					[
						'=ID' => $beforeItem->getId(),
					]
				]
			],
		]);

		$data = [];
		$nextSort = $maxSort;
		foreach($items as $item)
		{
			$data[] = [
				'USER_ID' => $userId,
				'TYPE_ID' => $beforeItem->getType()->getId(),
				'ITEM_ID' => $item->getId(),
				'SORT' => $nextSort,
			];
			$nextSort += static::DEFAULT_MAX_SORT;
			if($item->getId() === $beforeItem->getId())
			{
				break;
			}
		}

		if(!empty($data))
		{
			Application::getConnection()->addMulti(static::getTableName(), $data);
		}
	}

	public static function getMaxSort(int $userId, Stage $stage): int
	{
		$items = $stage->getUserSortedItems([
			'order' => [
				'USORT' => 'DESC',
			],
			'filter' => [
				'!USORT' => null,
			],
			'limit' => 1,
		], $userId);
		foreach($items as $item)
		{
			return $item->get('USER_SORT')->get('SORT');
		}

		return static::DEFAULT_MAX_SORT;
	}

	public static function getItemReferenceField(Type $type): Reference
	{
		$factory = Driver::getInstance()->getFactory();
		if(method_exists($factory, 'getItemEntity'))
		{
			$itemEntity = $factory->getItemEntity($type);
		}
		else
		{
			$itemEntity = TypeTable::compileEntity($type);
		}
		return new Reference('ITEM', $itemEntity, [
			'=this.ITEM_ID' => 'ref.ID',
		]);
	}
}