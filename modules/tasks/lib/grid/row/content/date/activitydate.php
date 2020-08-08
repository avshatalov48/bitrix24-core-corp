<?php
namespace Bitrix\Tasks\Grid\Row\Content\Date;

use Bitrix\Tasks\Grid\Row\Content\Date;
use CTasks;

/**
 * Class ActivityDate
 *
 * @package Bitrix\Tasks\Grid\Row\Content\Date
 */
class ActivityDate extends Date
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		$userId = (int)$parameters['USER_ID'];

		$isExpired = ($row['DEADLINE'] && static::isExpired(static::getDateTimestamp($row['DEADLINE'])));
		$isDeferred = ($row['REAL_STATUS'] === CTasks::STATE_DEFERRED);
		$isWaitCtrlCounts = (
			$row['REAL_STATUS'] === CTasks::STATE_SUPPOSEDLY_COMPLETED
			&& (int)$row['CREATED_BY'] === $userId
			&& (int)$row['RESPONSIBLE_ID'] !== $userId
		);
		$isCompletedCounts = (
			$row['REAL_STATUS'] === CTasks::STATE_COMPLETED
			|| ($row['REAL_STATUS'] === CTasks::STATE_SUPPOSEDLY_COMPLETED && (int)$row['CREATED_BY'] !== $userId)
		);

		$value = ((int)$row['NEW_COMMENTS_COUNT'] > 0 ? (int)$row['NEW_COMMENTS_COUNT'] : 0);
		$color = 'success';

		if ($isExpired && !$isCompletedCounts && !$isWaitCtrlCounts && !$isDeferred)
		{
			$value++;
			$color = 'danger';
		}

		if ($row['IS_MUTED'] === 'Y')
		{
			$color = 'gray';
		}

		$counter = '';
		if ($value > 0 && self::isMember($userId, $row))
		{
			$counter = "<div class='ui-counter ui-counter-{$color}'><div class='ui-counter-inner'>{$value}</div></div>";
		}

		$activityDate = static::formatDate($row['ACTIVITY_DATE']);
		$counterContainer = "<span class='task-counter-container'>{$counter}</span>";

		return $counterContainer."<span id='changedDate' style='margin-left: 3px'>{$activityDate}</span>";
	}

	/**
	 * @param int $userId
	 * @param array $row
	 * @return bool
	 */
	private static function isMember(int $userId, array $row): bool
	{
		$members = array_unique(
			array_merge(
				[$row['CREATED_BY'], $row['RESPONSIBLE_ID']],
				(is_array($row['ACCOMPLICES']) ? $row['ACCOMPLICES'] : []),
				(is_array($row['AUDITORS']) ? $row['AUDITORS'] : [])
			)
		);
		$members = array_map('intval', $members);

		return in_array($userId, $members, true);
	}
}