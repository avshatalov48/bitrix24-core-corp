<?
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

/** @global CUser $USER */
global $USER;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	die();
}

if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('IBLOCK_MODULE_NOT_INSTALLED'));
	die();
}

CUtil::JSPostUnescape();

$iblock_id = intval($_REQUEST["IBLOCK_ID"]);

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	die();
}

$arIBlock = array();
if ($iblock_id > 0)
	$arIBlock = CIBlock::GetArrayByID($iblock_id);

if($_REQUEST['MODE'] == 'SEARCH')
{
	$APPLICATION->RestartBuffer();

	$arResult = array();
	if ($iblock_id > 0)
	{
		$search = $_REQUEST['search'];

		$matches = array();
		if(preg_match('/^(.*?)\[([\d]+?)\]/i', $search, $matches))
		{
			$matches[2] = intval($matches[2]);
			if($matches[2] > 0)
			{
				$dbRes = CIBlockElement::GetList(
					array(),
					array("IBLOCK_ID" => $arIBlock["ID"], "=ID" => $matches[2]),
					false,
					false,
					array("ID", "NAME")
				);
				if($arRes = $dbRes->Fetch())
				{
					$arResult[] = array(
						'ID' => $arRes['ID'],
						'NAME' => $arRes['NAME'],
						'READY' => 'Y',
					);

					Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
					echo CUtil::PhpToJsObject($arResult);
					die();
				}
			}
			elseif(strlen($matches[1]) > 0)
			{
				$search = $matches[1];
			}
		}

		$dbRes = CIBlockElement::GetList(
			array(),
			array("IBLOCK_ID" => $arIBlock["ID"], "%NAME" => $search),
			false,
			array("nTopCount" => 20),
			array("ID", "NAME")
		);

		while($arRes = $dbRes->Fetch())
		{
			$arResult[] = array(
				'ID' => $arRes['ID'],
				'NAME' => $arRes['NAME'],
			);
		}
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arResult);
	die();
}
?>