<?php

namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter;

/**
 * Class Group
 *
 * @package Bitrix\Tasks\Internals\Counter
 */
class Group
{
	private static $instances = [];
	private $groupId;

	/**
	 * Group constructor.
	 *
	 * @param $groupId
	 */
	private function __construct($groupId)
	{
		$this->setGroupId($groupId);
	}

	/**
	 * @return mixed
	 */
	public function getGroupId()
	{
		return $this->groupId;
	}

	/**
	 * @param mixed $groupId
	 */
	public function setGroupId($groupId): void
	{
		$this->groupId = $groupId;
	}

	/**
	 * @param $groupId
	 * @return self
	 */
	public static function getInstance($groupId): self
	{
		if (!array_key_exists($groupId, self::$instances))
		{
			self::$instances[$groupId] = new self($groupId);
		}

		return self::$instances[$groupId];
	}

	/**
	 * @return string[]
	 */
	private function getMap(): array
	{
		return [
			'EXPIRED',
			'NEW_COMMENTS',
		];
	}

	/**
	 * @return array|array[]
	 * @throws Main\Db\SqlQueryException
	 */
	public function getCounters(): array
	{
		$counters = [
			'EXPIRED' => 0,
			'NEW_COMMENTS' => 0
		];

		$sql = "
			SELECT 
			       GROUP_ID,
			       TYPE,
			       SUM(VALUE) as CNT
			FROM ". CounterTable::getTableName() ." 
			WHERE
				GROUP_ID = {$this->getGroupId()}
			GROUP BY GROUP_ID, TYPE
		";

		$res = Application::getConnection()->query($sql);


		while ($row = $res->fetch())
		{
			if (in_array($row['TYPE'], CounterDictionary::MAP_EXPIRED))
			{
				$counters['EXPIRED'] += $row['CNT'];
			}

			if (in_array($row['TYPE'], CounterDictionary::MAP_COMMENTS))
			{
				$counters['NEW_COMMENTS'] += $row['CNT'];
			}
		}

		return [
			'total' => [
				'counter' => $counters['EXPIRED'] + $counters['NEW_COMMENTS'],
				'code' => '',
			],
			'expired' => [
				'counter' => $counters['EXPIRED'],
				'code' => Counter\Type::TYPE_EXPIRED,
			],
			'new_comments' => [
				'counter' => $counters['NEW_COMMENTS'],
				'code' => Counter\Type::TYPE_NEW_COMMENTS,
			],
		];
	}


}
