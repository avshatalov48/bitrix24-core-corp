<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 *
 * THIS IS AN EXPERIMENTAL CLASS, DONT EXTEND
 *
 * This class should be used in components, inside agent functions, in rest, ajax and more, bringing unification to all places and processes
 */

namespace Bitrix\Tasks\Manager\Task;

use \Bitrix\Main\Localization\Loc;

use \Bitrix\Tasks\UI;
use \Bitrix\Tasks\Util\Error\Collection;

Loc::loadMessages(__FILE__);

class CheckList extends \Bitrix\Tasks\Manager
{
	public static function getIsMultiple()
	{
		return true;
	}

	public static function getListByParentEntity($userId, $taskId, array $parameters = array())
	{
		$data = array();
		$can = array();

		$task = static::getTask($userId, $taskId);

		if($task !== null && $task->checkCanRead())
		{
			list($items, $arMetaData) = \CTaskCheckListItem::fetchList($task, array('SORT_INDEX' => 'ASC'));
			unset($arMetaData);

			$i = 0;
			foreach ($items as $item)
			{
				$itemData = $item->getData($parameters['ESCAPE_DATA']);
				unset($itemData['TASK_ID']);

				if($parameters['DROP_PRIMARY'])
				{
					$itemId = 'n'.$i;
					unset($itemData['ID']);
					$itemCan = static::getFullRights();
				}
				else
				{
					$itemId = $item->getId();
					$itemCan = array(
						'MODIFY' => $item->isActionAllowed(\CTaskCheckListItem::ACTION_MODIFY),
						'REMOVE' => $item->isActionAllowed(\CTaskCheckListItem::ACTION_REMOVE),
						'TOGGLE' => $item->isActionAllowed(\CTaskCheckListItem::ACTION_TOGGLE)
					);
				}

                $itemData[static::ACT_KEY] = $itemCan;

				$data[$itemId] = $itemData;
				$can[$itemId]['ACTION'] = $itemCan; // deprecated

				$i++;
			}
		}

		return array('DATA' => $data, 'CAN' => $can);
	}

	public static function add($userId, array $data, array $parameters = array('PUBLIC_MODE' => false))
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		if(array_key_exists('TASK_ID', $data))
		{
			$taskId = intval($data['TASK_ID']);
			unset($data['TASK_ID']);
		}
		elseif(array_key_exists('_OWNER_ENTITY_ID_', $data))
		{
			$taskId = intval($data['_OWNER_ENTITY_ID_']);
			unset($data['_OWNER_ENTITY_ID_']);
		}

		if($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, \CTaskCheckListItem::getPublicFieldMap(), $errors);
		}

		$item = null;
		$itemId = false;
		if($errors->checkNoFatals())
		{
			$task = static::getTask($userId, $taskId);

			$data['TITLE'] = htmlspecialcharsback($data['TITLE']);
			$item = \CTaskCheckListItem::add($task, $data);
			$itemId = $item->getId();
		}
		$display = $data['TITLE'];
//		$display = UI::sanitizeString($display, array('a'=>array('href'), 'img'=>array('src')));
		$display = UI::convertBBCodeToHtml($display, array('PRESET'=>'BASIC'));

		return array(
			'DATA' => array('ID' => $itemId, 'DISPLAY'=>$display, 'TITLE'=>strip_tags($display)),
			'ERRORS' => $errors,
		);
	}

	public static function update($userId, $itemId, array $data, array $parameters = array('PUBLIC_MODE' => false))
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		if ($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, \CTaskCheckListItem::getPublicFieldMap(), $errors);
		}

		$item = null;
		$task = null;
		$display = null;

		if ($errors->checkNoFatals())
		{
			$taskId = 0;

			if (array_key_exists('TASK_ID', $parameters))
			{
				$taskId = intval($parameters['TASK_ID']);
			}

			if (!$taskId)
			{
				$taskId = \CTaskCheckListItem::getTaskIdByItemId($itemId);
			}

			if ($taskId)
			{
				$task = static::getTask($userId, $taskId);
				$item = new \CTaskCheckListItem($task, $itemId);

				try
				{
					$item->update($data);
				}
				catch (\TasksException $e)
				{
					$originMessage = $e->getMessageOrigin();
					$message = Loc::getMessage('TASKS_MANAGER_TASK_CHECKLIST_ITEMS').': '.$originMessage->messages[0]['text'];
					$errors->add($e->getCode(), $message);
				}
			}
			else
			{
				$errors->add('GETTING_TASK_ID_ERROR', Loc::getMessage('TASKS_MANAGER_TASK_CHECKLIST_GETTING_TASK_ID_ERROR'));
			}
		}

		if(is_a($item, "CTaskCheckListItem") && method_exists($item, 'getTitle'))
		{
			$display = htmlspecialcharsback($item->getTitle());
//			$display = UI::sanitizeString($display, array('a' => array('href'), 'img' => array('src')));
			$display = UI::convertBBCodeToHtml($display, array('PRESET' => 'BASIC'));
		}
		
		return array(
			'DATA' => array(
				'ID' => $itemId,
				'DISPLAY' => $display,
				'TITLE' => strip_tags($display)
			),
			'ERRORS' => $errors,
		);
	}

	public static function manageSet($userId, $taskId, array $items = array(), array $parameters = array('PUBLIC_MODE' => false, 'MODE' => self::MODE_ADD))
	{
		$errors = static::ensureHaveErrorCollection($parameters);
		$result = array(
			'DATA' => array(),
			'CAN' => array(),
			'ERRORS' => $errors
		);

		if(!static::checkSetPassed($items, $parameters['MODE']))
		{
			return $result;
		}

		$task = static::getTask($userId, $taskId);

		$data = array();

		// todo: when we edit task with rights loose, this condition will make troubles. temporary commented out
		/*
		if($parameters['MODE'] != self::MODE_ADD && !$task->isActionAllowed(\CTaskItem::ACTION_EDIT))
		{
			throw new \Bitrix\Tasks\ActionNotAllowedException();
		}
		*/

		// if you can edit the task, then you can move checklist items or add new ones.
		// optionally you can rename\toggle\delete if allowed by certain item`s permissions

		$currentItems = array('DATA' => array());
		if($parameters['MODE'] == static::MODE_UPDATE) // update existing
		{
			$currentItems = static::getListByParentEntity($userId, $taskId, $parameters);
		}

		$items = 					static::indexItemSets($items);
		$currentItems['DATA'] = 	static::indexItemSets($currentItems['DATA']);

		list($toAdd, $toUpdate, $toDelete) = static::makeDeltaSets($items, $currentItems['DATA']);
		if(empty($toAdd) && empty($toUpdate) && empty($toDelete))
		{
			return $result;
		}

		$toToggle = static::makeToggleSet($items, $currentItems['DATA']);

		// ensure we can do all the operations
		$itemName = Loc::getMessage('TASKS_MANAGER_TASK_CHECKLIST_ITEM_NAME');

		static::ensureCanToggle($toToggle, $currentItems, $errors);
		static::ensureCanUpdate($toUpdate, $currentItems, $errors, $itemName);
		static::ensureCanDelete($toDelete, $currentItems, $errors, $itemName);

		if($errors->checkNoFatals())
		{
			$items = static::reArrangeBySortIndex($items); // do not rely on request item order, it may be broken

			foreach($toDelete as $index)
			{
				$itemInst = new \CTaskCheckListItem($task, $index);
				$itemInst->delete();
			}

			// also re-order items here
			$sortIndex = 0;

			$toAdd = array_flip($toAdd);
			$toUpdate = array_flip($toUpdate);

			foreach($items as $index => $item)
			{
				unset($item['ACTION']); // todo: move ACTION cutoff above

				if(isset($toAdd[$index]))
				{
					$item['SORT_INDEX'] = $sortIndex;
					unset($item['ID']); // do not pass ID to add()

					$item['TASK_ID'] = $taskId;

					$opResult = static::add($userId, $item, $parameters);

					$data[$opResult['DATA']['ID']] = $opResult['DATA'];
				}
				else
				{
					if(isset($toUpdate[$index]))
					{
						// complete update
						$item['SORT_INDEX'] = $sortIndex;
						unset($item['ID']); // do not pass ID to update()

						static::update($userId, $index, $item, array_merge(array('TASK_ID' => $taskId), $parameters));
					}
					else
					{
						// update sort index only
						$itemInst = new \CTaskCheckListItem($task, $index);
						$itemInst->setSortIndex($sortIndex);
					}

					// todo: pass all errors from $opResult to $errors

					$data[$index] = array('ID' => $index); // todo: extended data here later ?
				}

				$sortIndex++;
			}
		}

		$result['DATA'] = $data;

		return $result;
	}

	public static function mergeData($primary = [], $secondary = [], bool $withAdditional = true): array
	{
		if (!$withAdditional)
		{
			return (array)$secondary;
		}

		return (array)$secondary + (array)$primary;
	}

	public static function parseSet(&$data)
	{
		if(array_key_exists('SE_CHECKLIST', $data) && is_array($data['SE_CHECKLIST']))
		{
			$newCl = array();
			$i = 0;
			foreach($data['SE_CHECKLIST'] as $listItem)
			{
				$id = array_key_exists('ID', $listItem) ? intval($listItem['ID']) : 0;

				if(!$id && !array_key_exists('ACTION', $listItem))
				{
					$listItem['ACTION'] = static::getFullRights();
				}

				$newCl[($id ? $id : 'n'.$i)] = $listItem;
				$i++;
			}

			$data['SE_CHECKLIST'] = $newCl;
		}
	}

	public static function adaptSet(&$data)
	{
		if (array_key_exists(static::getCode(true), $data))
		{
			$checkList = $data[static::getCode(true)];

			if (is_array($checkList))
			{
				$toSave = [];

				foreach ($checkList as $key => $value)
				{
					if ((string)$value['TITLE'] != '')
					{
						$toSave[] = $value;
					}
				}

				$data['CHECKLIST'] = $toSave;
			}
		}
	}

	protected static function reArrangeBySortIndex(array $items)
	{
		// not all items may have SORT_INDEX, so find the maximum and restore missing
		$maxSortIndex = 0;
		foreach($items as $item)
		{
			$sortIndex = intval($item['SORT_INDEX']);
			if($sortIndex > $maxSortIndex)
			{
				$maxSortIndex = $sortIndex;
			}
		}

		$index = array();
		foreach($items as $itemId => &$item)
		{
			if(!isset($item['SORT_INDEX']))
			{
				$item['SORT_INDEX'] = $maxSortIndex++;
			}

			$sortIndex = intval($item['SORT_INDEX']);
			$index[$sortIndex] = $itemId;
		}
		unset($item);

		ksort($index);

		$result = array();
		foreach($index as $itemId)
		{
			$result[$itemId] = $items[$itemId];
		}

		return $result;
	}

	protected static function ensureCanToggle(array $toToggleItems, array $currentItems, Collection $errors)
	{
		$errorMessage = Loc::getMessage('TASKS_MANAGER_TASK_CHECKLIST_CANT_TOGGLE');

		$inoperable = static::getItemsInoperable($toToggleItems, $currentItems, array('TOGGLE'));
		if(!empty($inoperable))
		{
			foreach($inoperable as $itemId)
			{
				$errors->add('TOGGLE_PERMISSION_DENIED', str_replace('#ID#', $itemId, $errorMessage), Collection::TYPE_FATAL, array('DATA' => array('ID' => $itemId)));
			}
		}
	}

	protected static function makeToggleSet(array $items, array $currentItemsData)
	{
		$result = array();

		foreach($items as $itemId => $item)
		{
			if(isset($currentItemsData[$itemId]))
			{
				if(isset($item['IS_COMPLETE']) && ((string) $item['IS_COMPLETE'] != (string) $currentItemsData[$itemId]['IS_COMPLETE']))
				{
					$result[$itemId] = true;
				}
			}
		}

		return array_keys($result);
	}

	protected static function getFullRights()
	{
		return array(
			'MODIFY' => true,
			'REMOVE' => true,
			'TOGGLE' => true
		);
	}
}