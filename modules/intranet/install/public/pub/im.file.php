<?php

define('IM_AJAX_INIT', true);
define('PUBLIC_AJAX_MODE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);
define('STOP_STATISTICS', true);

require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

if (\Bitrix\Main\Loader::includeModule('disk') && \Bitrix\Main\Loader::includeModule('im'))
{
	require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/im/handlers/im.file.php';
}