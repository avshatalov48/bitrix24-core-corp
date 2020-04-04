<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Intranet;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\UserTable;

use Bitrix\Intranet\Internals;

Loc::loadMessages(__FILE__);

/**
 * Class Queue
 *
 * @package Bitrix\Intranet\Queue
 */
class UserQueue
{
	protected $list = [];
	protected $lastItem = null;
	protected $previousStack = [];
	protected $isLastItemRestored = false;
	protected $type = null;
	protected $id = null;

	protected $isWorkTimeCheckEnabled = false;
	protected $isUserCheckEnabled = true;
	protected $isAutoSaveEnabled = true;

	/** @var callable[] $filters  */
	protected $filters = [];

	/**
	 * Queue constructor.
	 *
	 * @param string $type Type.
	 * @param string $id ID.
	 * @param array $list List.
	 */
	public function __construct($type, $id, array $list = [])
	{
		$this->type = $type;
		$this->setId($id);
		$this->setValues($list);
	}

	/**
	 * Save last item automatically.
	 *
	 * @return $this
	 */
	public function disableAutoSave()
	{
		$this->isAutoSaveEnabled = false;
		return $this;
	}

	/**
	 * Enable work time checking.
	 *
	 * @return $this
	 */
	public function enableWorkTimeCheck()
	{
		$this->isWorkTimeCheckEnabled = true;
		return $this;
	}

	/**
	 * Return true if work time checking enabled.
	 *
	 * @return bool
	 */
	public function isWorkTimeCheckEnabled()
	{
		return $this->isWorkTimeCheckEnabled;
	}

	/**
	 * Get ID.
	 *
	 * @return null|string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set ID.
	 *
	 * @param null|string $id
	 * @return $this
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * Set list.
	 *
	 * @param array $list List.
	 * @return $this
	 */
	public function setValues(array $list)
	{
		$this->list = array_values($list);
		$this->previousStack = [];
		return $this;
	}

	/**
	 * Get list.
	 *
	 * @return array
	 */
	public function getValues()
	{
		return $this->list;
	}

	/**
	 * Remove data from DB by type and ID.
	 *
	 * @return bool
	 */
	public function delete()
	{
		$class = static::getDataManagerClass();
		return $class::delete(['ENTITY_TYPE' => $this->type, 'ENTITY_ID' => $this->id])->isSuccess();
	}

	/**
	 * Return true if wirk time is supported.
	 *
	 * @return bool
	 */
	public static function isSupportedWorkTime()
	{
		return ModuleManager::isModuleInstalled('timeman');
	}

	/**
	 * Get last used item from list.
	 *
	 * @return null|string
	 */
	public function current()
	{
		if (!$this->isLastItemRestored)
		{
			$this->restore();
			$this->isLastItemRestored = true;
		}

		return $this->lastItem;
	}

	/**
	 * Save last item to DB.
	 *
	 * @return $this
	 */
	public function save()
	{
		$sqlHelper = Application::getConnection()->getSqlHelper();
		$type = $sqlHelper->forSql($this->type);
		$id = $sqlHelper->forSql($this->id);
		$item = $sqlHelper->forSql($this->current());
		if ($item)
		{
			$class = static::getDataManagerClass();
			$tableName = $class::getTableName();
			$sql = "INSERT IGNORE $tableName(ENTITY_TYPE, ENTITY_ID, LAST_ITEM) "
				. "VALUES('$type', '$id', '$item') "
				. "ON DUPLICATE KEY UPDATE LAST_ITEM = '$item' ";
			Application::getConnection()->query($sql);
		}
		else
		{
			$this->delete();
		}

		return $this;
	}

	/**
	 * Restore last item from DB.
	 *
	 * @return $this
	 */
	public function restore()
	{
		$class = static::getDataManagerClass();
		$row = $class::getRow([
			'select' => ['LAST_ITEM'],
			'filter' => ['=ENTITY_TYPE' => $this->type, '=ENTITY_ID' => $this->id]
		]);
		$this->setLastItem($row ? $row['LAST_ITEM'] : null);

		return $this;
	}

	/**
	 * Return next item from list.
	 * Save item to DB if $isAutoSaveEnabled is true.
	 * Check item as User if $isUserCheckEnabled is true.
	 * Check item for work time if $isWorkTimeCheckEnabled is true.
	 *
	 * @return string|null
	 */
	public function next()
	{
		if (count($this->list) == 0)
		{
			return null;
		}

		$nextItem = null;
		$reservedItem = null;
		$list = $this->getStack();
		foreach ($list as $item)
		{
			if (!$this->filterItem($item, $reservedItem))
			{
				continue;
			}

			$nextItem = $item;
			break;
		}

		if (!$nextItem)
		{
			$nextItem = $reservedItem ? $reservedItem : $list[0];
		}

		$this->setLastItem($nextItem);

		if ($this->isAutoSaveEnabled)
		{
			$this->save();
		}

		return $nextItem;
	}

	/**
	 * Return previous used item.
	 * Stack of previous items is limited by 3 values.
	 *
	 * @return string|null
	 */
	public function previous()
	{
		if (count($this->previousStack) === 0)
		{
			$this->isLastItemRestored = false;
			$this->lastItem = null;
		}
		else
		{
			$this->lastItem = array_pop($this->previousStack);
		}

		return $this->lastItem;
	}

	/**
	 * Return random item.
	 * Stack of previous items is limited by 3 values.
	 *
	 * @return mixed|null
	 */
	public function random()
	{
		if (count($this->list) == 0)
		{
			return null;
		}

		$item = null;
		$length = count($this->list);
		for ($i = 0; $i < 3; $i++)
		{
			$index = mt_rand(0, $length - 1);
			if (!isset($this->list[$index]))
			{
				return null;
			}

			$item = $this->list[$index];
			if ($item === $this->current())
			{
				$item = null;
				break;
			}

			if (!$this->filterItem($item))
			{
				$item = null;
				continue;
			}

			break;
		}

		if (!$item)
		{
			return $this->next();
		}

		$this->setLastItem($item);

		if ($this->isAutoSaveEnabled)
		{
			$this->save();
		}

		return $item;
	}

	protected function setLastItem($item)
	{
		if ($this->lastItem)
		{
			if (count($this->previousStack) >= 3)
			{
				array_shift($this->previousStack);
			}
			array_push($this->previousStack, $this->lastItem);
		}
		$this->lastItem = $item;

		return $this;
	}

	protected function getStack()
	{
		if (!$this->current() || !in_array($this->current(), $this->list))
		{
			return $this->list;
		}

		$lastPosition = array_search($this->current(), $this->list);
		$lastPosition++;
		if ($lastPosition >= count($this->list))
		{
			$lastPosition = 0;
		}
		$list = array_slice($this->list, $lastPosition);
		if ($lastPosition > 0)
		{
			$list = array_merge(
				$list,
				array_slice($this->list, 0, $lastPosition)
			);
		}

		return $list;
	}

	protected function checkUser($userId)
	{
		if (!$this->isUserCheckEnabled)
		{
			return true;
		}

		if (!is_numeric($userId))
		{
			return false;
		}

		$row = UserTable::getRowById($userId);
		return is_array($row);
	}

	protected function checkUserWorkTime($userId, &$reservedUserId = null)
	{
		if (!$this->isWorkTimeCheckEnabled())
		{
			return true;
		}

		if (!self::isSupportedWorkTime())
		{
			return true;
		}

		if (!Loader::includeModule('timeman'))
		{
			return true;
		}

		$timeManUser = new \CTimeManUser($userId);
		$timeManSettings = $timeManUser->GetSettings(Array('UF_TIMEMAN'));
		if (!$timeManSettings['UF_TIMEMAN'])
		{
			$result = true;
		}
		else
		{
			$timeManUser->GetCurrentInfo(true); // need for reload cache

			if ($timeManUser->State() == 'OPENED')
			{
				$result = true;
			}
			else
			{
				$result = false;
			}
		}

		if (!$result && !$reservedUserId)
		{
			$reservedUserId = $userId;
		}

		return $result;
	}

	/**
	 * @return Internals\QueueTable|string
	 */
	protected static function getDataManagerClass()
	{
		return Internals\QueueTable::class;
	}

	protected function filterItem($item, &$reservedItem = null)
	{
		if (!$this->checkUser($item))
		{
			return false;
		}

		if (!$this->checkUserWorkTime($item, $reservedItem))
		{
			return false;
		}

		foreach ($this->filters as $filter)
		{
			$result = call_user_func_array($filter, [$item, $reservedItem]);
			if (!$result)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Add filter.
	 *
	 * @param callable $callable Callable filter.
	 * @return $this
	 * @throws ArgumentException
	 */
	public function addFilter($callable)
	{
		if (!is_callable($callable))
		{
			throw new ArgumentException('Filter must be callable.');
		}

		$this->filters[] = $callable;
		return $this;
	}

	/**
	 * Remove queue by type and ID.
	 *
	 * @param string $type Type.
	 * @param string $id ID.
	 * @return bool
	 */
	public static function deleteById($type, $id)
	{
		return (new static($type, $id))->delete();
	}

	/**
	 * Remove queue by type.
	 *
	 * @param string $type Type.
	 * @return bool
	 */
	public static function deleteByType($type)
	{
		$class = static::getDataManagerClass();
		return $class::delete(['ENTITY_TYPE' => $type])->isSuccess();
	}
}