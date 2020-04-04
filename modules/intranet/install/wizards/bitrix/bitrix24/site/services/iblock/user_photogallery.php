<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = $WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/user_photogallery.xml";
if (!file_exists($_SERVER["DOCUMENT_ROOT"].$iblockXMLFile))
{
	$iblockXMLFile = $WIZARD_SERVICE_RELATIVE_PATH."/xml/".\Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID)."/user_photogallery.xml";
}
$iblockCode = "user_photogallery"; 
$iblockType = "photos";

$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType));
$iblockID = false; 
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"]; 
}

if($iblockID == false)
{
	$iblockID = WizardServices::ImportIBlockFromXML(
		$iblockXMLFile,
		"user_photogallery",
		$iblockType,
		WIZARD_SITE_ID,
		$permissions = Array(
			"1" => "X",
			"2" => "R",
			WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
		)
	);
	
	if ($iblockID < 1)
		return;
	
	$ibSection = new CIBlockSection;
	$dbSection = CIBlockSection::GetList(Array(), Array("ACTIVE" => "Y", "IBLOCK_ID" => $iblockID));
	while ($arSection = $dbSection->Fetch())
	{
		$arFields = Array("ACTIVE" => "Y", "CREATED_BY" => 1, "SOCNET_GROUP_ID" => false);
		if ($arSection["CODE"] == "user_1")
		{
			$rsUser = CUser::GetByID(1);
			if ($arUser = $rsUser->Fetch())
			{
				$userName = CUser::FormatName(CSite::GetNameFormat(false), $arUser);
				if (strlen(trim($userName)) > 0)
					$arFields["NAME"] = $userName;
			}
		}
	
		$ibSection->Update($arSection["ID"], $arFields);
	}
	
	$arProperties = Array("APPROVE_ELEMENT", "REAL_PICTURE", "PUBLIC_ELEMENT", "FORUM_TOPIC_ID", "FORUM_MESSAGE_CNT", "vote_count", "vote_sum", "rating");
	foreach ($arProperties as $propertyName)
	{
		${$propertyName."_PROPERTY_ID"} = 0;
		$properties = CIBlockProperty::GetList(Array(), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $iblockID, "CODE" => $propertyName));
		if ($arProperty = $properties->Fetch())
			${$propertyName."_PROPERTY_ID"} = $arProperty["ID"];
	}
	
	WizardServices::SetIBlockFormSettings($iblockID, Array ( 'tabs' => GetMessage("W_IB_USER_PHOTOG_TAB1").$REAL_PICTURE_PROPERTY_ID.GetMessage("W_IB_USER_PHOTOG_TAB2").$rating_PROPERTY_ID.GetMessage("W_IB_USER_PHOTOG_TAB3").$vote_count_PROPERTY_ID.GetMessage("W_IB_USER_PHOTOG_TAB4").$vote_sum_PROPERTY_ID.GetMessage("W_IB_USER_PHOTOG_TAB5").$APPROVE_ELEMENT_PROPERTY_ID.GetMessage("W_IB_USER_PHOTOG_TAB6").$PUBLIC_ELEMENT_PROPERTY_ID.GetMessage("W_IB_USER_PHOTOG_TAB7"), ));
	
	//IBlock fields
	$iblock = new CIBlock;
	$arFields = Array(
		"ACTIVE" => "Y",
		"FIELDS" => array ( 'IBLOCK_SECTION' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y', ), 'ACTIVE_FROM' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE_TO' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'SORT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'NAME' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => '', ), 'PREVIEW_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'PREVIEW_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'PREVIEW_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'DETAIL_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'DETAIL_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'DETAIL_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'XML_ID' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'CODE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'TAGS' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), ),
		"CODE" => $iblockCode, 
		"XML_ID" => $iblockCode,
	);
	
	$iblock->Update($iblockID, $arFields);
	
//=====================in sonet log
/*	if(!CModule::IncludeModule("socialnetwork"))
		return;
	$arFields["ID"] = array();
	$db_res = CIBlockElement::GetList(array(), array("XML_ID" => array("2865", "2873", "2876", "2871", "2879")), false, false, array("ID"));
	while($ar_res = $db_res->Fetch())
	{
		$arFields["ID"][] = $ar_res['ID'];
	}
	
	$arLogParams = array(
		"COUNT" => 5, 
		"IBLOCK_TYPE" => "photos",//$arComponentParams["IBLOCK_TYPE"], 
		"IBLOCK_ID" => $iblockID,//$arComponentParams["IBLOCK_ID"],
		"DETAIL_URL" => "/company/personal/user/1/photo/photo/#SECTION_ID#/#ELEMENT_ID#/",//$arComponentParams["DETAIL_URL"],
		"ALIAS" => "user_1",//$arComponentParams["USER_ALIAS"],
		"arItems" => $arFields["ID"]
	);

	$sAuthorName = GetMessage("SONET_LOG_GUEST"); 
	$sAuthorUrl = "";
	if ($GLOBALS["USER"]->IsAuthorized())
	{
		$sAuthorName = trim($GLOBALS["USER"]->GetFormattedName(false));
		$sAuthorName = (empty($sAuthorName) ? $GLOBALS["USER"]->GetLogin() : $sAuthorName);
	}

	$db_res = CIBlockSection::GetList(array(), array("XML_ID"=>"751"), false,  array("ID"));
	$ar_res = $db_res->Fetch();
	$arFields["IBLOCK_SECTION"] = $ar_res["ID"];
	
	$entity_type = SONET_ENTITY_USER;
	$entity_id = "1";
	$arSonetFields = array(
		"ENTITY_TYPE" => $entity_type,
		"ENTITY_ID" => $entity_id,
		"EVENT_ID" => "photo",
		"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
		"TITLE_TEMPLATE" => str_replace("#AUTHOR_NAME#", $sAuthorName, GetMessage("SONET_PHOTO_LOG_1")),
		"TITLE" => str_replace("#COUNT#", "5", GetMessage("SONET_PHOTO_LOG_2")),
		"MESSAGE" => "",
		"URL" => str_replace(array("#SECTION_ID#", "#section_id#"), $arFields["IBLOCK_SECTION"], '/company/personal/user/1/photo/album/#SECTION_ID#/'),
		"MODULE_ID" => false,
		"CALLBACK_FUNC" => false,
		"EXTERNAL_ID" => $arFields["IBLOCK_SECTION"]."_1",//.$arFields["MODIFIED_BY"],  
		"PARAMS" => serialize($arLogParams),
		"SOURCE_ID" => $arFields["IBLOCK_SECTION"],
		"SITE_ID" => "s1",
	);

	if ($GLOBALS["USER"]->IsAuthorized())
		$arSonetFields["USER_ID"] = $GLOBALS["USER"]->GetID();

	$logID = CSocNetLog::Add($arSonetFields, false);
	
	if (intval($logID) > 0)
	{
		CSocNetLog::Update($logID, array("TMP_ID" => $logID));
		CSocNetLogRights::SetForSonet($logID, $entity_type, $entity_id, "photo", "view", true);
	}*/
}
?>
