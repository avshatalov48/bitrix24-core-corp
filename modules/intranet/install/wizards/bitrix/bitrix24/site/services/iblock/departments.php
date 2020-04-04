<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = $WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/departments.xml";
if (!file_exists($_SERVER["DOCUMENT_ROOT"].$iblockXMLFile))
{
	$iblockXMLFile = $WIZARD_SERVICE_RELATIVE_PATH."/xml/".\Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID)."/departments.xml";
}
$iblockCode = "departments"; 
$iblockType = "structure";

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
		"departments", 
		$iblockType, 
		WIZARD_SITE_ID, 
		$permissions = Array(
			"1" => "X",
			"2" => "R",
			WIZARD_PORTAL_ADMINISTRATION_GROUP => "X",
			WIZARD_PERSONNEL_DEPARTMENT_GROUP => "W",
		)
	);
	
	if ($iblockID < 1)
		return;
	
	//IBlock fields
	$iblock = new CIBlock;
	$arFields = Array(
		"CODE" => $iblockCode, 
		"XML_ID" => $iblockCode,
	);
	
	$iblock->Update($iblockID, $arFields);
	
	//Departments add
	$bs = new CIBlockSection;
	$arFields = array(
		"NAME" => COption::GetOptionString("main", "site_name", GetMessage("iblock_dep_name1")),
		"IBLOCK_ID" => $iblockID,
		//"UF_HEAD" => "1"
	);
	$ID = $bs->Add($arFields);
	
	if ($ID > 0)
	{
		$arDepartmentSections = array(		
			array(
				"NAME" => GetMessage("iblock_dep_name2"),
				"IBLOCK_ID" => $iblockID,
				"IBLOCK_SECTION_ID" => $ID
			),
			array(
				"NAME" => GetMessage("iblock_dep_name3"),
				"IBLOCK_ID" => $iblockID,
				"IBLOCK_SECTION_ID" => $ID
			),
			array(
				"NAME" => GetMessage("iblock_dep_name5"),
				"IBLOCK_ID" => $iblockID,
				"IBLOCK_SECTION_ID" => $ID
			),
		
		);
		
		foreach($arDepartmentSections as $department)
			$ID = $bs->Add($department);
	}

	$dbRes = CUserTypeEntity::GetList(Array(), Array("ENTITY_ID" => 'USER', "FIELD_NAME" => 'UF_DEPARTMENT'));
	if ($userField = $dbRes->Fetch())
	{
		$userField['SETTINGS'] = array(
			'DISPLAY' => 'LIST',
			'LIST_HEIGHT' => '8',
			'IBLOCK_ID' => $iblockID
		);

		//default department when user adding
		$res_sect = CIBlockSection::GetList(array(), array("IBLOCK_ID"=>$iblockID, "DEPTH_LEVEL"=>1));
		if($res_sect_arr = $res_sect->Fetch())
			$userField['SETTINGS']['DEFAULT_VALUE'] = $res_sect_arr["ID"];

		$userType = new CUserTypeEntity();
		$userType->Update($userField["ID"], $userField);
	}

	$prop = array(
		"ENTITY_ID" => "SONET_GROUP",
		"FIELD_NAME" => "UF_SG_DEPT",
		"USER_TYPE_ID" => "iblock_section",
		"MULTIPLE" => "Y",
		"SETTINGS" => array(
			'DISPLAY' => 'LIST',
			'LIST_HEIGHT' => '8',
			'IBLOCK_ID' => $iblockID,
			'ACTIVE_FILTER' => 'Y'
		)
	);

	$rsData = CUserTypeEntity::getList(array("ID" => "ASC"), array("ENTITY_ID" => $prop["ENTITY_ID"], "FIELD_NAME" => $prop["FIELD_NAME"]));
	if (!($rsData && ($arRes = $rsData->Fetch())))
	{
		$userField = array(
			"ENTITY_ID" => $prop["ENTITY_ID"],
			"FIELD_NAME" => $prop["FIELD_NAME"],
			"XML_ID" => $prop["FIELD_NAME"],
			"USER_TYPE_ID" => $prop["USER_TYPE_ID"],
			"SORT" => 100,
			"MULTIPLE" => $prop["MULTIPLE"],
			"MANDATORY" => "N",
			"SHOW_FILTER" => "N",
			"SHOW_IN_LIST" => "N",
			"EDIT_IN_LIST" => "Y",
			"IS_SEARCHABLE" => "N",
			"SETTINGS" => $prop["SETTINGS"],
		);

		$dbLangs = CLanguage::GetList(($b = ""), ($o = ""), array("ACTIVE" => "Y"));
		while ($arLang = $dbLangs->Fetch())
		{
			$messages = IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/install/index.php", $arLang["LID"], true);
			$userField["EDIT_FORM_LABEL"][$arLang["LID"]] = $messages["SONET_".$prop["FIELD_NAME"]."_EDIT_FORM_LABEL"];
			$userField["LIST_COLUMN_LABEL"][$arLang["LID"]] = $messages["SONET_".$prop["FIELD_NAME"]."_LIST_COLUMN_LABEL"];
			$userField["LIST_FILTER_LABEL"][$arLang["LID"]] = $messages["SONET_".$prop["FIELD_NAME"]."_LIST_FILTER_LABEL"];
		}

		$uf = new CUserTypeEntity;
		$uf->add($userField, false);
	}
	else
	{
		$dbRes = CUserTypeEntity::GetList(Array(), Array("ENTITY_ID" => 'SONET_GROUP', "FIELD_NAME" => 'UF_SG_DEPT'));
		if ($userField = $dbRes->Fetch())
		{
			$userField['SETTINGS'] = array(
				'DISPLAY' => 'LIST',
				'LIST_HEIGHT' => '8',
				'IBLOCK_ID' => $iblockID,
				'ACTIVE_FILTER' => 'Y'
			);

			$uf = new CUserTypeEntity();
			$uf->update($userField["ID"], $userField);
		}
	}
}
?>