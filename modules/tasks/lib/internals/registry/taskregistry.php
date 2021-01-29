<?php


namespace Bitrix\Tasks\Internals\Registry;

use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\TaskObject;

class TaskRegistry
{
	private static $instance;

	private $storage = [];

	/**
	 * @param int $userId
	 * @return static
	 */
	public static function getInstance(): self
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * TaskRegistry constructor.
	 * @param int $userId
	 */
	private function __construct()
	{

	}

	/**
	 * @param int|null $taskId
	 */
	public function drop(int $taskId = null)
	{
		if ($taskId)
		{
			unset($this->storage[$taskId]);
		}
		else
		{
			$this->storage = [];
		}
	}

	/**
	 * @param int $taskId
	 * @param bool $withRelations
	 * @return array|null
	 */
	public function get(int $taskId, bool $withRelations = false): ?array
	{
		if (!array_key_exists($taskId, $this->storage))
		{
			$this->load($taskId, $withRelations);
		}

		if (!$this->storage[$taskId])
		{
			return null;
		}

		if (
			$withRelations
			&& !isset($this->storage[$taskId]['IN_FAVORITES'])
		)
		{
			$this->fillFavorites([$taskId]);
		}

		if (
			$withRelations
			&& !isset($this->storage[$taskId]['MEMBER_LIST'])
		)
		{
			$this->fillMembers([$taskId]);
		}

		return $this->storage[$taskId];
	}

	/**
	 * @param int $taskId
	 * @param bool $withRelations
	 * @return TaskObject|null
	 */
	public function getObject(int $taskId, bool $withRelations = false): ?TaskObject
	{
		$data = $this->get($taskId, $withRelations);
		if (!$data)
		{
			return null;
		}

		return TaskObject::wakeUpObject($data);
	}

	/**
	 * @param int|array $taskIds
	 * @return $this
	 */
	public function load($taskIds, bool $withRelations = false): self
	{
		if (empty($taskIds))
		{
			return $this;
		}

		if (!is_array($taskIds))
		{
			$taskIds = [$taskIds];
		}

		$taskIds = array_diff($taskIds, array_keys($this->storage));
		if (empty($taskIds))
		{
			return $this;
		}

		foreach ($taskIds as $id)
		{
			$this->storage[$id] = null;
		}

		$res = \Bitrix\Tasks\Internals\TaskTable::query()
			->addSelect('ID')
			->addSelect('TITLE')
			->addSelect('GROUP_ID')
			->addSelect('STATUS')
			->addSelect('ALLOW_CHANGE_DEADLINE')
			->addSelect('ALLOW_TIME_TRACKING')
			->addSelect('ZOMBIE')
			->addSelect('DEADLINE')
			->whereIn('ID', $taskIds)
			->exec();

		$foundIds = [];
		while ($row = $res->fetch())
		{
			$foundIds[] = $row['ID'];
			$this->storage[$row['ID']] = $row;
		}

		if (empty($foundIds))
		{
			return $this;
		}

		$this->fillGroups($foundIds);

		if ($withRelations)
		{
			$this->fillFavorites($foundIds);
			$this->fillMembers($foundIds);
		}

		return $this;
	}

	/**
	 * @param array $taskIds
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function fillGroups(array $taskIds)
	{
		$groupIds = [];
		foreach ($taskIds as $taskId)
		{
			if (isset($this->storage[$taskId]['GROUP_ID']) && $this->storage[$taskId]['GROUP_ID'])
			{
				$groupIds[] = $this->storage[$taskId]['GROUP_ID'];
			}
		}

		GroupRegistry::getInstance()->load($groupIds);

		foreach ($this->storage as $taskId => $data)
		{
			if (!$data['GROUP_ID'])
			{
				$this->storage[$taskId]['GROUP_INFO'] = null;
			}
			else
			{
				$this->storage[$taskId]['GROUP_INFO'] = GroupRegistry::getInstance()->get((int) $data['GROUP_ID']);
			}
		}
	}

	/**
	 * @param array $taskIds
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function fillMembers(array $taskIds)
	{
		foreach ($taskIds as $taskId)
		{
			$this->storage[$taskId]['MEMBER_LIST'] = [];
		}

		$members = \Bitrix\Tasks\Internals\Task\MemberTable::query()
			->addSelect('TASK_ID')
			->addSelect('USER_ID')
			->addSelect('TYPE')
			->whereIn('TASK_ID', $taskIds)
			->exec()
			->fetchAll();

		foreach ($members as $member)
		{
			$this->storage[$member['TASK_ID']]['MEMBER_LIST'][] = [
				'TASK_ID' => (int) $member['TASK_ID'],
				'USER_ID' => (int) $member['USER_ID'],
				'TYPE' => $member['TYPE']
			];
		}
	}

	/**
	 * @param array $taskIds
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function fillFavorites(array $taskIds)
	{
		foreach ($taskIds as $id)
		{
			$this->storage[$id]['IN_FAVORITES'] = [];
		}

		$res = FavoriteTable::getList([
			'select' => ['TASK_ID', 'USER_ID'],
			'filter' => [
				'@TASK_ID' => $taskIds
			]
		]);

		while ($row = $res->fetch())
		{
			$this->storage[$row['TASK_ID']]['IN_FAVORITES'][] = (int) $row['USER_ID'];
		}
	}

}