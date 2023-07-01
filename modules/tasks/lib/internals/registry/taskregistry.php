<?php


namespace Bitrix\Tasks\Internals\Registry;

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Recyclebin\Internals\Models\RecyclebinDataTable;
use Bitrix\Recyclebin\Internals\Models\RecyclebinTable;
use Bitrix\Tasks\Integration\Recyclebin\Manager;
use Bitrix\Tasks\Internals\Task\FavoriteTable;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\TaskObject;
use Bitrix\Tasks\Util\Type\DateTime;

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

		if (
			$withRelations
			&& !isset($this->storage[$taskId]['DEPARTMENTS'])
		)
		{
			$this->fillDepartments([$taskId]);
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

		$taskIds = array_diff(array_unique($taskIds), array_keys($this->storage));
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
			->addSelect('DEADLINE')
			->addSelect('TASK_CONTROL')
			->addSelect('PRIORITY')
			->addSelect('DESCRIPTION')
			->addSelect('FORUM_TOPIC_ID')
			->addSelect('RESPONSIBLE_ID')
			->addSelect('CREATED_BY')
			->addSelect('CLOSED_DATE')
			->addSelect('START_DATE_PLAN')
			->addSelect('END_DATE_PLAN')
			->whereIn('ID', $taskIds)
			->exec();

		$foundIds = [];
		while ($row = $res->fetch())
		{
			$row['ZOMBIE'] = 'N';
			$foundIds[] = $row['ID'];
			$this->storage[$row['ID']] = $row;
		}

		if (count($taskIds) > count($foundIds))
		{
			$deletedIds = array_diff($taskIds, $foundIds);
			$this->loadDeleted($deletedIds);
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
			$this->fillDepartments($foundIds);
		}

		return $this;
	}

	/**
	 * @param array $deletedIds
	 */
	private function loadDeleted(array $deletedIds): void
	{
		if (!Loader::includeModule('recyclebin'))
		{
			return;
		}

		$res = RecyclebinTable::query()
			->setSelect([
				'TASK_ID' => 'ENTITY_ID',
				'DATA' => 'RD.DATA'
			])
			->registerRuntimeField(
				'RD',
				new ReferenceField(
					'RD',
					RecyclebinDataTable::getEntity(),
					Join::on('this.ID', 'ref.RECYCLEBIN_ID')->where('ref.ACTION', 'TASK'),
					['join_type' => 'inner']
				)
			)
			->where('ENTITY_TYPE', '=', Manager::TASKS_RECYCLEBIN_ENTITY)
			->whereIn('ENTITY_ID', $deletedIds)
			->exec();

		while ($row = $res->fetch())
		{
			$taskData = $this->unserializeData($row['DATA']);
			$taskData['ZOMBIE'] = 'Y';

			$this->storage[$row['TASK_ID']] = $taskData;
		}
	}

	/**
	 * @param string $data
	 * @return array
	 */
	private function unserializeData(string $data): array
	{
		$data = unserialize($data, ['allowed_classes' => false]);

		$fields = [
			'ID',
			'TITLE',
			'GROUP_ID',
			'STATUS',
			'ALLOW_CHANGE_DEADLINE',
			'ALLOW_TIME_TRACKING',
			'DEADLINE',
		];

		foreach ($data as $field => $value)
		{
			if (!in_array($field, $fields))
			{
				unset($data[$field]);
				continue;
			}

			if ($field === 'DEADLINE')
			{
				$data[$field] = DateTime::createFromTimestampGmt($value);
			}
		}

		return $data;
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
			if (!$data)
			{
				continue;
			}

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

		$members = MemberTable::query()
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

	/**
	 * @param array $taskIds
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function fillDepartments(array $taskIds)
	{
		$userIds = [];

		foreach ($taskIds as $taskId)
		{
			$this->storage[$taskId]['DEPARTMENTS'] = [
				MemberTable::MEMBER_TYPE_RESPONSIBLE => [],
				MemberTable::MEMBER_TYPE_ORIGINATOR => [],
				MemberTable::MEMBER_TYPE_ACCOMPLICE => [],
				MemberTable::MEMBER_TYPE_AUDITOR => [],
			];

			if (!isset($this->storage[$taskId]['MEMBER_LIST']))
			{
				continue;
			}

			foreach($this->storage[$taskId]['MEMBER_LIST'] as $row)
			{
				$userIds[$row['USER_ID']] = $row['USER_ID'];
			}
		}

		if (empty($userIds))
		{
			return;
		}

		$userIds = implode(',', $userIds);
		$res = \Bitrix\Tasks\Util\User::getList(
			[
				'filter' => [
					'@ID' => new SqlExpression($userIds),
				],
				'select' => ['ID', 'UF_DEPARTMENT']
			]
		);

		$deps = [];
		foreach ($res as $row)
		{
			if (!is_array($row['UF_DEPARTMENT']) || empty($row['UF_DEPARTMENT']))
			{
				continue;
			}
			$deps[$row['ID']] = $row['UF_DEPARTMENT'];
		}

		foreach ($taskIds as $taskId)
		{
			if (!isset($this->storage[$taskId]['MEMBER_LIST']))
			{
				continue;
			}

			foreach($this->storage[$taskId]['MEMBER_LIST'] as $row)
			{
				if (!isset($deps[$row['USER_ID']]))
				{
					continue;
				}
				$this->storage[$taskId]['DEPARTMENTS'][$row['TYPE']] = array_merge($this->storage[$taskId]['DEPARTMENTS'][$row['TYPE']], $deps[$row['USER_ID']]);
			}
		}
	}

}