<?php
namespace Bitrix\Timeman\Model\User;

use Bitrix\Timeman\Helper\EntityCodesHelper;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Helper\UserHelper;

class User extends \Bitrix\Timeman\Model\User\EO_User
{
	private $isHead = false;
	private $utcOffset;
	private $timezoneName;

	public function obtainIsHeadOfDepartment()
	{
		return $this->isHead;
	}

	public function defineIsHeadOfDepartment($value)
	{
		$this->isHead = $value;
	}

	public function defineTimezoneName($timezoneName)
	{
		$this->timezoneName = $timezoneName;
	}

	public function obtainTimeZone()
	{
		if ($this->timezoneName === null)
		{
			return $this->getTimeZone();
		}
		return $this->timezoneName;
	}

	public function defineUtcOffset($offset)
	{
		$this->utcOffset = $offset;
	}

	public function buildFormattedName()
	{
		return UserHelper::getInstance()->getFormattedName($this);
	}

	public function obtainUtcOffset()
	{
		if ($this->utcOffset === null)
		{
			return TimeHelper::getInstance()->getUserUtcOffset($this->getId());
		}
		return $this->utcOffset;
	}

	public function obtainEntityCode()
	{
		return EntityCodesHelper::buildUserCode($this->getId());
	}
}