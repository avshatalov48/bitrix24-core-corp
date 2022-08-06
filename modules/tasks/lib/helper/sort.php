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
	private $bd;

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
				SRT.TASK_ID AS ID,
				SRT.SORT AS SORTING,
				CASE WHEN T.STATUS = '5' THEN '2' ELSE '1' END AS STATUS_COMPLETE,
				T.DEADLINE AS DEADLINE_ORIG
			FROM
				b_tasks_sorting SRT
				LEFT JOIN b_tasks T ON SRT.TASK_ID = T.ID
			WHERE
			(
				SRT.GROUP_ID = ".$groupId."
				AND
				NOT (SRT.TASK_ID = ".$taskId.")
			)
		";

		$orderBy = ($order === 'asc') ? $this->getOrderAsc() : $this->getOrderDesc();

		$sql .= 'ORDER BY '.$orderBy;
		$sql .= ' LIMIT 1';

		$res = $this->db->Query($sql);
		if ($row = $res->fetch())
		{
			return (int) $row['ID'];
		}

		return 0;
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

	/**
	 * @return string
	 */
	private function getOrderAsc(): string
	{
		return '
			ISNULL(SORTING) DESC,
			SORTING DESC,
			STATUS_COMPLETE DESC,
			T.DEADLINE DESC,
			ID DESC
		';
	}

	/**
	 * @return string
	 */
	private function getOrderDesc(): string
	{
		return '
			ISNULL(SORTING) ASC,
			SORTING ASC,
			STATUS_COMPLETE ASC,
			length(T.DEADLINE)>0 DESC,
			T.DEADLINE ASC,
			ID ASC
		';
	}
}