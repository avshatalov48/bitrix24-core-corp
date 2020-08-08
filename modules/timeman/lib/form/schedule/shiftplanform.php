<?php
namespace Bitrix\Timeman\Form\Schedule;

use Bitrix\Main\Type\Date;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter;

class ShiftPlanForm extends BaseForm
{
	public $id;
	public $shiftId;
	public $userId;
	public $dateAssignedFormatted;

	public function __construct(ShiftPlan $shiftPlan = null)
	{
		if ($shiftPlan)
		{
			$this->id = $shiftPlan->getId();
			$this->shiftId = $shiftPlan->getShiftId();
			$this->userId = $shiftPlan->getUserId();
			$this->dateAssignedFormatted = $shiftPlan->getDateAssignedUtc()->format(ShiftPlanTable::DATE_FORMAT);
		}
	}

	public function configureFilterRules()
	{
		return [
			(new Filter\Validator\LoadableValidator('dateAssignedFormatted', 'shiftId', 'userId'))
			,
			(new Filter\Validator\RegularExpressionValidator('dateAssignedFormatted'))
				->configurePattern(ShiftPlanTable::getDateRegExp())
			,
			(new Filter\Validator\NumberValidator('shiftId', 'userId', 'id'))
				->configureMin(1)
				->configureIntegerOnly(true)
			,
		];
	}

	/**
	 * @return \Bitrix\Main\Type\Date
	 */
	public function getDateAssigned()
	{
		return new Date($this->dateAssignedFormatted, ShiftPlanTable::DATE_FORMAT);
	}

	public function getDateAssignedUtc()
	{
		$date = TimeHelper::getInstance()
			->createDateTimeFromFormat(ShiftPlanTable::DATE_FORMAT, $this->dateAssignedFormatted, 0);
		$date->setTime(0, 0, 0);
		return $date;
	}
}