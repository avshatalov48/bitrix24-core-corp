<?php


namespace Bitrix\Tasks\Internals\Registry;


use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\WorkgroupTable;

class GroupRegistry
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
	 * @param int $groupId
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function get(int $groupId): ?array
	{
		if (!array_key_exists($groupId, $this->storage))
		{
			$this->load($groupId);
		}

		return $this->storage[$groupId];
	}

	/**
	 * @param array|int $groupIds
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function load($groupIds): self
	{
		if (!Loader::includeModule("socialnetwork"))
		{
			return $this;
		}

		if (!is_array($groupIds))
		{
			$groupIds = [$groupIds];
		}

		$groupIds = array_unique($groupIds);
		$groupIds = array_diff($groupIds, array_keys($this->storage));
		if (empty($groupIds))
		{
			return $this;
		}

		foreach ($groupIds as $id)
		{
			$this->storage[$id] = null;
		}

		$res = WorkgroupTable::query()
			->addSelect('ID')
			->addSelect('CLOSED')
			->addSelect('NAME')
			->whereIn('ID', $groupIds)
			->exec();

		while ($row = $res->fetch())
		{
			$this->storage[$row['ID']] = [
				'ID' => $row['ID'],
				'CLOSED' => $row['CLOSED'],
				'NAME' => $row['NAME'],
				'TASKS_ENABLED' => false
			];
		}

		$isTasksEnabled = \CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $groupIds, 'tasks');
		if (is_array($isTasksEnabled))
		{
			foreach ($isTasksEnabled as $id => $value)
			{
				if (!array_key_exists($id, $this->storage))
				{
					continue;
				}
				$this->storage[$id]['TASKS_ENABLED'] = $value;
			}
		}

		return $this;
	}
}