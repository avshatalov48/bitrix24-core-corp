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
 * This class should be used in components, inside agent functions, in rest, ajax and more, bringing unification to all places and processes.
 *
 */

namespace Bitrix\Tasks;

use \Bitrix\Main\Localization\Loc;

use \Bitrix\Tasks\Util\Error\Collection;

Loc::loadMessages(__FILE__);

abstract class Manager
{
	const SE_PREFIX = 		'SE_'; // sub-entity prefix
    const ACT_KEY =         'ACTION'; // reserved key to keep allowed action map

	const MODE_ADD = 		0x01;
	const MODE_UPDATE = 	0x02;

	public static function getIsMultiple()
	{
		return false;
	}

	public static function getCode($prefix = false)
	{
		$class = explode('\\', (string) get_called_class());
		if(empty($class) && empty($class[count($class) - 1]))
		{
			throw new \Bitrix\Main\SystemException('Cannot identify sub-entity code');
		}

		return ($prefix ? static::SE_PREFIX : '').ToUpper($class[count($class) - 1]);
	}

	protected static function getTask($userId, $taskId)
	{
		// on the same $userId and $taskId this will return the same task instance from cache
		return \CTaskItem::getInstance($taskId, $userId);
	}

	protected static function ensureHaveErrorCollection(&$parameters)
	{
		if(!is_object($parameters['ERRORS']))
		{
			$parameters['ERRORS'] = new Collection();
		}

		return $parameters['ERRORS'];
	}

	protected static function filterData(array $data, array $fieldMap, Collection $errors)
	{
		// READ, WRITE, SORT, FILTER, DATE

		foreach($data as $field => $value)
		{
			$start = substr($field, 0, 3);
			if($start == 'UF_' || $start == static::SE_PREFIX) // we have no other fields that start from SE_ or UF_, so allow
			{
				continue;
			}

			if(!isset($fieldMap[$field]))
			{
				$errors->add('UNKNOWN_FIELD', 'Unknown field given: "'.$field.'"', Collection::TYPE_FATAL, array('FIELD' => $field));
			}
			elseif(!$fieldMap[$field][1]) // WRITE
			{
				$errors->add('FIELD_NOT_ALLOWED', 'Field is not allowed to write: "'.$field.'"', Collection::TYPE_FATAL, array('FIELD' => $field));
			}
		}

		return $data;
	}

	protected static function makeDeltaSets(array $items, array $currentItemsData)
	{
		$toAdd = array();
		$toUpdate = array();
		$toDelete = array();

		foreach($items as $id => $item)
		{
			if(isset($currentItemsData[$id]))
			{
				if(static::detectItemChanged($currentItemsData[$id], $items[$id]))
				{
					$toUpdate[$id] = true;
				}
			}
			else
			{
				$toAdd[$id] = true;
			}
		}

		foreach($currentItemsData as $id => $item)
		{
			if(!isset($items[$id]))
			{
				$toDelete[$id] = true;
			}
		}

		return array(array_keys($toAdd), array_keys($toUpdate), array_keys($toDelete));
	}

	protected static function detectItemChanged(array $itemThen, array $itemNow)
	{
		$changed = false;
		foreach($itemNow as $fld => $value)
		{
			if(!isset($itemThen[$fld]) || ((string) $itemThen[$fld] != (string) $itemNow[$fld]))
			{
				$changed = true;
				break;
			}
		}

		return $changed;
	}

	// permission check

	protected static function ensureCanUpdate(array $toUpdateItems, array $currentItems, Collection $errors, $itemName = '')
	{
		$inoperable = static::getItemsInoperable($toUpdateItems, $currentItems, array('MODIFY', 'EDIT'));
		if(!empty($inoperable))
		{
			if((string) $itemName == '')
			{
				$itemName = Loc::getMessage('TASKS_MANAGER_TASK_ITEM_NAME');
			}
			$errorMessage = str_replace('#ITEM_NAME#', $itemName, Loc::getMessage('TASKS_MANAGER_TASK_CANT_UPDATE'));

			foreach($inoperable as $itemId)
			{
				$errors->add('UPDATE_PERMISSION_DENIED', str_replace('#ID#', $itemId, $errorMessage), Collection::TYPE_FATAL, array('DATA' => array('ID' => $itemId)));
			}
		}
	}

	protected static function ensureCanDelete(array $toDeleteItems, array $currentItems, Collection $errors, $itemName = '')
	{
		$inoperable = static::getItemsInoperable($toDeleteItems, $currentItems, array('DELETE', 'REMOVE'));
		if(!empty($inoperable))
		{
			if((string) $itemName == '')
			{
				$itemName = Loc::getMessage('TASKS_MANAGER_TASK_ITEM_NAME');
			}
			$errorMessage = str_replace('#ITEM_NAME#', $itemName, Loc::getMessage('TASKS_MANAGER_TASK_CANT_DELETE'));

			foreach($inoperable as $itemId)
			{
				$errors->add('DELETE_PERMISSION_DENIED', str_replace('#ID#', $itemId, $errorMessage), Collection::TYPE_FATAL, array('DATA' => array('ID' => $itemId)));
			}
		}
	}

	protected static function getItemsInoperable(array $toActionItems, array $currentItems, array $actions)
	{
		$inoperable = array();
		foreach($toActionItems as $itemId)
		{
			if(isset($currentItems['DATA'][$itemId]))
			{
				$canDo = false;
				foreach($actions as $action)
				{
					if(static::checkCanDoOnItem($currentItems['CAN'][$itemId], $action))
					{
						$canDo = true;
						break;
					}
				}

				if(!$canDo)
				{
					$inoperable[$itemId] = true;
				}
			}
		}

		return array_keys($inoperable);
	}

	private static function checkCanDoOnItem(array $itemCan, $op)
	{
		if(!is_array($itemCan['ACTION']) || empty($itemCan['ACTION']))
		{
			return true; // no rights declaration, then yes
		}

		if(!isset($itemCan['ACTION'][$op]))
		{
			return false; // declaration array is present, but no such operation, then false
		}

		return !!$itemCan['ACTION'][$op];
	}

	protected static function checkCanReadTaskThrowException($userId, $taskId)
	{
		$task = static::getTask($userId, $taskId);
		if(!$task->checkCanRead(array('CHECK_BY_DATA_FETCH' => true)))
		{
			throw new \Bitrix\Tasks\AccessDeniedException();
		}
	}

	protected static function checkSetPassed(array $set, $mode)
	{
		if($mode == static::MODE_UPDATE)
		{
			return is_array($set); // only is_array(), because empty array is also a legal value
		}
		else
		{
			return is_array($set) && !empty($set); // in "add" mode set should not be empty, or else nothing to do
		}
	}

	protected static function indexItemSets(array $set)
	{
		$indexed = array();
		$i = 0;
		foreach($set as $item)
		{
			$pIndex = static::extractPrimaryIndex($item);
			if((string) $pIndex == '' || $pIndex == '0')
			{
				$pIndex = 'n'.($i++);
			}

			$indexed[$pIndex] = $item;
		}

		return $indexed;
	}

	protected static function extractPrimaryIndex(array $data)
	{
		return $data['ID'];
	}

	protected static function checkIsSubEntityKey($key)
	{
		$key = trim($key);
		if((string) $key == '')
		{
			return false;
		}

		if(strpos($key, static::SE_PREFIX) === 0)
		{
			$key = substr($key, strlen(static::SE_PREFIX), strlen($key) - strlen(static::SE_PREFIX));
			$legal = array_flip(static::getLegalSubEntities());

			return isset($legal[$key]) ? $key : false;
		}

		return false;
	}

	// default merge function
	public static function mergeData($primary = array(), $secondary = array())
	{
		return $primary; // no merge by default
	}

	// default normalize function
	public static function normalizeData($data)
	{
		if(static::getIsMultiple())
		{
			return \Bitrix\Tasks\Util\Type::normalizeArray($data);
		}

		return $data;
	}

	public static function extractPrimaryIndexes($data)
	{
		$result = array();

		if(static::getIsMultiple())
		{
			if(is_array($data))
			{
				foreach($data as $item)
				{
					$result[] = static::extractPrimaryIndex($item);
				}
			}
		}
		else
		{
			$result[] = static::extractPrimaryIndex($data);
		}

		return $result;
	}

	protected static function getLegalSubEntities()
	{
		return array();
	}

	protected static function stripSubEntityData(array $data)
	{
		foreach($data as $k => $v)
		{
			if(static::checkIsSubEntityKey($k))
			{
				unset($data[$k]);
			}
		}

		return $data;
	}
}