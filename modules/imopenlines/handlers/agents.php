<?php
@set_time_limit(0);
@ignore_user_abort(true);
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
define("NO_AGENT_STATISTIC", true);
define("NO_AGENT_CHECK", true);
define("IMOPENLINES_EXEC_CRON", true);

use \Bitrix\ImOpenLines\Session\Agent;
use \Bitrix\ImOpenLines\Log\ExecLog;

require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

$lock = sys_get_temp_dir()."/startimopenlinesagents.lock";
$fp = fopen($lock, 'w');

if (flock($fp, LOCK_EX|LOCK_NB))
{
	register_shutdown_function(
		function() use ($fp, $lock)
		{
			flock($fp, LOCK_UN);
			fclose($fp);
			unlink($lock);
		}
	);

	if(\Bitrix\Main\Loader::includeModule('imopenlines'))
	{
		Agent::transferToNextInQueue(0);
		Agent::closeByTime(0);
		Agent::mailByTime(0);

		if (ExecLog::isTimeToExec('Bitrix\ImOpenLines\Session\Agent::dismissedOperator'))
		{
			Agent::dismissedOperator(0);
		}
	}
}

CMain::FinalActions();
die();