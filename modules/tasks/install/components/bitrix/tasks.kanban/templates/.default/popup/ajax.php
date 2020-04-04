<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();

if (
	($type = $request->get('type')) &&
	($type == 'P' || $type == 'K')
)
{
	$popupsShowed = \CUserOptions::getOption(
		'tasks',
		'kanban_demo_showed',
		array()
	);
	$popupsShowed[] = $type;
	\CUserOptions::setOption(
		'tasks',
		'kanban_demo_showed',
		array_unique($popupsShowed)
	);
}

\CMain::finalActions();
die();