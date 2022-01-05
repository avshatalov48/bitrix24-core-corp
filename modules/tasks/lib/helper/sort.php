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
	 * @param int $userId
	 * @param int $groupId
	 * @return false
	 */
	public function getPosition(int $taskId, string $order, int $userId = 0, int $groupId = 0)
	{
		if ($userId)
		{
			$sql = $this->getQueryForUser($taskId, $userId);
		}
		elseif ($groupId)
		{
			$sql = $this->getQueryForGroup($taskId, $groupId);
		}
		else
		{
			return false;
		}

		$orderBy = ($order === 'asc') ? $this->getOrderAsc() : $this->getOrderDesc();

		$sql .= 'ORDER BY '.$orderBy;
		$sql .= ' LIMIT 1';

		return $this->db->Query($sql);
	}

	/**
	 * @param int $taskId
	 * @param int $userId
	 * @return string
	 */
	private function getQueryForUser(int $taskId, int $userId): string
	{
		return "
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
				SRT.USER_ID = ".$userId."
				AND
				NOT (SRT.TASK_ID = ".$taskId.")
			)
		";
	}

	/**
	 * @param int $taskId
	 * @param int $groupId
	 * @return string
	 */
	private function getQueryForGroup(int $taskId, int $groupId): string
	{
		return "
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