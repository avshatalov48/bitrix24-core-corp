<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule('intranet');

$arUserFieldNames = array('PERSONAL_PHOTO', 'FULL_NAME', 'ID','LOGIN','NAME','SECOND_NAME','LAST_NAME','EMAIL','DATE_REGISTER','PERSONAL_PROFESSION','PERSONAL_WWW','PERSONAL_BIRTHDAY','PERSONAL_ICQ','PERSONAL_GENDER','PERSONAL_PHONE','PERSONAL_FAX','PERSONAL_MOBILE','PERSONAL_PAGER','PERSONAL_STREET','PERSONAL_MAILBOX','PERSONAL_CITY','PERSONAL_STATE','PERSONAL_ZIP','PERSONAL_COUNTRY','PERSONAL_NOTES', 'PERSONAL_POSITION', 'WORK_POSITION', 'WORK_COMPANY', 'WORK_PHONE', 'WORK_FAX', 'ADMIN_NOTES','XML_ID');

$userProp = array();

foreach ($arUserFieldNames as $name)
{
	$userProp[$name] = GetMessage('ISL_'.$name);
}

$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);
if (!empty($arRes))
{
	foreach ($arRes as $key => $val)
	{
		$userProp[$val["FIELD_NAME"]] = '* '.(strlen($val["EDIT_FORM_LABEL"]) > 0 ? $val["EDIT_FORM_LABEL"] : $val["FIELD_NAME"]);
	}
}

$arComponentParameters = array(
	'PARAMETERS' => array(
		'PM_URL' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/personal/messages/chat/#USER_ID#/',
			'NAME' => GetMessage('INTR_ISE_PARAM_PM_URL'),
			'PARENT' => 'BASE',
		),
		
		'PATH_TO_CONPANY_DEPARTMENT' => array(
			'TYPE' => 'ADDITIONAL_SETTINGS',
			'DEFAULT' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
			'NAME' => GetMessage('INTR_ISE_PARAM_PATH_TO_CONPANY_DEPARTMENT'),
			'PARENT' => 'BASE',
		),

		'STRUCTURE_PAGE' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => 'structure.php',
			'NAME' => GetMessage('INTR_ISE_PARAM_STRUCTURE_PAGE'),
			'PARENT' => 'BASE',
		),
		
		'STRUCTURE_FILTER' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => 'structure',
			'NAME' => GetMessage('INTR_ISE_PARAM_STRUCTURE_FILTER'),
			'PARENT' => 'BASE',
		),

		'NUM_USERS' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '10',
			'NAME' => GetMessage('INTR_ISE_PARAM_NUM_USERS'),
			'PARENT' => 'BASE'
		),
		
		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_ISE_PARAM_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'Y',
			'DEFAULT' => "",
			'PARENT' => 'BASE',
		),

		"SHOW_LOGIN" => Array(
			"NAME" => GetMessage("INTR_ISE_PARAM_SHOW_LOGIN"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"VALUE" => "Y",
			"DEFAULT" =>"Y",
			"PARENT" => "BASE",
		),	
		
		'NAV_TITLE' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => GetMessage('INTR_ISE_PARAM_NAV_TITLE_DEFAULT'),
			'NAME' => GetMessage('INTR_ISE_PARAM_NAV_TITLE'),
		),
		
		'SHOW_NAV_TOP' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'N',
			'NAME' => GetMessage('INTR_ISE_PARAM_SHOW_NAV_TOP'),
		),

		'SHOW_NAV_BOTTOM' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'NAME' => GetMessage('INTR_ISE_PARAM_SHOW_NAV_BOTTOM'),
		),

		"USER_PROPERTY" => array(
			"NAME" => GetMessage('INTR_ISE_PARAM_USER_PROPERTY'),
			"TYPE" => "LIST",
			"VALUES" => $userProp,
			"MULTIPLE" => "Y",
			"DEFAULT" => array('UF_DEPARTMENT', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE'),
		),
		
		"DATE_FORMAT" => CComponentUtil::GetDateFormatField(GetMessage("INTR_ISE_PARAM_DATE_FORMAT"), 'ADDITIONAL_SETTINGS'),
		"DATE_FORMAT_NO_YEAR" => CComponentUtil::GetDateFormatField(GetMessage("INTR_ISE_PARAM_DATE_FORMAT_NO_YEAR"), 'ADDITIONAL_SETTINGS', true),
		"DATE_TIME_FORMAT" => CComponentUtil::GetDateTimeFormatField(GetMessage("INTR_ISE_PARAM_DATE_TIME_FORMAT"), 'ADDITIONAL_SETTINGS'),

		"SHOW_YEAR" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("INTR_ISE_PARAM_SHOW_YEAR"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"Y" => GetMessage("INTR_ISE_PARAM_SHOW_YEAR_VALUE_Y"),
				"M" => GetMessage("INTR_ISE_PARAM_SHOW_YEAR_VALUE_M"),
				"N" => GetMessage("INTR_ISE_PARAM_SHOW_YEAR_VALUE_N")
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "Y"
		),	
		
		"CACHE_TIME" => array('DEFAULT' => 3600),
	),
);

if (IsModuleInstalled("video"))
{
	$arComponentParameters["PARAMETERS"]["PATH_TO_VIDEO_CALL"] = array(
			"NAME" => GetMessage("INTR_ISE_PARAM_PATH_TO_VIDEO_CALL"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/video/#USER_ID#/",
			"PARENT" => "ADDITIONAL_SETTINGS",
		); 
}

?>