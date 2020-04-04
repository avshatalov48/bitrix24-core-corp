<?php
namespace Bitrix\Timeman\Service\Agent;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlan;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesCollection;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Model\Worktime\Record\WorktimeRecord;
use Bitrix\Timeman\Repository\Schedule\ShiftPlanRepository;
use Bitrix\Timeman\Repository\Schedule\ViolationRulesRepository;

class WorktimeAgentManager
{
	/** @var ViolationRulesRepository */
	private $violationRulesRepository;
	private $shiftPlanRepository;

	public function __construct(ViolationRulesRepository $violationRulesRepository, ShiftPlanRepository $shiftPlanRepository)
	{
		$this->violationRulesRepository = $violationRulesRepository;
		$this->shiftPlanRepository = $shiftPlanRepository;
	}

	/**
	 * @param Schedule $schedule
	 * @param null $fromDateTime
	 * @param ViolationRules $violationRules
	 * @throws \Exception
	 */
	public function createTimeLackForPeriodChecking($schedule, $fromDateTime = null, $violationRules = null)
	{
		if (!$schedule || !$violationRules)
		{
			return new Result();
		}
		if ($schedule->isReportPeriodOptionsChanged() || $schedule->isReportPeriodChanged())
		{
			$recountResult = $this->recountPeriodTimeLackAgents($schedule);
			if (!$recountResult->isSuccess())
			{
				return $recountResult;
			}
		}
		if (!$this->isPeriodTimeLackControlEnabled($schedule, $violationRules))
		{
			if ($violationRules->getPeriodTimeLackAgentId() > 0)
			{
				$this->deleteAgentById($violationRules->getPeriodTimeLackAgentId());
				$violationRules->setPeriodTimeLackAgentId(0);
				$this->violationRulesRepository->save($violationRules);
			}
			return new Result();
		}
		if ($violationRules->getPeriodTimeLackAgentId() > 0)
		{
			return new Result();
		}

		$fields = $this->preparePeriodTimeLackAgentFields($schedule, $violationRules, $fromDateTime);
		$id = $this->addAgent($fields);
		if ($id > 0)
		{
			$violationRules->setPeriodTimeLackAgentId($id);
			return $this->violationRulesRepository->save($violationRules);
		}

		return (new Result())->addError(new Error('Failed to create Period Time Lack Checking Agent', 'createTimeLackForPeriodCheckingError'));
	}

	/** Creates agent that will be executed at the end of shift (by user time)
	 * if schedule controls missed shifts - then agent sends notification on missed shift
	 * otherwise agent just deletes itself
	 * @param ShiftPlan $shiftPlan
	 * @param Shift $shift
	 * @param Schedule $schedule
	 */
	public function createMissedShiftChecking($shiftPlan, $shift)
	{
		if ($shift->buildUtcEndByShiftplan($shiftPlan)->getTimestamp() < TimeHelper::getInstance()->getUtcNowTimestamp())
		{
			return;
		}
		$agentId = $this->addAgent(
			$this->prepareMissedShiftAgentFields($shiftPlan, $shift)
		);
		if ($agentId > 0)
		{
			$shiftPlan->setMissedShiftAgentId($agentId);
			$this->shiftPlanRepository->save($shiftPlan);
		}
	}

	/**
	 * @param ShiftPlan $shiftPlan
	 * @param Shift $shift
	 * @return array
	 * @throws \Exception
	 */
	private function prepareMissedShiftAgentFields($shiftPlan, $shift)
	{
		return [
			'PARAMS' => [
				'shiftPlanId' => $shiftPlan->getId(),
			],
			'NAME' => 'Bitrix\\Timeman\\Service\\Agent\\ViolationNotifierAgent::notifyIfShiftMissed',
			'MODULE_ID' => 'timeman',
			'ACTIVE' => 'Y',
			'IS_PERIOD' => 'N',
			'NEXT_EXEC' => $shift->buildUtcEndByShiftplan($shiftPlan),
			'USER_ID' => false,
		];
	}

	protected function addAgent($params)
	{
		if (empty($params))
		{
			return 0;
		}
		if (isset($params['PARAMS']))
		{
			$params['NAME'] .= '(\'' . implode("','", $params['PARAMS']) . '\');';
			unset($params['PARAMS']);
		}
		if (isset($params['NEXT_EXEC']) && $params['NEXT_EXEC'] instanceof \DateTime)
		{
			$params['NEXT_EXEC'] = \Bitrix\Main\Type\DateTime::createFromPhp($params['NEXT_EXEC'])->toString();
		}
		$resId = \CAgent::add($params);
		return $resId === false ? 0 : $resId;
	}

	/**
	 * @param Schedule $schedule
	 * @param \DateTime $fromDateTime
	 * @return array
	 * @throws \Exception
	 */
	private function buildPeriodDates($schedule, $fromDateTime)
	{
		$toDateTime = null;
		$today = \DateTime::createFromFormat('Y-m-d',
			TimeHelper::getInstance()->getCurrentServerDateFormatted()
		);
		if ($fromDateTime)
		{
			$fromDateTime->add(new \DateInterval('P1D'));
		}
		switch ($schedule->getReportPeriod())
		{
			case ScheduleTable::REPORT_PERIOD_MONTH:
				if ($fromDateTime === null)
				{
					$fromDateTime = clone $today;
					$fromDateTime->modify('first day of next month');
				}
				$fromDateTime->setTime(0, 0, 0);

				$toDateTime = clone $fromDateTime;
				$toDateTime->modify('last day of');
				$toDateTime->setTime(23, 59, 59);
				break;

			case ScheduleTable::REPORT_PERIOD_QUARTER:
				$ranges = [
					1 => ['01.01', '31.03'],
					2 => ['01.04', '30.06'],
					3 => ['01.07', '30.09'],
					4 => ['01.10', '31.12'],
				];
				if ($fromDateTime === null)
				{
					$currentQuarter = intval(((int)$today->format('n') + 2) / 3);

					if ($currentQuarter === 4)
					{
						$fromDateTime = \DateTime::createFromFormat('d.m.Y H:i:s', $ranges[1][0] . '.' . $today->format('Y') . ' 00:00:00');
						$fromDateTime->add(new \DateInterval('P1Y'));
						$toDateTime = \DateTime::createFromFormat('d.m.Y H:i:s', $ranges[1][1] . '.' . $today->format('Y') . ' 23:59:59');
						$toDateTime->add(new \DateInterval('P1Y'));
					}
					else
					{
						$fromDateTime = \DateTime::createFromFormat('d.m.Y H:i:s', $ranges[$currentQuarter + 1][0] . '.' . $today->format('Y') . ' 00:00:00');
						$toDateTime = \DateTime::createFromFormat('d.m.Y H:i:s', $ranges[$currentQuarter + 1][1] . '.' . $today->format('Y') . ' 23:59:59');
					}
				}
				else
				{
					$fromDateTime->setTime(0, 0, 0);
					$currentQuarter = intval(((int)$fromDateTime->format('n') + 2) / 3);
					$toDateTime = \DateTime::createFromFormat('d.m.Y H:i:s', $ranges[$currentQuarter][1] . '.' . $fromDateTime->format('Y') . ' 23:59:59');
				}

				break;
			case ScheduleTable::REPORT_PERIOD_WEEK:
			case ScheduleTable::REPORT_PERIOD_TWO_WEEKS:
				if ($fromDateTime !== null)
				{
					$fromDateTime->setTime(0, 0, 0);
				}
				else
				{
					$fromDateTime = clone $today;
					$startDay = (int)$schedule->getReportPeriodOptions()[ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY];
					switch ($startDay)
					{
						case ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_MONDAY:
							$fromDateTime->modify('next monday');
							break;
						case ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_TUESDAY:
							$fromDateTime->modify('next tuesday');
							break;
						case ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_WEDNESDAY:
							$fromDateTime->modify('next wednesday');
							break;
						case ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_THURSDAY:
							$fromDateTime->modify('next thursday');
							break;
						case ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_FRIDAY:
							$fromDateTime->modify('next friday');
							break;
						case ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_SUNDAY:
							$fromDateTime->modify('next sunday');
							break;
						case ScheduleTable::REPORT_PERIOD_OPTIONS_START_WEEK_DAY_SATURDAY:
							$fromDateTime->modify('next saturday');
							break;
					}
				}
				$fromDateTime->setTime(0, 0, 0);

				$toDateTime = clone $fromDateTime;
				$toDateTime->setTime(23, 59, 59);
				$toDateTime->add(new \DateInterval(
					$schedule->getReportPeriod() === ScheduleTable::REPORT_PERIOD_WEEK ? 'P6D' : 'P13D'
				));
				break;
			default:
				break;
		}


		return [$fromDateTime, $toDateTime];
	}

	/**
	 * @param Schedule $schedule
	 * @param ViolationRules $violationRules
	 */
	private function recountPeriodTimeLackAgents($schedule)
	{
		$violationRulesList = $this->violationRulesRepository
			->findAllByScheduleId(
				$schedule->getId(),
				[
					'ID',
					'ENTITY_CODE',
					'PERIOD_TIME_LACK_AGENT_ID',
					'MAX_WORK_TIME_LACK_FOR_PERIOD',
					'USERS_TO_NOTIFY',
				],
				Query::filter()
					->where('PERIOD_TIME_LACK_AGENT_ID', '>', 0)
			);
		if ($violationRulesList->count() == 0)
		{
			return new Result();
		};

		$this->deleteAgentsByIds($violationRulesList->getPeriodTimeLackAgentIdList());
		$listToUpdate = new ViolationRulesCollection();
		foreach ($violationRulesList as $violationRules)
		{
			$this->deleteAgentById($violationRules->getPeriodTimeLackAgentId());
			$violationRules->setPeriodTimeLackAgentId(0);
			if ($this->isPeriodTimeLackControlEnabled($schedule, $violationRules))
			{
				$agentId = $this->addAgent(
					$this->preparePeriodTimeLackAgentFields($schedule, $violationRules)
				);
				$violationRules->setPeriodTimeLackAgentId($agentId);
				$this->violationRulesRepository->save($violationRules);
			}
			if ($violationRules->getPeriodTimeLackAgentId() === 0)
			{
				$listToUpdate->add($violationRules);
			}
		}
		return $this->violationRulesRepository->saveAll($listToUpdate, ['PERIOD_TIME_LACK_AGENT_ID' => 0]);
	}

	/**
	 * @param Schedule $schedule
	 * @param ViolationRules $violationRules
	 * @param $fromDateTime
	 * @return array
	 * @throws \Exception
	 */
	private function preparePeriodTimeLackAgentFields($schedule, $violationRules, $fromDateTime = null)
	{
		list($fromDateTime, $toDateTime) = $this->buildPeriodDates($schedule, $fromDateTime);
		if (!(
			isset($toDateTime) && isset($fromDateTime)
			&& $toDateTime instanceof \DateTime && $fromDateTime instanceof \DateTime
		))
		{
			return [];
		}
		$execTime = clone $toDateTime;
		$execTime->add(new \DateInterval('P1D'));
		return [
			'PARAMS' => [
				'scheduleId' => $schedule->getId(),
				'from' => $fromDateTime->format(TimeDictionary::DATE_TIME_FORMAT),
				'to' => $toDateTime->format(TimeDictionary::DATE_TIME_FORMAT),
				'entityCode' => $violationRules->getEntityCode(),
			],
			'NAME' => 'Bitrix\\Timeman\\Service\\Agent\\ViolationNotifierAgent::notifyIfPeriodTimeLack',
			'MODULE_ID' => 'timeman',
			'ACTIVE' => 'Y',
			'IS_PERIOD' => 'N',
			'NEXT_EXEC' => $execTime,
			'USER_ID' => false,
		];
	}

	/**
	 * @param Schedule $schedule
	 * @param ViolationRules $violationRules
	 * @return bool
	 */
	private function isPeriodTimeLackControlEnabled($schedule, $violationRules)
	{
		return $schedule->isFixed()
			   && $violationRules->isPeriodWorkTimeLackControlEnabled()
			   && !empty($violationRules->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_TIME_FOR_PERIOD));
	}

	public function deleteAgentById($agentId)
	{
		$dataClass = $this->getAgentDataClass();
		$agent = $dataClass::query()
			->addSelect('ID')
			->where('MODULE_ID', 'timeman')
			->where('ID', $agentId)
			->exec()
			->fetch();
		if ($agent)
		{
			return \CAgent::delete($agentId);
		}
		return true;
	}

	private function getAgentDataClass()
	{
		static $dataClass = null;
		if ($dataClass === null)
		{
			$entity = \Bitrix\Main\ORM\Entity::compileEntity(
				'TimemanCompiledAgentTable',
				[
					(new Fields\IntegerField('ID'))
						->configurePrimary(true)
						->configureAutocomplete(true)
					,
					(new Fields\StringField('MODULE_ID'))
					,
					(new Fields\StringField('NAME'))
					,
				],
				['table_name' => 'b_agent']
			);
			$dataClass = $entity->getDataClass();
		}
		return $dataClass;
	}

	public function deleteAgentsByIds($ids)
	{
		if (empty($ids))
		{
			return;
		}
		$ids = array_map('intval', $ids);
		$agentIdsChunks = array_chunk($ids, 50);
		foreach ($agentIdsChunks as $agentIds)
		{
			Application::getConnection()->query('DELETE FROM b_agent WHERE ID IN (' . implode(',', $agentIds) . ');');
		}
	}

	/**
	 * @param WorktimeRecord $record
	 * @param Schedule|null $schedule
	 * @param Shift|null $shift
	 */
	public function createAutoClosingAgent($record, $schedule, $shift)
	{
		if (!$schedule || !$schedule->isAutoClosing()
			|| $record->getRecordedStopTimestamp() > 0
			|| $record->getAutoClosingAgentId() > 0)
		{
			return;
		}
		$recordStopUtcTimestamp = $record->buildStopTimestampForAutoClose($schedule, $shift);
		if ($recordStopUtcTimestamp === null)
		{
			return;
		}

		$agentId = $this->addAgent([
			'PARAMS' => [
				'recordId' => $record->getId(),
			],
			'NAME' => 'Bitrix\\Timeman\\Service\\Agent\\AutoCloseWorktimeAgent::runCloseRecord',
			'MODULE_ID' => 'timeman',
			'ACTIVE' => 'Y',
			'IS_PERIOD' => 'N',
			'NEXT_EXEC' => TimeHelper::getInstance()->createUserDateTimeFromFormat('U', $recordStopUtcTimestamp, $record->getUserId()),
			'USER_ID' => false,
		]);
		if ($agentId > 0)
		{
			$record->setAutoClosingAgentId($agentId);
		}
	}

	public function createAutoClosingAgentForRecords(\Bitrix\Timeman\Model\Worktime\Record\WorktimeRecordCollection $records)
	{
		foreach ($records as $record)
		{
			$this->createAutoClosingAgent($record, $record->obtainSchedule(), $record->obtainShift());
		}
	}
}