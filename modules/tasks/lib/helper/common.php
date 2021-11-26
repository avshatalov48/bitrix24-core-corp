<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Item\Workgroup;

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
		$groupId = (int)$groupId;

		if (!$id)
		{
			$id = self::getDefaultId($groupId);
		}

		if (is_null(static::$instance) || !array_key_exists($id, static::$instance))
		{
			static::$instance[$id] = new static($id, $userId, $groupId);
		}

		return static::$instance[$id];
	}

	/**
	 * @param int $groupId
	 * @return string
	 */
	private static function getDefaultId(int $groupId): string
	{
		return \Bitrix\Tasks\Helper\FilterRegistry::getId(\Bitrix\Tasks\Helper\FilterRegistry::FILTER_GRID, $groupId);
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

	public function isScrumProject(): bool
	{
		if (Loader::includeModule('socialnetwork'))
		{
			$group = Workgroup::getById($this->getGroupId());

			return ($group && $group->isScrumProject());
		}

		return false;
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