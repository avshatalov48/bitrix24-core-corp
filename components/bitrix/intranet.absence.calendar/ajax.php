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
	$APPLICATION->IncludeComponent(
		'bitrix:intranet.absence.calendar', 
		'', 
		array(
			"AJAX_CALL" => "DATA", 
			"CALLBACK" => 'jsBXAC.SetData',
			'SITE_ID' => $_REQUEST['site_id'],
			'IBLOCK_ID' => $_REQUEST['iblock_id'],
			'CALENDAR_IBLOCK_ID' => $_REQUEST['calendar_iblock_id'],
			
			"FILTER_SECTION_CURONLY" => $_REQUEST['section_flag'] == 'Y' ? 'Y' : 'N',
			"TS_START" => $_REQUEST['TS_START'],
			"TS_FINISH" => $_REQUEST['TS_FINISH'],
			'PAGE_NUMBER' => $_REQUEST['PAGE_NUMBER'],
			"TYPES" => $_REQUEST['TYPES'],
			"DEPARTMENT" => $_REQUEST['DEPARTMENT'],
			"SHORT_EVENTS" => $_REQUEST['SHORT_EVENTS'],
			"USERS_ALL" => $_REQUEST['USERS_ALL'],
			"CURRENT_DATA_ID" => $_REQUEST['current_data_id']
		)
	); 

}
//require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>
