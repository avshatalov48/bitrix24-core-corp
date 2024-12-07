<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Counter\Processor;


use Bitrix\Main\Application;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\CounterTable;

trait CommandTrait
{

	/**
	 * @param int $userId
	 * @param array $types
	 * @param array $tasksIds
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	public static function reset(int $userId = 0, array $types = [], array $tasksIds = [], array $groupIds = []): void
	{
		$where = [];

		if ($userId)
		{
			$where[] = 'USER_ID = ' . $userId;
		}

		if (!empty($types))
		{
			$where[] = "TYPE IN ('". implode("','", $types) ."')";
		}

		if (!empty($tasksIds))
		{
			$where[] = "TASK_ID IN (". implode(",", $tasksIds) .")";
		}

		if (!empty($groupIds))
		{
			$where[] = "GROUP_ID IN (". implode(",", $groupIds) .")";
		}

		$where = (!empty($where)) ? ('WHERE ' . implode(' AND ', $where)) : '';

		$sql = "
			DELETE
			FROM ". CounterTable::getTableName(). "
			{$where}
		";

		Application::getConnection()->query($sql);
	}

	/**
	 * @param int $userId
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function saveFlag(int $userId): void
	{
		$sql = "
			INSERT INTO ". CounterTable::getTableName() ."
			(USER_ID, TASK_ID, GROUP_ID, TYPE, VALUE)
			VALUES ({$userId}, 0, 0, '". CounterDictionary::COUNTER_FLAG_COUNTED ."', 1)
		";
		Application::getConnection()->query($sql);
	}

	/**
	 * @param array $data
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private function batchInsert(array $data): void
	{
		$req = [];
		foreach ($data as $row)
		{
			$row = [
				'USER_ID'	=> $row['USER_ID'],
				'TASK_ID'	=> $row['TASK_ID'],
				'GROUP_ID'	=> $row['GROUP_ID'],
				'TYPE'		=> $row['TYPE'],
				'VALUE' 	=> $row['VALUE'],
			];
			$row['TYPE'] = "'". $row['TYPE'] ."'";
			$req[] = implode(',', $row);
		}

		if (empty($req))
		{
			return;
		}

		$sql = "
			INSERT INTO ". CounterTable::getTableName(). "
			(USER_ID, TASK_ID, GROUP_ID, TYPE, VALUE)
			VALUES
			(". implode("),(", $req) .")
		";

		Application::getConnection()->query($sql);
	}
}