<?php

namespace Bitrix\Crm\Agent\History;

use Bitrix\Crm\History\DealStageHistoryWithSupposedEntry;
use Bitrix\Crm\History\Entity\DealStageHistoryTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Update\Stepper;

/**
 * Class DealStageHistoryWithSupposed
 * @package Bitrix\Crm\Agent\History
 */
class DealStageHistoryWithSupposed extends Stepper
{
	const MAX_HISTORY_PER_ITERATION = 100;
	const STATISTIC_PRE_CALCULATE_SUPPOSED_HISTORY_FINISH = 'statisticPreCalculateSupposedHistoryFinish';
	const ANALYTICS_DEAL_SUPPOSED_HISTORY_LAST_EXECUTED_HISTORY_ID = 'analyticsDealSupposedHistoryLastExecutedHistoryId';
	const ANALYTICS_DEAL_SUPPOSED_HISTORY_EXECUTED_STEP_COUNT = 'analyticsDealSupposedHistoryExecutedStepCount';

	protected static $moduleId = 'crm';

	public function execute(array &$option)
	{
		/*hack because in table b_option field `name` has length 50 */
		if (static::class === DealStageHistoryWithSupposed::class || Option::get("crm", self::STATISTIC_PRE_CALCULATE_SUPPOSED_HISTORY_FINISH, 'N') === 'Y')
		{
			return false;
		}

		if(!Application::getConnection()->isTableExists('b_crm_last_stored_deal_stage_history_id'))
		{
			return false;
		}

		$lastStoredHistoryId = Option::get('crm', self::ANALYTICS_DEAL_SUPPOSED_HISTORY_LAST_EXECUTED_HISTORY_ID, 0);
		$executedStepCount = Option::get("crm", self::ANALYTICS_DEAL_SUPPOSED_HISTORY_EXECUTED_STEP_COUNT, 0);

		if ($executedStepCount == 0)
		{
			Option::set("crm", self::STATISTIC_PRE_CALCULATE_SUPPOSED_HISTORY_FINISH, 'N');
			$lastStoredHistoryId = $this->getLastStoredDealStageHistoryId();
		}

		$histories = $this->getHistories($lastStoredHistoryId);

		if (empty($histories))
		{
			Option::delete("crm", ['name' => self::ANALYTICS_DEAL_SUPPOSED_HISTORY_LAST_EXECUTED_HISTORY_ID]);
			Option::delete("crm", ['name' => self::ANALYTICS_DEAL_SUPPOSED_HISTORY_EXECUTED_STEP_COUNT]);
			Option::set("crm", self::STATISTIC_PRE_CALCULATE_SUPPOSED_HISTORY_FINISH, 'Y');
			$this->dropLastStoredDealIdTable();

			return false;
		}

		$executedDealIds = [];
		foreach ($histories as $history)
		{
			$lastStoredHistoryId = $history['ID'];
			$executedDealIds[] = $history['OWNER_ID'];
		}

		$dealIds = array_unique($executedDealIds);
		foreach ($dealIds as $id)
		{
			DealStageHistoryWithSupposedEntry::register($id);
		}

		$executedStepCount++;

		Option::set("crm", self::ANALYTICS_DEAL_SUPPOSED_HISTORY_LAST_EXECUTED_HISTORY_ID, $lastStoredHistoryId);
		Option::set("crm", self::ANALYTICS_DEAL_SUPPOSED_HISTORY_EXECUTED_STEP_COUNT, $executedStepCount);

		$option["steps"] = $executedStepCount;
		$option["count"] = (int)(($this->getHistoryCount() / self::MAX_HISTORY_PER_ITERATION) + 1);

		return true;
	}

	private function getLastStoredDealStageHistoryId()
	{
		static $result = null;
		if (is_null($result))
		{
			global $DB;
			$result = $DB->Query('SELECT DEAL_STAGE_HISTORY_ID FROM b_crm_last_stored_deal_stage_history_id;');
			$result = $result->fetch();
			if (!empty($result['DEAL_STAGE_HISTORY_ID']))
			{
				$result = (int)$result['DEAL_STAGE_HISTORY_ID'];
			}
			else
			{
				$result = 0;
			}
		}

		return $result;
	}

	private function getHistoryCount()
	{
		$result = DealStageHistoryTable::query()
			->addSelect(Query::expr()->count('ID'), 'CNT')
			->setCacheTtl(86400)
			->exec()
			->fetchRaw();

		return $result['CNT'];
	}

	private function getHistories($fromHistoryId = 0)
	{
		$query = DealStageHistoryTable::query();
		$query->addSelect('ID');
		$query->addSelect('OWNER_ID');
		$query->where('ID', '>', (int)$fromHistoryId);
		$query->setLimit(self::MAX_HISTORY_PER_ITERATION);

		return $query->exec()->fetchAll();
	}

	private function dropLastStoredDealIdTable()
	{
		global $DB;
		$DB->Query('DROP TABLE b_crm_last_stored_deal_stage_history_id;');
	}
}



