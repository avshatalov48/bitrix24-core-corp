<?php
namespace Bitrix\Timeman\Model\Schedule\Assignment\User;

use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Model\Schedule\Contract\ScheduleAssignable;

class ScheduleUser extends EO_ScheduleUser implements ScheduleAssignable
{
	public static function create($scheduleId, $userId, $excluded = false)
	{
		$item = new static($defaultValues = false);
		$item->setScheduleId($scheduleId);
		$item->setUserId($userId);
		if ($excluded)
		{
			$item->setIsExcluded();
		}
		else
		{
			$item->setIsIncluded();
		}
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

	public function setIsIncluded()
	{
		$this->setStatus(ScheduleUserTable::INCLUDED);
		return $this;
	}

	public function setIsExcluded()
	{
		$this->setStatus(ScheduleUserTable::EXCLUDED);
		return $this;
	}

	public function isExcluded()
	{
		return $this->getStatus() == ScheduleUserTable::EXCLUDED;
	}

	public function isIncluded()
	{
		return $this->getStatus() == ScheduleUserTable::INCLUDED;
	}

	/**
	 * @return string
	 */
	public function getEntityCode()
	{
		return EntityCodesHelper::buildUserCode($this->getUserId());
	}
}