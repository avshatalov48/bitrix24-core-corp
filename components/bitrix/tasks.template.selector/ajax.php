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

$SITE_ID = isset($_GET["SITE_ID"]) ? $_GET["SITE_ID"] : SITE_ID;

if ($_REQUEST['MODE'] == 'SEARCH')
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	$search = $_REQUEST['SEARCH_STRING'];
	$arFilter = array(
		"%TITLE" => $search,
		"!TPARAM_TYPE" => CTaskTemplates::TYPE_FOR_NEW_USER
	);
	$arGetListParams = array(
		'USER_ID' => \Bitrix\Tasks\Util\User::getId(),
		'USER_IS_ADMIN' => \Bitrix\Tasks\Integration\SocialNetwork\User::isAdmin(),
	);

	if (isset($_GET["FILTER"]))
		$arFilter = array_merge($arFilter, $_GET["FILTER"]);

	$totalTasksToBeSelected = 10;

	if(intval($_REQUEST['TEMPLATE_ID']))
	{
		$arFilter['BASE_TEMPLATE_ID'] = intval($_REQUEST['TEMPLATE_ID']);
		$arFilter['!=ID'] = intval($_REQUEST['TEMPLATE_ID']); // do not link to itself
		$arGetListParams['EXCLUDE_TEMPLATE_SUBTREE'] = true; // do not link to it`s subtree
	}

	$dbRes = CTaskTemplates::GetList(
		array('TITLE' => 'ASC'), 
		$arFilter,
		array('NAV_PARAMS' => array('nTopCount' => 10)),			// nPageTop
		$arGetListParams,
		array('ID', 'TITLE')	// fields to be selected
	);

	$arTasks = array();
	while ($arRes = $dbRes->fetch())
	{
		$arTasks[] = array(
			"ID" => $arRes["ID"],
			"TITLE" => $arRes["TITLE"],
			"STATUS" => CTasks::STATE_PENDING
		);
	}

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arTasks);

	CMain::FinalActions(); // to make events work on bitrix24
	die();
}
