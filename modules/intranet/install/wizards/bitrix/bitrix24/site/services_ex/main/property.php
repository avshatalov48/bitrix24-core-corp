<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arProperties = Array(

	'UF_PUBLIC' => Array(
		'ENTITY_ID' => 'USER',
		'FIELD_NAME' => 'UF_PUBLIC',
		'USER_TYPE_ID' => 'boolean',
		'XML_ID' => 'UF_PUBLIC',
		'SORT' => 100,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'Y',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'DISPLAY' => 'CHECKBOX',
		),
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

}
?>