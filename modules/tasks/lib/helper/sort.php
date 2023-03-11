<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Helper;

class Sort
{
	private $db;

	public function __construct()
	{
		global $DB;
		$this->db = $DB;
	}

	/**
	 * @param int $taskId
	 * @param string $order
	 * @param int $groupId
	 * @return int
	 */
	public function getPositionForGroup(int $taskId, string $order, int $groupId): int
	{
		$sql = "
			SELECT 
				SRT.TASK_ID 
			FROM b_tasks_sorting SRT 
			WHERE 
				SRT.GROUP_ID = ".$groupId."
			ORDER BY 
				SRT.SORT ASC, 
			    SRT.TASK_ID ASC 
			LIMIT 100
		";
		$res = $this->db->Query($sql);

		$taskIds = [];
		while ($row = $res->fetch())
		{
			$taskIds[] = (int) $row['TASK_ID'];
		}

		return $this->getSortByStatus($taskId, $taskIds, $order);
	}

	/**
	 * @param int $taskId
	 * @param string $order
	 * @param int $userId
	 * @return int
	 */
	public function getPositionForUser(int $taskId, string $order, int $userId): int
	{
		$sql = "
			SELECT 
				SRT.TASK_ID 
			FROM b_tasks_sorting SRT 
			WHERE 
				SRT.USER_ID = ".$userId."
			ORDER BY 
				SRT.SORT ASC, 
			    SRT.TASK_ID ASC 
			LIMIT 100
		";
		$res = $this->db->Query($sql);

		$taskIds = [];
		while ($row = $res->fetch())
		{
			$taskIds[] = (int) $row['TASK_ID'];
		}

		return $this->getSortByStatus($taskId, $taskIds, $order);
	}

	/**
	 * @param int $taskId
	 * @param array $taskIds
	 * @param string $order
	 * @return int
	 */
	private function getSortByStatus(int $taskId, array $taskIds, string $order): int
	{
		if (empty($taskIds))
		{
			return 0;
		}

		$orderBy = '
			STATUS_COMPLETE ASC,
			T.DEADLINE ASC
		';
		if ($order === 'asc')
		{
			$orderBy = '
				STATUS_COMPLETE DESC,
				T.DEADLINE DESC
			';
		}

		$sql = "
			SELECT
				T.ID, 
				SRT.SORT,
			    CASE WHEN T.STATUS = '5' THEN '2' ELSE '1' END AS STATUS_COMPLETE
			FROM
				b_tasks T
			LEFT JOIN b_tasks_sorting SRT 
				ON SRT.TASK_ID = T.ID
			WHERE
				T.ID IN (".implode(',', $taskIds).")
			ORDER BY ". $orderBy ."
			LIMIT 2
		";
		$res = $this->db->Query($sql);

		while ($row = $res->fetch())
		{
			if ((int)$row['ID'] !== $taskId)
			{
				return (int) $row['ID'];
			}
		}

		return 0;
	}
}