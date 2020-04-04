<?php
namespace Bitrix\Timeman\Model\Schedule\Assignment\User;

class ScheduleUser extends EO_ScheduleUser
{
	public static function create($scheduleId, $userId, $excluded)
	{
		$item = new static($defaultValues = false);
		$item->setScheduleId($scheduleId);
		$item->setUserId($userId);
		$item->setStatus($excluded);
		return $item;
	}

	public static function isUserIncluded($assignment)
	{
		return $assignment['STATUS'] == ScheduleUserTable::INCLUDED;
	}

	public static function isUserExcluded($assignment)
	{
		return $assignment['STATUS'] == ScheduleUserTable::EXCLUDED;
	}

	public function isExcluded()
	{
		return $this->getStatus() == ScheduleUserTable::EXCLUDED;
	}

	public function isIncluded()
	{
		return $this->getStatus() == ScheduleUserTable::INCLUDED;
	}
}