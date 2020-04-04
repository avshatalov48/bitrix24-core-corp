<?php
namespace Bitrix\Timeman\Form\Schedule;

use Bitrix\Timeman\Helper\DateTimeHelper;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter;

class ShiftPlanForm extends BaseForm
{
	public $shiftId;
	public $userId;
	public $dateAssignedFormatted;

	public function __construct(ShiftPlan $shiftPlan = null)
	{
		if ($shiftPlan)
		{
			$this->shiftId = $shiftPlan->getShiftId();
			$this->userId = $shiftPlan->getUserId();
			$this->dateAssignedFormatted = $shiftPlan->getDateAssigned()->format(ShiftPlanTable::DATE_FORMAT);
		}
	}

	public function configureFilterRules()
	{
		return [
			(new Filter\Validator\RequiredValidator('dateAssignedFormatted', 'shiftId', 'userId'))
			,
			(new Filter\Validator\RegularExpressionValidator('dateAssignedFormatted'))
				->configurePattern(DateTimeHelper::getDateRegExp())
			,
			(new Filter\Validator\NumberValidator('timestamp'))
				->configureIntegerOnly(true)
				->configureMin(946677600) // 2000-01-01
			,
			(new Filter\Validator\NumberValidator('shiftId', 'userId'))
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
		return \Bitrix\Main\Type\Date::createFromPhp(
			\DateTime::createFromFormat(
				ShiftPlanTable::DATE_FORMAT,
				$this->dateAssignedFormatted,
				TimeHelper::getInstance()->getUserTimezone($this->userId)
			)
		);
	}
}