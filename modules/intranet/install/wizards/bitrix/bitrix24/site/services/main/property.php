<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arProperties = Array(

	'UF_PHONE_INNER' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_PHONE_INNER',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 2,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'S',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),

	'UF_1C' => Array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_1C',
		'USER_TYPE_ID' => 'boolean',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'N',
		'IS_SEARCHABLE' => 'Y',
		'SETTINGS' => array(
			'DISPLAY' => 'CHECKBOX',
		),
	),

	'UF_INN' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_INN',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
	
	'UF_DISTRICT' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_DISTRICT',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
	'UF_SKYPE' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_SKYPE',
		'USER_TYPE_ID' => 'string_formatted',
		'XML_ID' => 'UF_SKYPE',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
		'SETTINGS' => array('PATTERN' => '<a href="callto:#VALUE#">#VALUE#</a>'),
	),
	'UF_TWITTER' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_TWITTER',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
	'UF_FACEBOOK' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_FACEBOOK',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
	'UF_LINKEDIN' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_LINKEDIN',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
	'UF_XING' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_XING',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
	'UF_WEB_SITES' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_WEB_SITES',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
	'UF_SKILLS' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_SKILLS',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
	'UF_INTERESTS' => array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_INTERESTS',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'Y',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'Y',
	),
);

$arLanguages = Array();
$rsLanguage = CLanguage::GetList($by, $order, array());
while($arLanguage = $rsLanguage->Fetch())
	$arLanguages[] = $arLanguage["LID"];

foreach ($arProperties as $arProperty)
{
	$dbRes = CUserTypeEntity::GetList(Array(), Array("ENTITY_ID" => $arProperty["ENTITY_ID"], "FIELD_NAME" => $arProperty["FIELD_NAME"]));
	if ($dbRes->Fetch())
		continue;

	$arLabelNames = Array();
	foreach($arLanguages as $languageID)
	{
		WizardServices::IncludeServiceLang("property_names.php", $languageID);
		$arLabelNames[$languageID] = GetMessage($arProperty["FIELD_NAME"]);
	}

	$arProperty["EDIT_FORM_LABEL"] = $arLabelNames;
	$arProperty["LIST_COLUMN_LABEL"] = $arLabelNames;
	$arProperty["LIST_FILTER_LABEL"] = $arLabelNames;

	$userType = new CUserTypeEntity();
	$success = (bool)$userType->Add($arProperty);

	//if($ex = $APPLICATION->GetException())
		//$strError = $ex->GetString();
}
?>