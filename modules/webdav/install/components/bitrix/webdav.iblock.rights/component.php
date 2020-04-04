<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!CModule::IncludeModule('iblock')):
	return false;
endif;

if (!CModule::IncludeModule("webdav")):
	ShowError(GetMessage("W_WEBDAV_IS_NOT_INSTALLED"));
	return 0;
endif;
//require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/components/bitrix/webdav/functions.php");

CUtil::InitJSCore();
/********************************************************************
				Input params
********************************************************************/
/***************** BASE ********************************************/
	$arParams["IBLOCK_ID"] = isset($arParams['IBLOCK_ID']) ? intval($arParams['IBLOCK_ID']) : 0;
	$arParams["ENTITY_TYPE"] = (isset($arParams['ENTITY_TYPE']) && in_array($arParams['ENTITY_TYPE'], array('IBLOCK', 'ELEMENT', 'SECTION'))) ? $arParams['ENTITY_TYPE'] : '';
	$arParams["ENTITY_ID"] = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
	$arParams['SOCNET_GROUP_ID'] = (isset($arParams['SOCNET_GROUP_ID']) && (intval($arParams['SOCNET_GROUP_ID']) > 0)) ? intval($arParams['SOCNET_GROUP_ID']) : 0;
	$arParams["SOCNET_TYPE"] = (isset($arParams['SOCNET_TYPE']) && in_array($arParams['SOCNET_TYPE'], array('user', 'group'))) ? $arParams['SOCNET_TYPE'] : '';
	$arParams["SOCNET_ID"] = isset($arParams['SOCNET_ID']) ? intval($arParams['SOCNET_ID']) : 0;
/***************** ADDITIONAL **************************************/
    $arParams["ACTION"] = (isset($_REQUEST["ACTION"]) ? strtolower($_REQUEST["ACTION"]) : '');
    $arParams["ACTION"] = (in_array($arParams["ACTION"], array("set_rights")) ? $arParams["ACTION"] : '');
	$bCreate = (isset($arParams['CREATE']));

    $arResult["NOTIFICATIONS"] = array(
        //"recover" => GetMessage("BPADH_RECOVERY_OK"), 
        //"delete" => GetMessage("BPADH_DELETE_OK"), 
    );

    if (!empty($_REQUEST["result"]) && array_key_exists($_REQUEST["result"], $arResult["NOTIFICATIONS"])) 
        $arResult["OK_MESSAGE"] = $arResult["NOTIFICATIONS"][$_REQUEST["result"]];
/***************** STANDART ****************************************/
	$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] == "N" ? "N" : "Y");

	if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
		$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
	else
		$arParams["CACHE_TIME"] = 0;
	global $CACHE_MANAGER;
/********************************************************************
				/Input params
********************************************************************/

/********************************************************************
				Main data
********************************************************************/
$arError = array();
if (!$bCreate)
{
	if ($arParams["IBLOCK_ID"] <= 0)
		$arError[] = array(
			"id" => "empty_iblock_id",
			"text" => GetMessage("WD_PERMS_NO_IBLOCK_ID"));
	if ($arParams["ENTITY_ID"] <= 0)
		$arError[] = array(
			"id" => "empty_entity_id",
			"text" => GetMessage("WD_PERMS_NO_ENTITY_ID"));
}
if (strlen($arParams["ENTITY_TYPE"]) <= 0)
	$arError[] = array(
		"id" => "empty_entity_type",
		"text" => GetMessage("WD_PERMS_NO_ENTITY_TYPE"));

if (empty($arError))
{
	# get permissions for selected element
}
else
{
	$e = new CAdminException($arError);
	ShowError($e->GetString());
	return false;
}
/********************************************************************
				/Main data
********************************************************************/

$arResult["ERROR_MESSAGE"] = "";

/********************************************************************
				Action
********************************************************************/
$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/action.php");
include($path);
/********************************************************************
				/Action
********************************************************************/

/********************************************************************
				Data
********************************************************************/
$obIBlockRights = null;
$arResult['ENTITY_NAME'] = '';
$arResult['ENTITY_PARENTS'] = array();
if (!$bCreate)
{
	if ($arParams["ENTITY_TYPE"] === 'IBLOCK')
	{
		$obIBlockRights = new CIBlockRights($arParams['ENTITY_ID']);
		$checkOP = 'iblock_rights_edit';
		$dbIB = CIBlock::GetList(array(), array(
			'ID' => $arParams['ENTITY_ID'],
			'CHECK_PERMISSIONS' => 'N')
		);
		if ($dbIB && $arIB = $dbIB->Fetch())
		{
			$arResult['ENTITY_NAME'] = $arIB['NAME'];
		}
	}
	elseif ($arParams["ENTITY_TYPE"] === 'SECTION')
	{
		$obIBlockRights = new CIBlockSectionRights($arParams['IBLOCK_ID'],$arParams['ENTITY_ID']);
		$checkOP = 'section_rights_edit';
		$dbIB = CIBlockSection::GetList(array(), array(
			'ID' => $arParams['ENTITY_ID'],
			'CHECK_PERMISSIONS' => 'N')
		);
		if ($dbIB && $arIB = $dbIB->Fetch())
		{
			$arResult['ENTITY_NAME'] = $arIB['NAME'];
			$arResult['ENTITY_PARENTS'][] = $arIB['IBLOCK_SECTION_ID'];
		}
	}
	elseif ($arParams["ENTITY_TYPE"] === 'ELEMENT')
	{
		$obIBlockRights = new CIBlockElementRights($arParams['IBLOCK_ID'],$arParams['ENTITY_ID']);
		$checkOP = 'element_rights_edit';
		$dbIB = CIBlockElement::GetList(
			array(),
			array(
				'ID' => $arParams['ENTITY_ID'],
				'IBLOCK_ID' => $arParams['IBLOCK_ID'],
			),
			false,
			false,
			array(
				'ID',
				'IBLOCK_ID',
				'IBLOCK_SECTION_ID',
				'NAME'
			)
		);
		if ($dbIB && $arIB = $dbIB->Fetch())
		{
			$arResult['ENTITY_NAME'] = $arIB['NAME'];
			$arResult['ENTITY_PARENTS'][] = $arIB['IBLOCK_SECTION_ID'];
		}
	}
	if (sizeof($arResult['ENTITY_PARENTS']) > 0 && intval($arResult['ENTITY_PARENTS'][0]) > 0)
	{
		$dbChain = CIBlockSection::GetNavChain($arParams['IBLOCK_ID'], $arResult['ENTITY_PARENTS'][0]);
		if ($dbChain)
		{
			while ($arChain = $dbChain->Fetch())
			{
				$arResult['ENTITY_PARENTS'][] = $arChain['IBLOCK_SECTION_ID'];
			}
		}
	}
}

if (($USER->CanDoOperation('webdav_change_settings')) || ($obIBlockRights && $obIBlockRights->UserHasRightTo($arParams['IBLOCK_ID'], $arParams['ENTITY_ID'], $checkOP)))
{
	$arTasks = CIBlockRights::GetRightsList();
	$arTaskLetters = CWebDavIblock::GetTasks();
	//bad hack. It's not public rights.
	if(isset($arTaskLetters['S']))
	{
		unset($arTasks[$arTaskLetters['S']]);
	}
	if(isset($arTaskLetters['T']))
	{
		unset($arTasks[$arTaskLetters['T']]);
	}

	$arResult['PERMISSIONS'] = $arTasks;

	if (!$bCreate)
	{
		$arRightParams = array("count_overwrited" => true);
		if (!empty($arResult['ENTITY_PARENTS']))
		{
			$arRightParams['parents'] = $arResult['ENTITY_PARENTS'];
		}
		$arCurrent = $obIBlockRights->GetRights($arRightParams);

		foreach($arCurrent as $arRightSet)
			$arNames[] = $arRightSet["GROUP_CODE"];
		$access = new CAccess();
		$arSubjs = $access->GetNames($arNames);

		if ((!empty($arParams['SOCNET_TYPE'])) && (!$USER->CanDoOperation('webdav_change_settings')) && (intval($arParams['SOCNET_ID'])>0))
		{
			foreach($arCurrent as $rightID => &$arRight)
			{
				if (
					(($arRight['GROUP_CODE'] === 'G1') && ($arRight['IS_INHERITED'] === 'Y') && ($arRight['TASK_ID'] === $arTaskLetters['X'])) ||
					(($arRight['GROUP_CODE'] === 'G2') && ($arRight['IS_INHERITED'] === 'Y') && ($arRight['TASK_ID'] === $arTaskLetters['D']))
				)
				{
					unset($arCurrent[$rightID]); // commont rights
					continue;
				}
				if ($arParams['SOCNET_TYPE'] == 'group')
				{
					if (($arRight['GROUP_CODE'] === 'SG'.$arParams['SOCNET_ID'].'_A') && ($arRight['TASK_ID'] === $arTaskLetters['X']))
						$arRight['IS_INHERITED'] = 'Y'; // group admin
				} elseif ($arParams['SOCNET_TYPE'] == 'user') {
					if (($arRight['GROUP_CODE'] === 'U'.$arParams['SOCNET_ID']) && ($arRight['TASK_ID'] === $arTaskLetters['X']))
						$arRight['IS_INHERITED'] = 'Y'; // user
				}
			}
			unset($arRight);
		}

		foreach($arCurrent as $rightID => &$arRight)
		{
			if ((strpos($arRight['GROUP_CODE'], "SG") === 0) && (strpos($arRight['GROUP_CODE'], "_") === false))
			{
				unset($arCurrent[$rightID]);
				continue;
			}
			$arRight['ENTITY_SOURCE_NAME'] = '';
			$arRight['ENTITY_SOURCE_URL'] = '';

			$arRight['ENTITY_SELF'] = false;
			if (isset($arRight['ENTITY_ID']) && isset($arRight['ENTITY_TYPE']))
			{
				$arRight['ENTITY_SELF'] = (
					intval($arRight['ENTITY_ID']) === intval($arParams['ENTITY_ID']) &&
					intval($arRight['ENTITY_TYPE']) === intval($arParams['ENTITY_TYPE']));
			}

			if ($arRight['IS_INHERITED'] == 'Y' && isset($arRight['ENTITY_TYPE']) && isset($arRight['ENTITY_ID']) && (intval($arRight['ENTITY_ID']) > 0))
			{
				$dbIB = null;
				if ($arRight['ENTITY_TYPE'] == 'iblock')
				{
					$dbIB = CIBlock::GetList(array(), array('ID' => intval($arRight['ENTITY_ID'])));
				}
				elseif ($arRight['ENTITY_TYPE'] == 'section')
				{
					$dbIB = CIBlockSection::GetList(array(), array('ID' => intval($arRight['ENTITY_ID'])));
				}
				if ($dbIB && $arIB=$dbIB->Fetch())
				{
					$arRight['ENTITY_SOURCE_NAME'] = $arIB['NAME'];
				}
				if (($arRight['ENTITY_TYPE'] == 'iblock') || ($arRight['ENTITY_TYPE'] == 'section'))
				{
					$urlParams = array(
						"IBLOCK_ID" => $arParams["IBLOCK_ID"],
						"ENTITY_TYPE" => strtoupper($arRight['ENTITY_TYPE']),
						"ENTITY_ID" => intval($arRight['ENTITY_ID'])
					);
					if (!empty($arParams["SOCNET_TYPE"]) && $arParams['SOCNET_ID'] > 0)
					{
						$urlParams['SOCNET_TYPE'] = $arParams["SOCNET_TYPE"];
						$urlParams['SOCNET_ID'] = $arParams['SOCNET_ID'];
					}

					$url = WDAddPageParams(
						"/bitrix/components/bitrix/webdav.section.list/templates/.default/iblock_e_rights.php",
						$urlParams
					);
					$arRight['ENTITY_SOURCE_URL'] = $url;
				}
			}
		}

		$arResult['DATA'] = $arCurrent;
		$arResult['SUBJECTS'] = $arSubjs;
	}
	else
	{
		$arResult['DATA'] = array();
		$arResult['SUBJECTS'] = array();
	}
/********************************************************************
			/Data
********************************************************************/

	$this->IncludeComponentTemplate();
}

/********************************************************************
				Standart operations
********************************************************************/
if($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(GetMessage("WD_PERMS_TITLE"));
}
/********************************************************************
				/Standart operations
********************************************************************/
?>
