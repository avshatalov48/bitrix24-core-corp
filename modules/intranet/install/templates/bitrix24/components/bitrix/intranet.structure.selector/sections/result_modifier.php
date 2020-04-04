<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult["CURRENT_SECTION"] = $current_section = intval($arResult['FILTER_VALUES'][$arParams['FILTER_NAME'].'_UF_DEPARTMENT']);
if ($current_section === 0)
{
	$iblockID = COption::GetOptionInt("intranet", "iblock_structure");
	$db_up_department = CIBlockSection::GetList(Array(), Array("SECTION_ID"=>0, "IBLOCK_ID"=>$iblockID));
	if ($ar_up_department = $db_up_department->Fetch())
	{
		$arResult["CURRENT_SECTION"] = $current_section = $ar_up_department["ID"];
	}
	$GLOBALS[$arParams['FILTER_NAME'].'_UF_DEPARTMENT'] = $current_section;
}

$arResult['SECTIONS'] = array();

$iblockID = COption::GetOptionInt("intranet", "iblock_structure"); 
$rsSections = CIBlockSection::GetList(
	array(),
	array("IBLOCK_ID" => $iblockID, "SECTION_ID" => $current_section, 'GLOBAL_ACTIVE' => 'Y'),
	false,
	array("ID", "NAME", "SECTION_PAGE_URL", "UF_HEAD")
);
while($arSection = $rsSections->GetNext())
{
	if (intval($arSection["UF_HEAD"]) > 0)
	{
		$dbUser = \Bitrix\Main\UserTable::getList(array('filter' => array('ACTIVE' => 'Y', 'ID' => $arSection['UF_HEAD'])));
		if ($arUser = $dbUser->Fetch())
			$arSection["UF_HEAD_NAME"] = $name = CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser);
		else
			$arSection['UF_HEAD'] = null;
	}
	$arResult['SECTIONS'][] = $arSection;
}

// department data:
if ($arParams['SHOW_SECTION_INFO'] == 'Y' && $current_section)
{
	$dbRes = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $iblockID, "ID" => $current_section), false, array("UF_HEAD"));
	if ($arResult['SECTION_DATA'] = $dbRes->GetNext())
	{
		if ($arResult['SECTION_DATA']['DETAIL_PICTURE'])
			$arResult['SECTION_DATA']['DETAIL_PICTURE'] = CFile::ShowImage($arResult['SECTION_DATA']['DETAIL_PICTURE']);
		elseif ($arResult['SECTION_DATA']['PICTURE'])
			$arResult['SECTION_DATA']['DETAIL_PICTURE'] = CFile::ShowImage($arResult['SECTION_DATA']['PICTURE']);
	//uf_head:
		$dbUser = CUser::GetList($b="", $o="", array("ID" => intval($arResult['SECTION_DATA']["UF_HEAD"]), "ACTIVE" => "Y"), array('FIELDS' => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "EMAIL", "CONFIRM_CODE", "PERSONAL_PHOTO", "PERSONAL_MOBILE", "WORK_POSITION", "LAST_ACTIVITY_DATE", "LAST_LOGIN"), 'SELECT' => array("UF_SKYPE")));
		if ($arUser = $dbUser->GetNext())
		{	
			$arResult['SECTION_DATA']["UF_HEAD"] = $arUser;
			$arResult['SECTION_DATA']["UF_HEAD"]["FORMATTED_NAME"] = CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser, true, false);
			
			if (intval($arResult['SECTION_DATA']["UF_HEAD"]["PERSONAL_PHOTO"]) > 0)
			{
				$imageFile = CFile::GetFileArray($arResult['SECTION_DATA']["UF_HEAD"]["PERSONAL_PHOTO"]);
				if ($imageFile !== false)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$imageFile,
						array("width" => 100, "height" => 100),
						BX_RESIZE_IMAGE_EXACT,
						false
					);
					$arResult['SECTION_DATA']["UF_HEAD"]["PERSONAL_PHOTO"] = $arFileTmp["src"];
				}
				else
					$arResult['SECTION_DATA']["UF_HEAD"]["PERSONAL_PHOTO"]= "";
			}
			if (!empty($arUser["CONFIRM_CODE"]))
				$arResult['SECTION_DATA']["UF_HEAD"]["ACTIVITY_STATUS"] = "inactive";
			else
				$arResult['SECTION_DATA']["UF_HEAD"]["ACTIVITY_STATUS"] = "active";
				
		}
	// head department:		
		$resSec = CIBlockSection::GetList(array(), array("IBLOCK_ID" => $iblockID, "ID" => $arResult['SECTION_DATA']["IBLOCK_SECTION_ID"]), false, array("ID", "NAME","UF_HEAD", "SECTION_PAGE_URL"));
		if ($arSection = $resSec->GetNext())
		{
			$arResult['SECTION_DATA']["IBLOCK_SECTION_NAME"] = $arSection["NAME"];
			$arResult['SECTION_DATA']["IBLOCK_SECTION_UF_HEAD"] = $arSection["UF_HEAD"];
			$dbUser = CUser::GetList($b="", $o="", array("ID" => intval($arSection["UF_HEAD"])));
			if ($arUser = $dbUser->Fetch())
				$arResult['SECTION_DATA']["IBLOCK_SECTION_UF_HEAD_NAME"] = $name = CUser::FormatName($arParams['NAME_TEMPLATE'], $arUser);
		}
	}
}

$bChainFinished = false;
$obEnum = new CUserTypeIBlockSection;
$rsEnum = $obEnum->GetList($arResult["UF_DEPARTMENT_field"]);
while ($arRes = $rsEnum->GetNext())
{
	$arRes['IBLOCK_SECTION_ID'] = intval($arRes['IBLOCK_SECTION_ID']);

	if ($current_section && !$bChainFinished)
	{
		$arResult['SECTIONS_CHAIN'][$arRes['DEPTH_LEVEL']-1] = array($arRes['ID'], $arRes['NAME']);
		if ($current_section == $arRes['ID'])
		{
			$bChainFinished = true;
			if (true || $arParams['SET_TITLE'] == 'Y')
				$GLOBALS['APPLICATION']->SetTitle($arRes['NAME']);
			$arResult['SECTIONS_CHAIN'] = array_slice($arResult['SECTIONS_CHAIN'], 0, $arRes['DEPTH_LEVEL']);
		}
	}
}

$arRemove = array($arParams['FILTER_NAME'].'_UF_DEPARTMENT');
if (defined('BX_AJAX_PARAM_ID')) $arRemove[] = BX_AJAX_PARAM_ID;
if (is_array($arResult['SECTIONS_CHAIN']))
{
	foreach ($arResult['SECTIONS_CHAIN'] as $key => $arItem)
	{
		$arItem[2] = $GLOBALS['APPLICATION']->GetCurPageParam(
			$arParams['FILTER_NAME'].'_UF_DEPARTMENT='.$arItem[0],
			$arRemove
		);

		$GLOBALS['APPLICATION']->AddChainItem($arItem[1], $arItem[2]);
		$arResult['SECTIONS_CHAIN'][$key] = $arItem;
	}
}

$IBLOCK_PERMISSION = CIBlock::GetPermission(COption::GetOptionInt('intranet', 'iblock_structure'));

$sectionHeadId = 0;
if (!empty($arResult['SECTION_DATA']['UF_HEAD']))
{
	$sectionHeadId = is_array($arResult['SECTION_DATA']['UF_HEAD'])
		? $arResult['SECTION_DATA']['UF_HEAD']['ID']
		: $arResult['SECTION_DATA']['UF_HEAD'];
}
$userNum = 0;
$arFilter = Array("ACTIVE" => 'Y', "UF_DEPARTMENT" => $current_section);
$rsUsers = CUser::GetList($by = 'ID', $order = 'ASC', $arFilter); 
while ($arUser = $rsUsers->fetch())
{
	if ($arUser['ID'] != $sectionHeadId)
		$userNum++;
}
$arResult['SECTION_DATA']["USER_COUNT"] = $userNum;