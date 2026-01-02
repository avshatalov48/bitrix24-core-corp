<?php

namespace Bitrix\Crm\Agent\RepeatSale;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\RepeatSale\AllowedAgentsTimeManager;
use Bitrix\Crm\RepeatSale\Logger;

class JobExecutorRecreatorAgent extends AgentBase
{
	public const AGENT_DONE_STOP_IT = false;

	public static function doRun(): bool
	{
		if (AllowedAgentsTimeManager::getInstance()->isUseTimeLimit())
		{
			return self::AGENT_DONE_STOP_IT;
		}

		$jobExecutorAgentName = 'Bitrix\Crm\Agent\RepeatSale\JobExecutorAgent::run();';
		$jobExecutorAgent = \CAgent::GetList([], ['NAME' => $jobExecutorAgentName, 'MODULE_ID' => 'crm'])->Fetch();

		if (is_array($jobExecutorAgent))
		{
			\CAgent::Delete($jobExecutorAgent['ID']);

			/**
			 * @see \Bitrix\Crm\Agent\RepeatSale\JobExecutorAgent
			 */
			\CAgent::AddAgent(
				'Bitrix\Crm\Agent\RepeatSale\JobExecutorAgent::run();',
				'crm',
				'N',
				60,
				'',
				'Y',
				\ConvertTimeStamp(time() + \CTimeZone::GetOffset() + 800, 'FULL'),
			);

			(new Logger())->info('JobExecutorAgent recreated successfully', []);
		}

		AllowedAgentsTimeManager::getInstance()->setUseTimeLimit(true);

		return self::AGENT_DONE_STOP_IT;
	}
}
