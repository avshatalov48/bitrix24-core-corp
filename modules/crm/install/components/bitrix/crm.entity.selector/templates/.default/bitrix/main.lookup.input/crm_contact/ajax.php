<?
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('crm');

$CCrmCompany = new CCrmContact();
if (!$USER->IsAuthorized() || $CCrmCompany->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE))
	die();

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

if ($_REQUEST['MODE'] == 'SEARCH')
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();
	
	$search = $_REQUEST['search'];

	$matches = array();
	if (preg_match('/^\[(\d+?)]/i', $search, $matches))
	{
		$matches[1] = intval($matches[1]);
		if ($matches[1] > 0)
		{
			$dbRes = CCrmContact::GetList(Array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('ID' => $matches[1]));
			if ($arRes = $dbRes->Fetch())
			{
				$arData = array(
					array(
						'ID' => $arRes['ID'],
						'NAME' => str_replace(array(';', ','), ' ', (isset($arRes['LAST_NAME'])? $arRes['LAST_NAME'].' ': '').$arRes['NAME']),
						'READY' => 'Y',
					)
				);
				Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
				echo CUtil::PhpToJsObject($arData);
				die();
			}
		}
	}
	$arData = array();
	$dbRes = CCrmContact::GetList(Array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('FULL_NAME' => trim($search)));
	while ($arRes = $dbRes->Fetch())
	{
		$arData[] = array(
			'ID' => $arRes['ID'],
			'NAME' => str_replace(array(';', ','), ' ', (isset($arRes['LAST_NAME'])? $arRes['LAST_NAME'].' ': '').$arRes['NAME']),
			'READY' => 'Y',
		);
	}
	if (empty($arData))
	{
		$dbRes = CCrmContact::GetList(Array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('ID' => intval($search)));
		if ($arRes = $dbRes->Fetch())
		{
			$arData = array(
				array(
					'ID' => $arRes['ID'],
					'NAME' => str_replace(array(';', ','), ' ', (isset($arRes['LAST_NAME'])? $arRes['LAST_NAME'].' ': '').$arRes['NAME']),
					'READY' => 'Y',
				)
			);
			Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
			echo CUtil::PhpToJsObject($arData);
			die();
		}
	}
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arData);
	die();
}
?>