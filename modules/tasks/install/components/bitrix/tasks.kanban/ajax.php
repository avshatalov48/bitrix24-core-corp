<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$APPLICATION->IncludeComponent('bitrix:tasks.kanban', '', array(
	'IS_AJAX' => 'Y'
), false, array(
	'HIDE_ICONS' => 'Y'
));

\CMain::finalActions();
die();