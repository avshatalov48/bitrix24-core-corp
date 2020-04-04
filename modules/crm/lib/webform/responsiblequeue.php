<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UserTable;
use Bitrix\Crm\WebForm\Internals\QueueTable;

Loc::loadMessages(__FILE__);

class ResponsibleQueue
{
	CONST OPTION_MODULE_ID = 'crm';
	CONST OPTION_LAST_NAME = 'crm_webform_resp_queue';
	public $list = array();
	public $lastId = null;
	public $entityId = null;
	protected $isWorkTimeCheckEnabled = false;
	protected $autoSetLastId = false;

	public function __construct($entityId, $autoSetLastId = true)
	{
		$this->entityId = $entityId;
		$this->autoSetLastId = $autoSetLastId;

		$this->loadList();
		$this->lastId = $this->getLastId();
	}

	public function getList()
	{
		return $this->list;
	}

	public function isWorkTimeCheckEnabled()
	{
		return $this->isWorkTimeCheckEnabled;
	}

	public function loadList()
	{
		$listDb = QueueTable::getList(array(
			'select' => array('USER_ID', 'WORK_TIME'),
			'filter' => array('=FORM_ID' => $this->entityId),
			'cache' => array('ttl' => 0)
		));
		while ($item = $listDb->fetch())
		{
			$this->list[] = $item['USER_ID'];
			$this->isWorkTimeCheckEnabled = $item['WORK_TIME'] == 'Y';
		}
	}

	public function setList(array $list, $isWorkTimeCheckEnabled = false)
	{
		// delete old data
		$deleteListDb = QueueTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=FORM_ID' => $this->entityId),
		));
		while ($deleteItem = $deleteListDb->fetch())
		{
			QueueTable::delete($deleteItem['ID']);
		}

		// add new data
		foreach ($list as $item)
		{
			$item = (int) $item;
			if (!$this->checkId($item))
			{
				continue;
			}

			QueueTable::add(array(
				'FORM_ID' => $this->entityId,
				'USER_ID' => $item,
				'WORK_TIME' => $isWorkTimeCheckEnabled ? 'Y' : 'N',
			));
		}

		$this->loadList();
	}

	public static function remove($entityId)
	{
		Option::delete(self::OPTION_MODULE_ID, array('name' => self::OPTION_LAST_NAME . $entityId));
	}

	public static function isSupportedWorkTime()
	{
		return ModuleManager::isModuleInstalled('timeman');
	}

	public function setLastId($id)
	{
		Option::set(self::OPTION_MODULE_ID, self::OPTION_LAST_NAME . $this->entityId, $id);
	}

	public function getLastId()
	{
		return Option::get(self::OPTION_MODULE_ID, self::OPTION_LAST_NAME . $this->entityId, null);
	}

	protected function getStack()
	{
		if (!$this->lastId || !in_array($this->lastId, $this->list))
		{
			return $this->list;
		}

		$lastPosition = array_search($this->lastId, $this->list);
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

	public static function checkId($id)
	{
		$item = UserTable::getRowById($id);
		return is_array($item);
	}

	protected function checkWorkTimeId($id)
	{
		if (!$this->isWorkTimeCheckEnabled || !self::isSupportedWorkTime())
		{
			return true;
		}

		if (!Loader::includeModule('timeman'))
		{
			return true;
		}

		$timeManUser = new \CTimeManUser($id);
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

		return $result;
	}

	public function getNextId()
	{
		if (count($this->list) == 0)
		{
			return null;
		}

		$nextId = null;
		$reservedId = null;
		$list = $this->getStack();
		foreach ($list as $id)
		{
			if (!$this->checkId($id))
			{
				continue;
			}

			if (!$this->checkWorkTimeId($id))
			{
				if (!$reservedId)
				{
					$reservedId = $id;
				}

				continue;
			}

			$nextId = $id;
			break;
		}

		if (!$nextId)
		{
			$nextId = $reservedId ? $reservedId : $list[0];
		}

		if ($nextId && $this->autoSetLastId)
		{
			$this->setLastId($nextId);
		}

		return $nextId;
	}
}