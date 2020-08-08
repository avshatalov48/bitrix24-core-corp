<?php
namespace Bitrix\Timeman\Service\Worktime\Violation;

use Bitrix\Main\ObjectException;
use Bitrix\Timeman\Model\Schedule\Schedule;

class WorktimeViolationManager
{
	/** @var WorktimeViolationBuilder */
	private $fixedViolationBuilder;
	/** @var WorktimeViolationBuilder */
	private $shiftedViolationBuilder;
	/** @var WorktimeViolationBuilder */
	private $flextimeViolationBuilder;

	private $violationBuilderFactory;

	public function __construct(WorktimeViolationBuilderFactory $violationBuilderFactory)
	{
		$this->violationBuilderFactory = $violationBuilderFactory;
	}

	/**
	 * @return WorktimeViolation[]
	 */
	public function buildViolations(WorktimeViolationParams $params, $types = [])
	{
		return $this->getViolationBuilder($params)->buildViolations($types);
	}

	/**
	 * @param WorktimeViolationParams $params
	 * @param \DateTime $fromDateTime
	 * @param \DateTime $toDateTime
	 * @return WorktimeViolationResult
	 * @throws ObjectException
	 */
	public function buildPeriodTimeLackViolation($params, $fromDateTime, $toDateTime)
	{
		return $this->getViolationBuilder($params)
			->buildPeriodTimeLackViolation($params, $fromDateTime, $toDateTime);
	}

	/**
	 * @param $shiftId
	 * @param $userId
	 * @param $dateFormatted
	 * @return WorktimeViolationResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function buildMissedShiftViolation(WorktimeViolationParams $params)
	{
		return $this->getViolationBuilder($params)->buildMissedShiftViolation();
	}

	/**
	 * @param WorktimeViolationParams $violationParams
	 * @return WorktimeViolationBuilder
	 * @throws ObjectException
	 */
	protected function getViolationBuilder(WorktimeViolationParams $violationParams)
	{
		if (!$violationParams->getSchedule())
		{
			throw new ObjectException(Schedule::class . ' is required to instantiate WorktimeViolationBuilder');
		}
		if (Schedule::isScheduleFixed($violationParams->getSchedule()))
		{
			if (!$this->fixedViolationBuilder)
			{
				$this->fixedViolationBuilder = $this->violationBuilderFactory->createFixedScheduleViolationBuilder($violationParams);
			}
			else
			{
				$this->fixedViolationBuilder->setWorktimeViolationParams($violationParams);
			}
			return $this->fixedViolationBuilder;
		}
		elseif (Schedule::isScheduleShifted($violationParams->getSchedule()))
		{
			if (!$this->shiftedViolationBuilder)
			{
				$this->shiftedViolationBuilder = $this->violationBuilderFactory->createShiftedScheduleViolationBuilder($violationParams);
			}
			else
			{
				$this->shiftedViolationBuilder->setWorktimeViolationParams($violationParams);
			}
			return $this->shiftedViolationBuilder;
		}
		elseif (Schedule::isScheduleFlextime($violationParams->getSchedule()))
		{
			if (!$this->flextimeViolationBuilder)
			{
				$this->flextimeViolationBuilder = $this->violationBuilderFactory->createFlextimeScheduleViolationBuilder($violationParams);
			}
			else
			{
				$this->flextimeViolationBuilder->setWorktimeViolationParams($violationParams);
			}
			return $this->flextimeViolationBuilder;
		}
	}
}