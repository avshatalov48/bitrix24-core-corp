<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 * 
 * @access private
 * 
 * This class should be used in components, inside agent functions, in rest, ajax and more, bringing unification to all places and processes
 */

namespace Bitrix\Tasks\Manager\Task;

use \Bitrix\Main\Loader;

use \Bitrix\Tasks\Util\Error\Collection;

final class ElapsedTime extends \Bitrix\Tasks\Manager
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
			list($items, $arMetaData) = \CTaskElapsedItem::fetchList($task);
			unset($arMetaData);

			foreach($items as $item)
			{
				$itemData = $item->getData($parameters['ESCAPE_DATA']);
				$data[$itemData['ID']] = $itemData;
				$can[$itemData['ID']]['ACTION'] = array(
					'MODIFY' => $item->isActionAllowed(\CTaskElapsedItem::ACTION_ELAPSED_TIME_MODIFY),
					'REMOVE' => $item->isActionAllowed(\CTaskElapsedItem::ACTION_ELAPSED_TIME_REMOVE)
				);
			}
		}

		return array(
			'DATA' => $data, 
			'CAN' => $can
		);
	}

	public static function add($userId, array $data, array $parameters = array('PUBLIC_MODE' => false))
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		$taskId = intval($data['TASK_ID']);
		unset($data['TASK_ID']);

		if($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, \CTaskElapsedItem::getPublicFieldMap(), $errors);
		}

		$result = array(
			"DATA" => array(),
			"ERRORS" => array(),
		);

		if($errors->checkNoFatals())
		{
			$task = static::getTask($userId, $taskId);
			$item = \CTaskElapsedItem::add($task, $data);

			$result['DATA']['ID'] = $item->getId();

			// bad practice, but as an exception for this time
			if($parameters['RETURN_ENTITY'])
			{
				$result = static::get($userId, $taskId, $item->getId(), $parameters);
			}
		}

		return $result;
	}

	public static function update($userId, $itemId, array $data, array $parameters = array('PUBLIC_MODE' => false))
	{
		$errors = static::ensureHaveErrorCollection($parameters);

		if ($parameters['PUBLIC_MODE'])
		{
			$data = static::filterData($data, \CTaskElapsedItem::getPublicFieldMap(), $errors);
		}

		$result = array(
			"DATA" => array(),
			"ERRORS" => array(),
		);

		if ($errors->checkNoFatals())
		{
			$taskId = 0;
			if(array_key_exists('TASK_ID', $parameters))
			{
				$taskId = intval($parameters['TASK_ID']);
			}
			if(!$taskId)
			{
				$taskId = static::getTaskId($itemId);
			}

			$task = static::getTask($userId, $taskId);
			$item = new \CTaskElapsedItem($task, $itemId);
			$item->update($data);

			$result['DATA']['ID'] = $item->getId();

			// bad practice, but as an exception for this time
			if($parameters['RETURN_ENTITY'])
			{
				$result = static::get($userId, $taskId, $itemId, $parameters);
			}
		}

		return $result;
	}

	// todo: there should not be $taskId in arguments, refactor!
	private static function get($userId, $taskId, $itemId, array $parameters = array())
	{
		$errors = static::ensureHaveErrorCollection($parameters);
		$data = array();
		$can = array();

		$task = static::getTask($userId, $taskId);

		if($task !== null && $task->checkCanRead())
		{
			list($items, $arMetaData) = \CTaskElapsedItem::fetchList($task, array(), array("ID" => $itemId));
			unset($arMetaData);

			if (isset($items[0]))
			{
				$item = $items[0];
				$itemData = $item->getData($parameters['ESCAPE_DATA']);
				$data = $itemData;
				$can = array(
					'MODIFY' => $item->isActionAllowed(\CTaskElapsedItem::ACTION_ELAPSED_TIME_MODIFY),
					'REMOVE' => $item->isActionAllowed(\CTaskElapsedItem::ACTION_ELAPSED_TIME_REMOVE)
				);
			}
			else
			{
				$errors->add('ITEM_NOT_FOUND', 'Item not found');
			}
		}

		return array(
			'DATA' => $data,
			'CAN' => $can,
			'ERRORS' => $errors
		);
	}

	private static function getTaskId($itemId)
	{
		$item = \CTaskElapsedTime::getList(array(), array('ID' => $itemId), array('skipJoinUsers' => true))->fetch();
		if(is_array($item) && !empty($item))
		{
			return $item['TASK_ID'];
		}

		return false;
	}
}