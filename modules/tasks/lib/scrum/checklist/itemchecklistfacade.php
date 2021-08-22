<?php
namespace Bitrix\Tasks\Scrum\Checklist;

use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\Scrum\Internal\ItemChecklistTable;
use Bitrix\Tasks\Scrum\Internal\ItemChecklistTreeTable;

class ItemChecklistFacade extends CheckListFacade
{
	protected static $selectFields = [
		'ID',
		'ITEM_ID',
		'CREATED_BY',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
		'TOGGLED_BY',
		'TOGGLED_DATE'
	];
	protected static $filterFields = [
		'ID',
		'ITEM_ID',
		'CREATED_BY',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
		'TOGGLED_BY',
		'TOGGLED_DATE',
	];
	protected static $orderFields = [
		'ID',
		'ITEM_ID',
		'CREATED_BY',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
		'TOGGLED_BY',
		'TOGGLED_DATE',
	];

	public static $entityIdName = 'ITEM_ID';

	public static $currentAccessAction;

	/**
	 * Returns class that extends abstract class CheckListTree
	 * @see CheckListTree
	 *
	 * @return string
	 */
	public static function getCheckListTree(): string
	{
		return ItemCheckListTree::class;
	}

	public static function getCheckListDataController(): string
	{
		return ItemChecklistTable::getClass();
	}

	public static function getCheckListTreeDataController(): string
	{
		return ItemChecklistTreeTable::getClass();
	}

	protected static function getAccessControllerClass(): string
	{
		return ItemAccessController::class;
	}

	public static function getCheckListMemberDataController()
	{
		return '';
	}

	public static function getItemsForEntity($itemId, $userId)
	{
		$items = static::getByEntityId($itemId);
		$items = static::fillActionsForItems($itemId, $userId, $items);

		return $items;
	}

	public static function getByEntityId($itemId)
	{
		return static::getList([], [static::$entityIdName => $itemId]);
	}

	public static function fillActionsForItems($itemId, $userId, $items)
	{
		if (empty($items))
		{
			return $items;
		}

		$items = array_map(
			static function($item) use ($itemId, $userId)
			{
				$item['ACTION'] = [
					'MODIFY' => false,
					'REMOVE' => false,
					'TOGGLE' => true,
				];

				return $item;
			},
			$items
		);

		return $items;
	}

	protected static function fillCommonAccessActions($itemId, $userId): void
	{
		static::$commonAccessActions[$itemId][$userId] = [
			self::ACTION_ADD => true,
			self::ACTION_REORDER => true,
		];
	}

	protected static function fillItemAccessActions($taskId, $checkList, $userId): void
	{
		$checkListId = $checkList->getFields()['ID'];

		static::$itemAccessActions[$taskId][$userId][$checkListId] = [
			self::ACTION_MODIFY => false,
			self::ACTION_REMOVE => false,
			self::ACTION_TOGGLE => true,
		];
	}

	public static function getFieldsForTable($fields): array
	{
		return [
			'ITEM_ID' => $fields['ENTITY_ID'],
			'CREATED_BY' => $fields['CREATED_BY'],
			'TITLE' => $fields['TITLE'],
			'SORT_INDEX' => $fields['SORT_INDEX'],
			'IS_COMPLETE' => $fields['IS_COMPLETE'],
			'IS_IMPORTANT' => $fields['IS_IMPORTANT'],
		];
	}
}