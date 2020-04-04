<?php
namespace Bitrix\Tasks\Replica;

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

		if ($selectedItemId > 0)
		{

			$conn = \Bitrix\Main\Application::getConnection();
			$rc = $conn->query("
				SELECT i2.ID, i2.SORT_INDEX
				FROM b_tasks_checklist_items i1
				INNER JOIN b_tasks_checklist_items i2 on i2.TASK_ID = i1.TASK_ID
				WHERE i1.ID = ".(int)$selectedItemId."
				ORDER BY i2.SORT_INDEX ASC, i2.ID ASC
			");

			//Following code from \CTaskCheckListItem::moveItem :
			$arItems = array($selectedItemId => 0);
			$prevItemId = 0;
			$sortIndex = 1;
			while ($arItem = $rc->fetch())
			{
				if ($insertAfterItemId == $prevItemId)
					$arItems[$selectedItemId] = $sortIndex++;

				if ($arItem['ID'] != $selectedItemId)
					$arItems[$arItem['ID']] = $sortIndex++;

				$prevItemId = $arItem['ID'];
			}

			if ($insertAfterItemId == $prevItemId)
				$arItems[$selectedItemId] = $sortIndex;

			if ($arItems)
			{
				$sqlUpdate = "UPDATE b_tasks_checklist_items SET SORT_INDEX = CASE ID\n";

				foreach ($arItems as $id => $sortIndex)
					$sqlUpdate .= "WHEN $id THEN $sortIndex\n";

				$sqlUpdate .= "END WHERE ID IN (".implode(', ', array_keys($arItems)).")";

				$conn->query($sqlUpdate);
			}
		}
	}
}
