<?php

namespace Bitrix\Tasks\Helper;

abstract class Common
{
	protected static $instance = null;
	private $id;
	private $userId;
	private $groupId;

	private function __construct($id, $userId, $groupId)
	{
		$this->setId($id);
		$this->setGroupId($groupId);
		$this->setUserId($userId);
	}

	/**
	 * @param $userId
	 * @param int $groupId
	 * @param null $gridId
	 *
	 * @return self
	 */
	public static function getInstance($userId, $groupId = 0, $id = null)
	{
		if (!$id)
		{
			$id = self::getDefaultId($groupId > 0);
		}

		if (is_null(static::$instance) || !array_key_exists($id, static::$instance))
		{
			static::$instance[$id] = new static($id, $userId, $groupId);
		}

		return static::$instance[$id];
	}

	private static function getDefaultId($isGroup = false)
	{ // TODO
		$roleId = 4096;
		$typeFilter = 'ADVANCED';
		$presetSelected = 'N';

		return 'TASKS_GRID_ROLE_ID_'.$roleId.'_'.(int)$isGroup.'_'.$typeFilter.'_'.$presetSelected;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param mixed $filterId
	 */
	protected function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * @return \CTaskListState|null
	 */
	public function getListStateInstance()
	{
		static $instance = null;

		if (!$instance)
		{
			$instance = \CTaskListState::getInstance($this->getUserId());
		}

		return $instance;
	}

	/**
	 * @return mixed
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * @param mixed $userId
	 */
	protected function setUserId($userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @return mixed
	 */
	protected function getGroupId()
	{
		return $this->groupId;
	}

	/**
	 * @param mixed $groupId
	 */
	protected function setGroupId($groupId)
	{
		$this->groupId = $groupId;
	}

}