<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
IncludeModuleLangFile(__FILE__);

$arEntities = array('USER');
if ($ib = COption::GetOptionInt('intranet', 'iblock_structure', false))
	$arEntities[] = 'IBLOCK_'.$ib.'_SECTION';

$arUserFields = array(
	'UF_TIMEMAN' => array(
		'FIELD_NAME' => 'UF_TIMEMAN',
		'USER_TYPE_ID' => 'enumeration',
		'XML_ID' => '',
		'SORT' => 1011,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'DISPLAY' => 'LIST',
			'CAPTION_NO_VALUE' => GetMessage('TM_FIELD_UF_TM_NOVALUE')
		),
		'ENUM' => array(
			'Y' => GetMessage('TM_FIELD_UF_TIMEMAN_Y'),
			'N' => GetMessage('TM_FIELD_UF_TIMEMAN_N'),
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_MAX_START' => array(
		'FIELD_NAME' => 'UF_TM_MAX_START',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1015,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '8',
			'MAX_LENGTH' => '5',
			'DEFAULT_VALUE' => '00:00',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_MIN_FINISH' => array(
		'FIELD_NAME' => 'UF_TM_MIN_FINISH',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1019,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '8',
			'MAX_LENGTH' => '5',
			'DEFAULT_VALUE' => '00:00',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_MIN_DURATION' => array(
		'FIELD_NAME' => 'UF_TM_MIN_DURATION',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1023,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '8',
			'MAX_LENGTH' => '5',
			'DEFAULT_VALUE' => '00:00',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_REPORT_REQ' => array(
		'FIELD_NAME' => 'UF_TM_REPORT_REQ',
		'USER_TYPE_ID' => 'enumeration',
		'XML_ID' => '',
		'SORT' => 1027,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'DISPLAY' => 'LIST',
			'CAPTION_NO_VALUE' => GetMessage('TM_FIELD_UF_TM_NOVALUE')
		),
		'ENUM' => array(
			'Y' => GetMessage('TM_FIELD_UF_TM_REPORT_REQ_Y'),
			'N' => GetMessage('TM_FIELD_UF_TM_REPORT_REQ_N'),
			'A' => GetMessage('TM_FIELD_UF_TM_REPORT_REQ_A'),
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_REPORT_TPL' => array(
		'FIELD_NAME' => 'UF_TM_REPORT_TPL',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1031,
		'MULTIPLE' => 'Y',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '50',
			'ROWS' => '6',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_FREE' => array(
		'FIELD_NAME' => 'UF_TM_FREE',
		'USER_TYPE_ID' => 'enumeration',
		'XML_ID' => '',
		'SORT' => 1034,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'DISPLAY' => 'LIST',
			'CAPTION_NO_VALUE' => GetMessage('TM_FIELD_UF_TM_NOVALUE')
		),
		'ENUM' => array(
			'Y' => GetMessage('TM_FIELD_UF_TM_FREE_Y'),
			'N' => GetMessage('TM_FIELD_UF_TM_FREE_N'),
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_TIME' => array(
		'FIELD_NAME' => 'UF_TM_TIME',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1035,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '8',
			'MAX_LENGTH' => '5',
			'DEFAULT_VALUE' => '17:00',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_DAY' => array(
		'FIELD_NAME' => 'UF_TM_DAY',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1036,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '8',
			'MAX_LENGTH' => '1',
			'DEFAULT_VALUE' => '',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_REPORT_DATE' => array(
		'FIELD_NAME' => 'UF_TM_REPORT_DATE',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1037,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '8',
			'MAX_LENGTH' => '2',
			'DEFAULT_VALUE' => '',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_REPORT_PERIOD' => array(
		'FIELD_NAME' => 'UF_REPORT_PERIOD',
		'USER_TYPE_ID' => 'enumeration',
		'XML_ID' => '',
		'SORT' => 1038,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'I',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'DISPLAY' => 'LIST',
			'CAPTION_NO_VALUE' => GetMessage('TM_FIELD_UF_TM_NOVALUE')
		),
		'ENUM' => array(
			'DAY' => GetMessage('TM_FIELD_UF_TM_DAY'),
			'WEEK' => GetMessage('TM_FIELD_UF_TM_WEEK'),
			'MONTH' => GetMessage('TM_FIELD_UF_TM_MONTH'),
			'NONE' => GetMessage('TM_FIELD_UF_NONE'),
		),
		'ENUM_DEFAULT' => 'N',
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_DELAY_TIME' => array(
		'FIELD_NAME' => 'UF_DELAY_TIME',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1039,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '8',
			'MAX_LENGTH' => '20',
			'DEFAULT_VALUE' => '',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_LAST_REPORT_DATE' => array(
		'FIELD_NAME' => 'UF_LAST_REPORT_DATE',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1040,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '8',
			'MAX_LENGTH' => '20',
			'DEFAULT_VALUE' => '',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_SETTING_DATE' => array(
		'FIELD_NAME' => 'UF_SETTING_DATE',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1040,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '8',
			'MAX_LENGTH' => '20',
			'DEFAULT_VALUE' => '',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
	'UF_TM_ALLOWED_DELTA' => array(
		'FIELD_NAME' => 'UF_TM_ALLOWED_DELTA',
		'USER_TYPE_ID' => 'string',
		'XML_ID' => '',
		'SORT' => 1065,
		'MULTIPLE' => 'N',
		'MANDATORY' => 'N',
		'SHOW_FILTER' => 'N',
		'SHOW_IN_LIST' => 'N',
		'EDIT_IN_LIST' => 'Y',
		'IS_SEARCHABLE' => 'N',
		'SETTINGS' => array(
			'SIZE' => '3',
			'MAX_LENGTH' => '5',
			'DEFAULT_VALUE' => '900',
		),
		'EDIT_FORM_LABEL' => array(),
		'LIST_COLUMN_LABEL' => array(),
		'LIST_FILTER_LABEL' => array()
	),
);

$arLabels = array();
$rsLanguage = CLanguage::GetList($by, $order, array());
while($arLanguage = $rsLanguage->Fetch())
{
	$lang_file = $_SERVER['DOCUMENT_ROOT'].(
			isset($updater) && is_object($updater) && strtoupper(get_class($updater)) === 'CUPDATER'
			? $updater->curModulePath
			: BX_ROOT.'/modules/timeman'
		)
		.'/lang/'.$arLanguage["LID"].'/install/fields.php';

	if (file_exists($lang_file))
	{
		$tmp_mess = __IncludeLang($lang_file, true);

		if ($arLanguage['LID'] == LANGUAGE_ID)
		{
			$arLabels = $tmp_mess;
		}

		foreach ($arUserFields as $key => $field)
		{
			$arUserFields[$key]['EDIT_FORM_LABEL'][$arLanguage['ID']] =
			$arUserFields[$key]['LIST_COLUMN_LABEL'][$arLanguage['ID']] =
			$arUserFields[$key]['LIST_FILTER_LABEL'][$arLanguage['ID']] =
				$tmp_mess['TM_FIELD_'.$field['FIELD_NAME']];
		}
	}
}

$obUserType = new CUserTypeEntity();
$obEnum = new CUserFieldEnum();

foreach ($arEntities as $entity)
{
	$arExistFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields($entity);

	foreach ($arUserFields as $key => $arFields)
	{
		if(array_key_exists($key, $arExistFields))
		{
			if ($arFields["USER_TYPE_ID"] == "enumeration")
			{
				$entities = $obUserType->GetList(Array(),Array("ENTITY_ID"=>$entity,"FIELD_NAME"=>$arFields["FIELD_NAME"]));
				$arEnumValuesUpdate = Array();
				if($arEntity = $entities->Fetch())
				{
					$dbEnumValues = $obEnum->GetList(array(), array("USER_FIELD_ID" =>$arEntity["ID"]));
					while($arEnumValue  = $dbEnumValues->Fetch())
					{
						unset($arFields["ENUM"][$arEnumValue["XML_ID"]]);
					}

					if (count($arFields["ENUM"])>0)
					{
						foreach($arFields["ENUM"] as $key=>$name)
						{
							$arEnumValuesUpdate['n'.$key] = array(
								'VALUE' => $name,
								'XML_ID' => $key,
								'DEF' => $key == $arFields['ENUM_DEFAULT'] ? 'Y' : 'N',
							);
						}
					}

					$obEnum->SetEnumValues($arEntity["ID"], $arEnumValuesUpdate);
				}
			}
			else
				continue;
		}

		$arFields['ENTITY_ID'] = $entity;

		if ($enum = $arFields['ENUM'])
			unset($arFields['ENUM']);

		if ($FIELD_ID = $obUserType->Add($arFields))
		{
			if (is_array($enum))
			{
				$arEnumValues = array();
				foreach ($enum as $key => $name)
				{
					$arEnumValues['n'.$key] = array(
						'VALUE' => $name,
						'XML_ID' => $key,
						'DEF' => $key == $arFields['ENUM_DEFAULT'] ? 'Y' : 'N',
					);
				}
				$obEnum->SetEnumValues($FIELD_ID, $arEnumValues);
			}
		}
	}
}

if (!$bSkipUpdateForm)
{
	$tm_tabs_string =
		'--uf_timeman_section--#----'.$arLabels['TM_FIELD_UF_TIMEMAN'].'--,'
		.'--UF_TIMEMAN--#--'.$arLabels['TM_FIELD_UF_TIMEMAN'].'--,'
		.'--UF_TM_MAX_START--#--'.$arLabels['TM_FIELD_UF_TM_MAX_START'].'--,'
		.'--UF_TM_MIN_FINISH--#--'.$arLabels['TM_FIELD_UF_TM_MIN_FINISH'].'--,'
		.'--UF_TM_MIN_DURATION--#--'.$arLabels['TM_FIELD_UF_TM_MIN_DURATION'].'--,'
		.'--UF_TM_REPORT_REQ--#--'.$arLabels['TM_FIELD_UF_TM_REPORT_REQ'].'--,'
		.'--UF_TM_REPORT_TPL--#--'.$arLabels['TM_FIELD_UF_TM_REPORT_TPL'].'--,'
		.'--UF_TM_FREE--#--'.$arLabels['TM_FIELD_UF_TM_FREE'].'--';

	$arForms = array('user_edit' => 'user_fields_tab');
	if ($ib > 0)
		$arForms['form_section_'.$ib] = 'PICTURE';

	$query = "SELECT NAME,VALUE FROM b_user_option WHERE COMMON='Y' AND CATEGORY='form' AND NAME IN ('".implode("', '", array_keys($arForms))."')";
	$dbRes = $DB->Query($query);
	while ($arRes = $dbRes->Fetch())
	{
		$value = unserialize($arRes['VALUE']);
		if ($value['tabs'])
		{
			if (!strpos($value['tabs'], 'UF_TIMEMAN'))
			{
				$v = preg_split(
					'/(--\s*'.$arForms[$arRes['NAME']].'\s*--#--[^-]*--,)/',
					$value['tabs'],
					2,
					PREG_SPLIT_DELIM_CAPTURE
				);

				if (count($v) == 3)
				{
					$v[1] .= $tm_tabs_string.',';
					$value['tabs'] = implode('', $v);
					$r = CUserOptions::SetOption("form", $arRes['NAME'], $value, $common=true);
				}
			}
		}
	}
}
?>