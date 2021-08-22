<?php
namespace Bitrix\Tasks\Scrum\Checklist;

use Bitrix\Tasks\CheckList\CheckListFacade;
use Bitrix\Tasks\Scrum\Internal\TypeChecklistTable;
use Bitrix\Tasks\Scrum\Internal\TypeChecklistTreeTable;

class TypeChecklistFacade extends CheckListFacade
{
	protected static $selectFields = [
		'ID',
		'ENTITY_ID',
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
		'ENTITY_ID',
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
		'ENTITY_ID',
		'CREATED_BY',
		'PARENT_ID',
		'TITLE',
		'SORT_INDEX',
		'IS_COMPLETE',
		'IS_IMPORTANT',
		'TOGGLED_BY',
		'TOGGLED_DATE',
	];

	public static $entityIdName = 'ENTITY_ID';

	public static $currentAccessAction;

	/**
	 * Returns class that extends abstract class CheckListTree
	 * @see CheckListTree
	 *
	 * @return string
	 */
	public static function getCheckListTree(): string
	{
		return TypeCheckListTree::class;
	}

	public static function getCheckListDataController(): string
	{
		return TypeChecklistTable::getClass();
	}

	public static function getCheckListTreeDataController(): string
	{
		return TypeChecklistTreeTable::getClass();
	}

	protected static function getAccessControllerClass(): string
	{
		return TypeAccessController::class;
	}

	public static function getCheckListMemberDataController()
	{
		return '';
	}

	public static function getItemsForEntity($entityId, $userId)
	{
		$items = static::getByEntityId($entityId);
		$items = static::fillActionsForItems($entityId, $userId, $items);

		return $items;
	}

	public static function getByEntityId($entityId)
	{
		return static::getList([], [static::$entityIdName => $entityId]);
	}

	public static function fillActionsForItems($entityId, $userId, $items)
	{
		if (empty($items))
		{
			return $items;
		}

		$items = array_map(
			static function($item) use ($entityId, $userId)
			{
				$item['ACTION'] = [
					'MODIFY' => true,
					'REMOVE' => true,
					'TOGGLE' => false,
				];

				return $item;
			},
			$items
		);

		return $items;
	}

	protected static function fillCommonAccessActions($entityId, $userId): void
	{
		static::$commonAccessActions[$entityId][$userId] = [
			self::ACTION_ADD => true,
			self::ACTION_REORDER => true,
		];
	}

	protected static function fillItemAccessActions($taskId, $checkList, $userId): void
	{
		$checkListId = $checkList->getFields()['ID'];

		static::$itemAccessActions[$taskId][$userId][$checkListId] = [
			self::ACTION_MODIFY => true,
			self::ACTION_REMOVE => true,
			self::ACTION_TOGGLE => false,
		];
	}

	public static function getFieldsForTable($fields): array
	{
		return [
			'ENTITY_ID' => $fields['ENTITY_ID'],
			'CREATED_BY' => $fields['CREATED_BY'],
			'TITLE' => $fields['TITLE'],
			'SORT_INDEX' => $fields['SORT_INDEX'],
			'IS_COMPLETE' => $fields['IS_COMPLETE'],
			'IS_IMPORTANT' => $fields['IS_IMPORTANT'],
		];
	}
}