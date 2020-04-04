<?php
namespace Bitrix\Timeman\Service\Schedule\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\Calendar\Calendar;
use Bitrix\Timeman\Service\BaseServiceResult;

class CalendarServiceResult extends BaseServiceResult
{
	/** @var Calendar $calendar */
	private $calendar;

	/**
	 * @return Calendar
	 */
	public function getCalendar()
	{
		return $this->calendar;
	}

	/**
	 * @param Calendar $calendar
	 * @return $this
	 */
	public function setCalendar($calendar)
	{
		$this->calendar = $calendar;
		return $this;
	}

	public function addCalendarNotFoundError()
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_CALENDAR_NOT_FOUND')));
		return $this;
	}
}