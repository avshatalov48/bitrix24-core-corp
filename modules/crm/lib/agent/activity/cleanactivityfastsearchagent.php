<?php

namespace Bitrix\Crm\Agent\Activity;

use Bitrix\Crm\Activity\FastSearch\ActivityFastSearchTable;
use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Main\Type\DateTime;
use CCrmDateTimeHelper;

/**
 * This agent remove records from ActivityFastSearchTable older that max search period
 */
class CleanActivityFastSearchAgent extends AgentBase
{
	public static function doRun(): bool
	{
		global $DB;
		$days = ActivityFastSearchTable::CREATED_THRESHOLD_DAYS;
		$dt = (new DateTime())->add("-P{$days}D");

		$dtSqlStr = CCrmDateTimeHelper::DateToSql($dt);

		$sql = "delete from b_crm_act_fastsearch where CREATED < $dtSqlStr";
		$DB->Query($sql);

		return true;
	}
}