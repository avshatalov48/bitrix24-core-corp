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
	{
		continue;
	}

	$arLabelNames = Array();
	foreach($arLanguages as $languageID)
	{
		CExtranetWizardServices::IncludeServiceLang("property_names.php", $languageID);
		$arLabelNames[$languageID] = GetMessage($arProperty["FIELD_NAME"]);
	}

	$arProperty["EDIT_FORM_LABEL"] = $arLabelNames;
	$arProperty["LIST_COLUMN_LABEL"] = $arLabelNames;
	$arProperty["LIST_FILTER_LABEL"] = $arLabelNames;

	$userType = new CUserTypeEntity();
	$success = (bool)$userType->Add($arProperty);

	if ($arProperty["FIELD_NAME"] == "UF_PUBLIC")
	{
		$arCustomTabs = array();
		$customTabs = CUserOptions::GetOption("form", "user_edit", false, 0);
		if (
			$customTabs
			&& $customTabs["tabs"]
		)
		{
			$arTabs = explode("--;--", $customTabs["tabs"]);
			foreach($arTabs as $customFields)
			{
				if ($customFields == "")
				{
					continue;
				}

				$arCustomFields = explode("--,--", $customFields);
				$arCustomTabID = "";
				foreach($arCustomFields as $customField)
				{
					if($arCustomTabID == "")
					{
						list($arCustomTabID, $arCustomTabName) = explode("--#--", $customField);
						$arCustomTabs[$arCustomTabID] = array(
							"TAB" => $arCustomTabName,
							"FIELDS" => array(),
						);
					}
					else
					{
						list($arCustomFieldID, $arCustomFieldName) = explode("--#--", $customField);
						$arCustomFieldName = ltrim($arCustomFieldName, defined("BX_UTF")? "* -\xa0\xc2": "* -\xa0");
						$arCustomTabs[$arCustomTabID]["FIELDS"][$arCustomFieldID] = $arCustomFieldName;
					}
				}
			}
		}

		if (
			!empty($arCustomTabs)
			&& array_key_exists("user_fields_tab", $arCustomTabs)
			&& is_array($arCustomTabs["user_fields_tab"])
			&& array_key_exists("FIELDS", $arCustomTabs["user_fields_tab"])
			&& is_array($arCustomTabs["user_fields_tab"]["FIELDS"])
			&& array_key_exists("USER_FIELDS_ADD", $arCustomTabs["user_fields_tab"]["FIELDS"])
			&& !array_key_exists("UF_PUBLIC", $arCustomTabs["user_fields_tab"]["FIELDS"])
		)
		{
			$arFieldsNew = array();

			foreach ($arCustomTabs["user_fields_tab"]["FIELDS"] as $field_code => $field_name)
			{
				$arFieldsNew[$field_code] = $field_name;
				if ($field_code == "USER_FIELDS_ADD")
				{
					$arFieldsNew["UF_PUBLIC"] = $arLabelNames[LANGUAGE_ID];
				}
			}

			$arCustomTabs["user_fields_tab"]["FIELDS"] = $arFieldsNew;

			$option = "";
			foreach($arCustomTabs as $arCustomTabID => $arTab)
			{
				if (is_array($arTab) && isset($arTab["TAB"]))
				{
					$option .= $arCustomTabID.'--#--'.$arTab["TAB"];
					if (isset($arTab["FIELDS"]) && is_array($arTab["FIELDS"]))
					{
						foreach ($arTab["FIELDS"] as $arCustomFieldID => $arCustomFieldName)
						{
							$option .= '--,--'.$arCustomFieldID.'--#--'.$arCustomFieldName;
						}
					}
				}
				$option .= '--;--';
			}
			CUserOptions::SetOption("form", "user_edit", array("tabs" => $option), true, 0);
		}
	}
}
?>