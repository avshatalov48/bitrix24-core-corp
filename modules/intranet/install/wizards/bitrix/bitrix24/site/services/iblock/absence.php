<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockXMLFile = $WIZARD_SERVICE_RELATIVE_PATH."/xml/".LANGUAGE_ID."/absence.xml";
if (!file_exists($_SERVER["DOCUMENT_ROOT"].$iblockXMLFile))
{
	$iblockXMLFile = $WIZARD_SERVICE_RELATIVE_PATH."/xml/".\Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID)."/absence.xml";
}
$iblockCode = "absence"; 
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
		"absence", 
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

	$arProperties = Array("USER", "FINISH_STATE", "STATE", "ABSENCE_TYPE");
	foreach ($arProperties as $propertyName)
	{
		${$propertyName."_PROPERTY_ID"} = 0;
		$properties = CIBlockProperty::GetList(Array(), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $iblockID, "CODE" => $propertyName));
		if ($arProperty = $properties->Fetch())
			${$propertyName."_PROPERTY_ID"} = $arProperty["ID"];
	}
	
	$aFormOptions = array('tabs'=>'edit1--#--'.GetMessage('ABSENCE_FORM_1').'--,--PROPERTY_'.$USER_PROPERTY_ID.'--#--'.GetMessage('ABSENCE_FORM_2').'--,--PROPERTY_'.$ABSENCE_TYPE_PROPERTY_ID.'--#--'.GetMessage('ABSENCE_FORM_3').'--,--NAME--#--*'.GetMessage('ABSENCE_FORM_4').'--,--edit1_csection1--#----'.GetMessage('ABSENCE_FORM_5').'--,--ACTIVE_FROM--#--'.GetMessage('ABSENCE_FORM_6').'--,--ACTIVE_TO--#--'.GetMessage('ABSENCE_FORM_7').'--;--');
	WizardServices::SetIBlockFormSettings($iblockID, $aFormOptions);

	//IBlock fields
	$iblock = new CIBlock;
	$arFields = Array(
		"ACTIVE" => "Y",
		"FIELDS" => array(
			'IBLOCK_SECTION'    => array('IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''),
			'ACTIVE'            => array('IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y'),
			'ACTIVE_FROM'       => array('IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ''),
			'ACTIVE_TO'         => array('IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ''),
			'SORT'              => array('IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''),
			'NAME'              => array('IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => ''),
			'PREVIEW_PICTURE'   => array('IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array('FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N')),
			'PREVIEW_TEXT_TYPE' => array('IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text'),
			'PREVIEW_TEXT'      => array('IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''),
			'DETAIL_PICTURE'    => array('IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array('SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N')),
			'DETAIL_TEXT_TYPE'  => array('IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text'),
			'DETAIL_TEXT'       => array('IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''),
			'XML_ID'            => array('IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''),
			'CODE'              => array('IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''),
			'TAGS'              => array('IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''),
		),
		"CODE" => $iblockCode, 
		"XML_ID" => $iblockCode, 
	);
	
	$iblock->Update($iblockID, $arFields);
}
?>