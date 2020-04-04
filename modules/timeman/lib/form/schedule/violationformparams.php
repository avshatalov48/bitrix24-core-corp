<?php
namespace Bitrix\Timeman\Form\Schedule;

use Bitrix\Timeman\Form\Schedule\Exception\ViolationFormParamsException;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;

/**
 * Class ViolationFormParams
 * just a helper for passing params to ViolationForm constructor
 */
class ViolationFormParams
{
	/** @var ScheduleForm */
	private $scheduleForm;
	/** @var Schedule */
	private $schedule;
	/** @return ViolationRules */
	private $violationRules;

	/**
	 * @param mixed $violationRules
	 * @return ViolationFormParams
	 */
	public function setViolationRules($violationRules)
	{
		$this->violationRules = $violationRules;
		return $this;
	}

	/** @return ScheduleForm */
	public function getScheduleForm()
	{
		return $this->scheduleForm;
	}

	/** @return ViolationRules */
	public function getViolationRules()
	{
		return $this->violationRules;
	}

	/** @return Schedule */
	public function getSchedule()
	{
		return $this->schedule;
	}

	/**
	 * @param ScheduleForm $scheduleForm
	 * @return ViolationFormParams
	 */
	public function setScheduleForm($scheduleForm)
	{
		$this->checkClass($scheduleForm, ScheduleForm::class);

		$this->scheduleForm = $scheduleForm;
		return $this;
	}

	/**
	 * @param Schedule $schedule
	 * @return ViolationFormParams
	 */
	public function setSchedule($schedule)
	{
		$this->checkClass($schedule, Schedule::class);

		$this->schedule = $schedule;
		return $this;
	}

	private function checkClass($value, $className)
	{
		if (!$value instanceof $className && !is_null($value))
		{
			throw new ViolationFormParamsException();
		}
	}

}