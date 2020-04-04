<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockID = WizardServices::ImportIBlockFromXML(
	$WIZARD_SERVICE_RELATIVE_PATH."/xml/_".LANGUAGE_ID."/calendar_groups.xml",
	$iblockCode = "calendar_groups_extranet",
	$iblockType = "events",
	WIZARD_SITE_ID,
	$permissions = Array(
		"1" => "X",
		WIZARD_EXTRANET_ADMIN_GROUP => "X",
		WIZARD_EXTRANET_GROUP => "R",
	)
);

if ($iblockID < 1)
	return;

$arProperties = Array("PERIOD_TYPE", "PERIOD_COUNT", "EVENT_LENGTH", "PERIOD_ADDITIO");
foreach ($arProperties as $propertyName)
{
	${$propertyName."_PROPERTY_ID"} = 0;
	$properties = CIBlockProperty::GetList(Array(), Array("ACTIVE"=>"Y", "IBLOCK_ID" => $iblockID, "CODE" => $propertyName));
	if ($arProperty = $properties->Fetch())
		${$propertyName."_PROPERTY_ID"} = $arProperty["ID"];
}

WizardServices::SetIBlockFormSettings($iblockID, Array ( 'tabs' => GetMessage("W_IB_CALENDAR_GROUPS_TAB1").$PERIOD_TYPE_PROPERTY_ID.GetMessage("W_IB_CALENDAR_GROUPS_TAB2").$PERIOD_COUNT_PROPERTY_ID.GetMessage("W_IB_CALENDAR_GROUPS_TAB3").$EVENT_LENGTH_PROPERTY_ID.GetMessage("W_IB_CALENDAR_GROUPS_TAB4").$PERIOD_ADDITIONAL_PROPERTY_ID.GetMessage("W_IB_CALENDAR_GROUPS_TAB5"), ));

//IBlock fields
$iblock = new CIBlock;
$arFields = Array(
	"ACTIVE" => "Y",
	"FIELDS" => array ( 'IBLOCK_SECTION' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'Y', ), 'ACTIVE_FROM' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'ACTIVE_TO' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'SORT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'NAME' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => '', ), 'PREVIEW_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'FROM_DETAIL' => 'N', 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'PREVIEW_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'PREVIEW_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'DETAIL_PICTURE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => array ( 'SCALE' => 'N', 'WIDTH' => '', 'HEIGHT' => '', 'IGNORE_ERRORS' => 'N', ), ), 'DETAIL_TEXT_TYPE' => array ( 'IS_REQUIRED' => 'Y', 'DEFAULT_VALUE' => 'text', ), 'DETAIL_TEXT' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'XML_ID' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'CODE' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), 'TAGS' => array ( 'IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => '', ), )
);

$iblock->Update($iblockID, $arFields);

//CWizardUtil::ReplaceMacros(WIZARD_SITE_PATH."/contacts/personal.php", Array("CALENDAR_GROUPS_IBLOCK_ID" => $iblockID));

?>
