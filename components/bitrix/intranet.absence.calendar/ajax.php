<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$MODE = $_GET['MODE'] ? $_GET['MODE'] : 'GET';

if ($MODE == 'VIEW')
{
	$APPLICATION->includeComponent(
		'bitrix:intranet.absence.calendar.view',
		in_array($_GET['VIEW'], array('day', 'week', 'month')) ? $_GET['VIEW'] : '',
		array()
	);
}
elseif ($MODE == 'INFO')
{
	if ($_GET['ID'])
	{
		$APPLICATION->IncludeComponent(
			'bitrix:intranet.absence.calendar',
			'',
			array(
				"AJAX_CALL" => "INFO",
				'SITE_ID' => $_GET['SITE_ID'],
				'ID' => intval($_GET['ID']),
				'IBLOCK_ID' => intval($_GET['IBLOCK']),
				'TYPE' => intval($_GET['TYPE']),
			)
		);
	}
}
elseif ($MODE == 'GET')
{
	if (
		\Bitrix\Main\Loader::includeModule('bitrix24')
		&& COption::GetOptionString('bitrix24', 'absence_limits_enabled', '') === 'Y'
		&& !\Bitrix\Bitrix24\Feature::isFeatureEnabled('absence')
	)
	{
		return;
	}

	$APPLICATION->IncludeComponent(
		'bitrix:intranet.absence.calendar',
		'',
		array(
			"AJAX_CALL" => "DATA",
			"CALLBACK" => 'jsBXAC.SetData',
			'SITE_ID' => $_REQUEST['site_id'] ?? null,
			'IBLOCK_ID' => $_REQUEST['iblock_id'] ?? 0,
			'CALENDAR_IBLOCK_ID' => $_REQUEST['calendar_iblock_id'] ?? 0,

			"FILTER_SECTION_CURONLY" => isset($_REQUEST['section_flag']) && $_REQUEST['section_flag'] == 'Y' ? 'Y' : 'N',
			"TS_START" => $_REQUEST['TS_START'] ?? null,
			"TS_FINISH" => $_REQUEST['TS_FINISH'] ?? null,
			'PAGE_NUMBER' => $_REQUEST['PAGE_NUMBER'] ?? null,
			"TYPES" => $_REQUEST['TYPES'] ?? null,
			"DEPARTMENT" => $_REQUEST['DEPARTMENT'] ?? null,
			"SHORT_EVENTS" => $_REQUEST['SHORT_EVENTS'] ?? null,
			"USERS_ALL" => $_REQUEST['USERS_ALL'] ?? null,
			"CURRENT_DATA_ID" => $_REQUEST['current_data_id'] ?? 0,
		)
	);

}
//require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>
