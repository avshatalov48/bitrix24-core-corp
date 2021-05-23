<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$MODE = $_REQUEST['mode'];
$IBLOCK_ID = intval($_REQUEST['ID']);
if ($MODE && $IBLOCK_ID > 0 || $MODE == 'test')
{
	$APPLICATION->RestartBuffer();
	$APPLICATION->IncludeComponent('bitrix:sharepoint.link', '', array(
		'IBLOCK_ID' => $IBLOCK_ID,
		'MODE' => $MODE
	), null, array('HIDE_ICONS' => 'Y'));
}

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>