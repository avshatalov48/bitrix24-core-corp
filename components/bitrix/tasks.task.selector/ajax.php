<?

use \Bitrix\Main\Localization\Loc;

define('STOP_STATISTICS',    true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);
define('PUBLIC_AJAX_MODE', true);

define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('tasks');

Loc::loadMessages(__FILE__);

$SITE_ID = ($_GET['SITE_ID'] ?? SITE_ID);

if ($_REQUEST['MODE'] === 'SEARCH')
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	$search = $_REQUEST['SEARCH_STRING'];

	if (is_numeric($search))
	{
		$filter = [
			[
				'::LOGIC' => 'OR',
				['%TITLE' => $search],
				['ID' => $search],
			],
		];
	}
	else
	{
		$filter = ['%TITLE' => $search];
	}
	if (isset($_GET['FILTER']))
	{
		$filter = array_merge($filter, $_GET['FILTER']);
	}
	$filter['CHECK_PERMISSIONS'] = 'Y';
	$filter['STATUS'] = [CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];

	$totalTasksToBeSelected = 10;

	$select = ['ID', 'TITLE', 'STATUS'];
	$order = ['TITLE' => 'ASC'];
	$params = [
		'NAV_PARAMS' => ['nTopCount' => $totalTasksToBeSelected],
	];

	$tasks = [];
	$dbRes = CTasks::GetList($order, $filter, $select, $params);
	while ($task = $dbRes->fetch())
	{
		$tasks[] = [
			'ID' => $task['ID'],
			'TITLE' => \Bitrix\Main\Text\Emoji::decode($task['TITLE']),
			'STATUS' => $task['STATUS'],
		];
	}

	$tasksCount = count($tasks);
	if ($tasksCount < 10)
	{
		// Additionally, get not active tasks
		unset($filter['STATUS']);
		$filter['!STATUS'] = [CTasks::STATE_PENDING, CTasks::STATE_IN_PROGRESS];
		$params['NAV_PARAMS']['nTopCount'] = $totalTasksToBeSelected - $tasksCount;

		$dbRes = CTasks::GetList($order, $filter, $select, $params);
		while ($task = $dbRes->fetch())
		{
			$tasks[] = [
				'ID' => $task['ID'],
				'TITLE' => \Bitrix\Main\Text\Emoji::decode($task['TITLE']),
				'STATUS' => $task['STATUS'],
			];
		}
	}

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($tasks);

	CMain::FinalActions(); // to make events work on bitrix24
}