<?php

namespace Bitrix\ImOpenLines\Integrations\Report;

use Bitrix\Im\Model\MessageTable;
use Bitrix\ImOpenLines\Integrations\Report\Statistics\Dialog;
use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\DialogStatTable;
use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\StatisticQueueTable;
use Bitrix\ImOpenLines\Integrations\Report\Statistics\Entity\TreatmentByHourStatTable;
use Bitrix\ImOpenLines\Integrations\Report\Statistics\Manager;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Session;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Update\Stepper;

/**
 * Class Statistic
 * @package Bitrix\ImOpenLines\Integrations\Report
 */
class Statistic extends Stepper
{
	const DAY_COUNT_FOR_PAST_STATISTIC = 60;
	const SESSION_TO_STATISTIC_PER_ITERATION = 100;
	protected static $moduleId = "imopenlines";


	/**
	 * Executes some action, and if return value is false, agent will be deleted.
	 * If return true then continue execution, else stop execution and remove agent.
	 *
	 * @param array $option Array with main data to show if it is necessary like {steps : 35, count : 7}, where steps is an amount of iterations, count - current position.
	 * @return boolean
	 */
	public function execute(array &$option)
	{
		$lastId = Option::get("imopenlines", 'lastId', 0);
		$executedStepCount = Option::get("imopenlines", 'executedStepCount', 0);

		if ($executedStepCount === 0)
		{
			Option::set("imopenlines", 'statisticPreCalculateFinish', false);
			$this->cleanStatisticTables();
		}

		$sessions = $this->getSessionsFrom($lastId);

		if (!$sessions)
		{
			Option::delete("imopenlines", array('name' => 'lastId'));
			Option::delete("imopenlines", array('name' => 'executedStepCount'));
			Option::set("imopenlines", 'statisticPreCalculateFinish', true);
			return false;
		}

		foreach ($sessions as $session)
		{
			$this->repairIsChatCreatedNew($session);
			$this->writeToStatistics($session);
		}

		$lastExecutedSession = end($sessions);
		$executedStepCount++;
		Option::set("imopenlines", 'lastId', $lastExecutedSession['ID']);
		Option::set("imopenlines", 'executedStepCount', $executedStepCount);
		$option["steps"] = $executedStepCount;
		$option["count"] = (int)(($this->getSessionsCount() / self::SESSION_TO_STATISTIC_PER_ITERATION) + 1);

		return true;

	}

	/**
	 * @param $session
	 */
	private function repairIsChatCreatedNew(&$session)
	{
		Loader::includeModule('im');

		$cnt = MessageTable::getCount([
			'<ID' => $session['START_ID'],
			'=CHAT_ID' => $session['CHAT_ID']
		]);

		$session['IS_CHAT_CREATED_NEW'] = ($cnt == 0);
	}

	/**
	 * @param $lastId
	 * @return array
	 */
	private function getSessionsFrom($lastId)
	{
		$query = new Query(SessionTable::getEntity());
		$query->addSelect('ID');
		$query->addSelect('DATE_CREATE', 'DATE');
		$query->addSelect('OPERATOR_ID');
		$query->addSelect('CONFIG_ID', 'OPEN_LINE_ID');
		$query->addSelect('SOURCE', 'SOURCE_ID');
		$query->addSelect('VOTE', 'MARK');
		$query->addSelect('STATUS');
		$query->addSelect('CHAT_ID');
		$query->addSelect('START_ID');
		$query->addSelect('QUEUE_HISTORY');
		$query->addSelect('TIME_FIRST_ANSWER');
		$query->setLimit(self::SESSION_TO_STATISTIC_PER_ITERATION);
		$query->where('ID', '>', $lastId);
//		$query->where('DATE', '>', $this->getStatisticStartTime());
		return  $query->exec()->fetchAll();
	}

	/**
	 * @param $res
	 */
	private function writeToStatistics($res)
	{
		$params = array(
			'DATE' => $res['DATE'],
			'OPERATOR_ID' => $res['OPERATOR_ID'],
			'OPEN_LINE_ID' => $res['OPEN_LINE_ID'],
			'SOURCE_ID' => $res['SOURCE_ID'],
			'MARK' => $res['MARK'],
			'IS_CHAT_CREATED_NEW' => $res['IS_CHAT_CREATED_NEW'],
			'SECS_TO_ANSWER' => $res['TIME_FIRST_ANSWER'] ?: 0,
		);
		switch ($res['STATUS'])
		{
			case Session::STATUS_SKIP:
				$params['STATUS'] = Dialog::STATUS_SKIPPED;
				break;
			case Session::STATUS_ANSWER:
				$params['STATUS'] = Dialog::STATUS_ANSWERED;
				break;
			default:
				$params['STATUS'] = Dialog::STATUS_ANSWERED;
		}

		Manager::writeToStatistics(Manager::TREATMENT_STATISTIC_KEY, $params);
		Manager::writeToStatistics(Manager::TREATMENT_BY_HOUR_STATISTIC_KEY, $params);
		Manager::writeToStatistics(Manager::DIALOG_ANSWER_STATISTIC_KEY, $params);
		Manager::writeToStatistics(Manager::MARK_STATISTIC_KEY, $params);

		$params['STATUS'] = Dialog::STATUS_NO_PRECESSED;
		Manager::writeToStatistics(Manager::DIALOG_CREATE_STATISTIC_KEY, $params);
	}

	/**
	 * @return int
	 */
	private function getSessionsCount()
	{
		return SessionTable::getCount();
	}

	private function cleanStatisticTables()
	{
		StatisticQueueTable::clean();
		DialogStatTable::clean();
		TreatmentByHourStatTable::clean();
	}

	/**
	 * @return DateTime
	 */
	private function getStatisticStartTime()
	{
		$now = time();
		$twoMonthAgoTimeStamp = $now - (self::DAY_COUNT_FOR_PAST_STATISTIC * 24 * 60 * 60);
		return DateTime::createFromTimestamp($twoMonthAgoTimeStamp);
	}
}