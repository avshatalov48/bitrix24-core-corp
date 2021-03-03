<?php
@set_time_limit(0);
@ignore_user_abort(true);
define('NOT_CHECK_PERMISSIONS', true);
define('STOP_STATISTICS', true);
define('NO_AGENT_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('IMOPENLINES_EXEC_CRON', true);

use \Bitrix\Main\Loader,
	\Bitrix\ImOpenLines\Session\Agent;

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if(Loader::includeModule('imopenlines'))
{
	Agent::mailByTime(0);
	Agent::closeByTime(0);
	Agent::sendMessageNoAnswer();
	Agent::sendAutomaticMessage();

	Agent::transferToNextInQueue(0);
}

\CMain::FinalActions();
die();