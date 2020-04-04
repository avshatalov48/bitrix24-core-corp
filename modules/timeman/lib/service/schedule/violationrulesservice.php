<?php
namespace Bitrix\Timeman\Service\Schedule;

use Bitrix\Timeman\Form\Schedule\ViolationForm;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Repository\Schedule\ViolationRulesRepository;
use Bitrix\Timeman\Service\Agent\WorktimeAgentManager;
use Bitrix\Timeman\Service\BaseService;
use Bitrix\Timeman\Service\Schedule\Result\ViolationRulesServiceResult;

class ViolationRulesService extends BaseService
{
	/** @var ViolationRulesRepository */
	private $violationRulesRepository;
	/** @var WorktimeAgentManager */
	private $worktimeAgentManager;

	public function __construct(ViolationRulesRepository $violationRulesRepository, WorktimeAgentManager $worktimeAgentManager)
	{
		$this->violationRulesRepository = $violationRulesRepository;
		$this->worktimeAgentManager = $worktimeAgentManager;
	}

	/**
	 * @param ViolationForm $violationForm
	 * @param null $schedule
	 * @return ViolationRulesServiceResult
	 */
	public function add(ViolationForm $violationForm, $schedule = null)
	{
		if ($schedule === null && !($schedule = $this->violationRulesRepository->findScheduleById($violationForm->scheduleId)))
		{
			return (new ViolationRulesServiceResult())->addScheduleNotFoundError();
		}
		$violationForm = clone $violationForm;
		$this->adjustViolationFormFields($violationForm);
		$violationRules = ViolationRules::create($schedule->getId(), $violationForm, $violationForm->entityCode);
		$res = $this->violationRulesRepository->save($violationRules);
		if (!$res->isSuccess())
		{
			return ViolationRulesServiceResult::createByResult($res);
		}

		$this->worktimeAgentManager->createTimeLackForPeriodChecking($schedule, null, $violationRules);

		return (new ViolationRulesServiceResult())
			->setViolationRules($violationRules);
	}

	/**
	 * @param ViolationForm $violationForm
	 * @param Schedule $schedule
	 * @return ViolationRulesServiceResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function update(ViolationForm $violationForm, $schedule = null)
	{
		$violationRules = null;
		if ($schedule && $schedule->obtainScheduleViolationRules())
		{
			$violationRules = $schedule->obtainScheduleViolationRules();
		}
		if (!$violationRules)
		{
			$violationRules = $this->violationRulesRepository->findByScheduleIdEntityCode($violationForm->scheduleId, $violationForm->entityCode);
			if (!$violationRules)
			{
				return (new ViolationRulesServiceResult())->addViolationRulesNotFoundError();
			}
		}
		if (!$schedule && !($schedule = $this->violationRulesRepository->findScheduleById($violationForm->scheduleId)))
		{
			return (new ViolationRulesServiceResult())->addScheduleNotFoundError();
		}
		$violationForm = clone $violationForm;
		$this->adjustViolationFormFields($violationForm);
		$violationRules->edit($violationForm);
		$res = $this->violationRulesRepository->save($violationRules);
		if (!$res->isSuccess())
		{
			return ViolationRulesServiceResult::createByResult($res);
		}

		$this->worktimeAgentManager->createTimeLackForPeriodChecking($schedule, null, $violationRules);

		return (new ViolationRulesServiceResult())
			->setViolationRules($violationRules);
	}

	/**
	 * @param ViolationForm $violationForm
	 */
	private function adjustViolationFormFields($violationForm)
	{
		if (!$violationForm->saveAllViolationFormFields)
		{
			$violationForm->resetExtraFields();
		}
		$violationForm->adjustViolationSeconds();
	}
}