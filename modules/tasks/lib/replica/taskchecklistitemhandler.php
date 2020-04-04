<?php
namespace Bitrix\Tasks\Replica;

use Bitrix\Main\Application;
use Bitrix\Tasks\CheckList\Task\TaskCheckListTree;

class TaskChecklistItemHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_tasks_checklist_items";
	protected $moduleId = "tasks";
	protected $className = "\\Bitrix\\Tasks\\Internals\\Task\\CheckListTable";

	protected $primary = array(
		"ID" => "auto_increment",
	);
	protected $predicates = array(
		"TASK_ID" => "b_tasks.ID",
		"CREATED_BY" => "b_user.ID",
		"TOGGLED_BY" => "b_user.ID",
	);
	protected $translation = array(
		"ID" => "b_tasks_checklist_items.ID",
		"TASK_ID" => "b_tasks.ID",
		"CREATED_BY" => "b_user.ID",
		"TOGGLED_BY" => "b_user.ID",
	);

	/**
	 * Registers event handlers for database operations like add new, update or delete.
	 *
	 * @return void
	 */
	public function initDataManagerEvents()
	{
		parent::initDataManagerEvents();
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->addEventHandler(
			"tasks",
			"OnCheckListItemMoveItem",
			array($this, "onCheckListItemMoveItem")
		);
		$eventManager->addEventHandler(
			"replica",
			"OnExecuteCheckListItemMoveItem",
			array($this, "onExecuteCheckListItemMoveItem")
		);
	}

	/**
	 * Tasks event OnCheckListItemMoveItem handler.
	 *
	 * @param integer $selectedItemId Moved item identifier.
	 * @param integer $insertAfterItemId New item position.
	 *
	 * @return boolean
	 * @see \CTaskCheckListItem::moveItem()
	 */
	function onCheckListItemMoveItem($selectedItemId, $insertAfterItemId)
	{
		$operation = new \Bitrix\Replica\Db\Execute();
		$operation->writeToLog(
			"CheckListItemMoveItem",
			array(
				array(
					"relation" => "b_tasks_checklist_items.ID",
					"value" => $selectedItemId,
				),
				array(
					"relation" => "b_tasks_checklist_items.ID",
					"value" => $insertAfterItemId,
				),
			)
		);
		return true;
	}

	/**
	 * Remote event handler.
	 *
	 * @param \Bitrix\Main\Event $event Contains two parameters: 0 - moved item, 1 - move position.
	 *
	 * @return void
	 * @see \CTaskCheckListItem::moveItem()
	 * @see \Bitrix\Tasks\Replica\TaskChecklistItemHandler::onCheckListItemMoveItem()
	 */
	function onExecuteCheckListItemMoveItem(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();
		$selectedItemId = (int)$parameters[0];
		$insertAfterItemId = (int)$parameters[1];

		if ($selectedItemId > 0 && $insertAfterItemId > 0)
		{
			$connection = Application::getConnection();

			$items = $this->getItems($selectedItemId, $connection);

			$newSortIndex = (int)$items[$insertAfterItemId]['SORT_INDEX'] + 1;
			$oldSortIndex = (int)$items[$selectedItemId]['SORT_INDEX'];
			$newParentId = (int)$items[$insertAfterItemId]['PARENT_ID'];
			$oldParentId = (int)$items[$selectedItemId]['PARENT_ID'];

			$nextIndex = $newSortIndex + 1;
			$prevIndex = $oldSortIndex;

			$recountedSortIndexes = [];
			foreach ($items as $id => $item)
			{
				$parentId = (int)$item['PARENT_ID'];
				$sortIndex = (int)$item['SORT_INDEX'];

				if ($parentId === $newParentId && $sortIndex >= $newSortIndex)
				{
					$recountedSortIndexes[$id] = $nextIndex;
					$nextIndex++;
				}
				else if ($parentId === $oldParentId && $sortIndex > $oldSortIndex)
				{
					$recountedSortIndexes[$id] = $prevIndex;
					$prevIndex++;
				}
			}
			$recountedSortIndexes[$selectedItemId] = $newSortIndex;

			$this->updateSortIndexes($recountedSortIndexes, $connection);
			$this->updateParents($newParentId, $oldParentId, $selectedItemId);
		}
	}

	private function getItems($itemId, $connection)
	{
		$itemsResult = $connection->query("
			SELECT i2.ID, i2.SORT_INDEX, PARENT_ID
			FROM b_tasks_checklist_items i1
			INNER JOIN b_tasks_checklist_items i2 on i2.TASK_ID = i1.TASK_ID
			LEFT JOIN b_tasks_checklist_items_tree ON CHILD_ID = i2.ID AND LEVEL = 1
			WHERE i1.ID = {$itemId}
			ORDER BY i2.SORT_INDEX, i2.ID
		");

		$items = [];
		while ($item = $itemsResult->fetch())
		{
			$item['PARENT_ID'] = ($item['PARENT_ID'] === null? 0 : (int)$item['PARENT_ID']);
			$items[$item['ID']] = $item;
		}

		return $items;
	}

	private function updateSortIndexes($recountedSortIndexes, $connection)
	{
		$sqlUpdate = "UPDATE b_tasks_checklist_items SET SORT_INDEX = CASE ID\n";

		foreach ($recountedSortIndexes as $id => $sortIndex)
		{
			$sqlUpdate .= "WHEN $id THEN $sortIndex\n";
		}

		$sqlUpdate .= "END WHERE ID IN (".implode(', ', array_keys($recountedSortIndexes)).")";

		$connection->query($sqlUpdate);
	}

	private function updateParents($newParentId, $oldParentId, $selectedItemId)
	{
		if ($newParentId !== $oldParentId)
		{
			if ($newParentId === 0)
			{
				TaskCheckListTree::detachSubTree($selectedItemId);
			}
			else
			{
				TaskCheckListTree::attach($selectedItemId, $newParentId);
			}
		}
	}
}
