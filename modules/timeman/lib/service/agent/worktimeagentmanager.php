<?php
namespace Bitrix\Timeman\Service\Agent;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Timeman\Helper\TimeDictionary;
use Bitrix\Timeman\Helper\TimeHelper;
use Bitrix\Timeman\Model\Schedule\Schedule;
use Bitrix\Timeman\Model\Schedule\ScheduleTable;
use Bitrix\Timeman\Model\Schedule\Shift\Shift;
use Bitrix\Timeman\Model\Schedule\ShiftPlan\ShiftPlanTable;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRules;
use Bitrix\Timeman\Model\Schedule\Violation\ViolationRulesTable;
use Bitrix\Timeman\Service\DependencyManager;

class WorktimeAgentManager
{
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
		if ($violationRules->getPeriodTimeLackAgentId() > 0
			|| !$this->needNewPeriodTimeLackAgent($schedule, $violationRules)
		)
		{
			return new Result();
		}

		$fields = $this->preparePeriodTimeLackAgentFields($schedule, $violationRules, $fromDateTime);
		$id = $this->addAgent($fields);
		if ($id > 0)
		{
			$violationRules->setPeriodTimeLackAgentId($id);
			return DependencyManager::getInstance()
				->getViolationRulesRepository()
				->save($violationRules);
		}

		return (new Result())->addError(new Error('Failed to create Period Time Lack Checking Agent', 'createTimeLackForPeriodCheckingError'));
	}

	public function createMissedShiftChecking($shiftPlan, $shift)
	{
		$userId = (int)$shiftPlan['USER_ID'];
		if (!(
			(int)$shiftPlan['SHIFT_ID'] > 0
			&& $userId > 0
			&& $shiftPlan['DATE_ASSIGNED'] instanceof Date
			&& !empty($shift['WORK_TIME_START'])
		))
		{
			return;
		}
		/** @var Date $execServerTime */
		$execServerTime = clone $shiftPlan['DATE_ASSIGNED'];
		$shiftDuration = Shift::getShiftDuration($shift);
		$execServerTime->add(((int)$shift['WORK_TIME_START'] + $shiftDuration) . ' seconds');
		$execServerTime->add('-' . TimeHelper::getInstance()->getUserToServerOffset($userId) . ' seconds');
		if ($execServerTime === false)
		{
			return;
		}

		$params = (int)$shiftPlan['SHIFT_ID'] . ', ' . (int)$shiftPlan['USER_ID'] . ', ' . "'"
				  . $shiftPlan['DATE_ASSIGNED']->format(ShiftPlanTable::DATE_FORMAT) . "'"
				  . ', ' . (int)$shift['WORK_TIME_END'];
		$this->addAgent([
			'NAME' => 'Bitrix\\Timeman\\Service\\Agent\\ViolationNotifierAgent::notifyIfShiftMissed(' . $params . ');',
			'MODULE_ID' => 'timeman',
			'ACTIVE' => 'Y',
			'IS_PERIOD' => 'N',
			'NEXT_EXEC' => $execServerTime->format('d.m.Y H:i:s'),
			'USER_ID' => false,
		]);
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
		$res = \CAgent::add($params);
		return $res === false ? 0 : $res;
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
		$violationRulesList = DependencyManager::getInstance()
			->getViolationRulesRepository()
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

		$agentIdsChunks = array_chunk($violationRulesList->getPeriodTimeLackAgentIdList(), 50);
		foreach ($agentIdsChunks as $agentIds)
		{
			Application::getConnection()->query('DELETE FROM b_agent WHERE ID IN (' . implode(',', $agentIds) . ');');
		}

		foreach ($violationRulesList as $violationRules)
		{
			$violationRules->setPeriodTimeLackAgentId(0);
			if ($this->needNewPeriodTimeLackAgent($schedule, $violationRules))
			{
				$agentId = $this->addAgent(
					$this->preparePeriodTimeLackAgentFields($schedule, $violationRules)
				);
				$violationRules->setPeriodTimeLackAgentId($agentId);
			}
		}
		return DependencyManager::getInstance()
			->getViolationRulesRepository()
			->saveAll($violationRulesList);
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
			'NEXT_EXEC' => DateTime::createFromPhp($execTime)->toString(),
			'USER_ID' => false,
		];
	}

	/**
	 * @param Schedule $schedule
	 * @param ViolationRules $violationRules
	 * @return bool
	 */
	private function needNewPeriodTimeLackAgent($schedule, $violationRules)
	{
		return $schedule->isFixed()
			   && $violationRules->isPeriodWorkTimeLackControlEnabled()
			   && !empty($violationRules->getNotifyUsersSymbolic(ViolationRulesTable::USERS_TO_NOTIFY_FIXED_TIME_FOR_PERIOD));
	}
}