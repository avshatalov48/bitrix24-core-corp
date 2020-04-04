<?php
namespace Bitrix\Timeman\Service\Schedule\Result;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Service\BaseServiceResult;

class ViolationRulesServiceResult extends BaseServiceResult
{
	/** @var ViolationRules $shift */
	private $violationRules;

	/**
	 * @return ViolationRules
	 */
	public function getViolationRules()
	{
		return $this->violationRules;
	}

	/**
	 * @param ViolationRules $violationRules
	 */
	public function setViolationRules($violationRules)
	{
		$this->violationRules = $violationRules;
		return $this;
	}

	public function addViolationRulesNotFoundError()
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_VIOLATION_RULES_NOT_FOUND')));
		return $this;
	}

	public function addScheduleNotFoundError()
	{
		$this->addError(new Error(Loc::getMessage('TM_BASE_SERVICE_RESULT_ERROR_SCHEDULE_NOT_FOUND')));
		return $this;
	}
}