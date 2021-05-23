<?
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule('crm');

$CCrmCompany = new CCrmCompany();
if (!$USER->IsAuthorized() || $CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE))
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
			$dbRes = CCrmCompany::GetList(Array('TITLE'=>'ASC', 'LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('ID' => $matches[1]));
			if ($arRes = $dbRes->Fetch())
			{
				$arData = array(
					array(
						'ID' => $arRes['ID'],
						'NAME' => str_replace(array(';', ','), ' ', $arRes['TITLE']),
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
	$dbRes = CCrmCompany::GetList(Array('TITLE'=>'ASC', 'LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('TITLE' => '%'.trim($search).'%'));
	while ($arRes = $dbRes->Fetch())
	{
		$arData[] = array(
			'ID' => $arRes['ID'],
			'NAME' => str_replace(array(';', ','), ' ', $arRes['TITLE']),
			'READY' => 'Y',
		);
	}
	if (empty($arData))
	{
		$dbRes = CCrmCompany::GetList(Array('TITLE'=>'ASC', 'LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('ID' => intval($search)));
		if ($arRes = $dbRes->Fetch())
		{
			$arData = array(
				array(
					'ID' => $arRes['ID'],
					'NAME' => str_replace(array(';', ','), ' ', $arRes['TITLE']),
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