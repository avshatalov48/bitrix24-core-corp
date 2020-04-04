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

$IBLOCK_ID = COption::GetOptionInt('intranet', 'iblock_structure', false);

$arSections = array(0 => '');
if ($IBLOCK_ID !== false)
{
	$dbRes = CIBlockSection::GetTreeList(array('IBLOCK_ID' => $IBLOCK_ID, 'ACTIVE' => 'Y'));
	while ($arRes = $dbRes->Fetch())
	{
		$arSections[$arRes['ID']] = trim(str_repeat('. ', $arRes['DEPTH_LEVEL']-1).' '.$arRes['NAME']);
	}
}


$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'AJAX_MODE' => array(),
		'STRUCTURE_PAGE' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'structure.php',
			'NAME' => GetMessage('INTR_ISBN_PARAM_STRUCTURE_PAGE'),
			'PARENT' => 'BASE'
		),

		'PM_URL' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/personal/messages/chat/#USER_ID#/',
			'NAME' => GetMessage('INTR_ISBN_PARAM_PM_URL'),
			'PARENT' => 'BASE',
		),

		'PATH_TO_CONPANY_DEPARTMENT' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
			'NAME' => GetMessage('INTR_ISBN_PARAM_PATH_TO_CONPANY_DEPARTMENT'),
			'PARENT' => 'BASE',
		),
		
		'STRUCTURE_FILTER' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'structure',
			'NAME' => GetMessage('INTR_ISBN_PARAM_STRUCTURE_FILTER'),
			'PARENT' => 'BASE'
		),
		
		'NUM_USERS' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => 10,
			'NAME' => GetMessage('INTR_ISBN_PARAM_NUM_USERS'),
			'PARENT' => 'BASE',
		),
		
		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_ISBN_PARAM_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'Y',
			'DEFAULT' => "",
			'PARENT' => 'BASE',
		),

		"SHOW_LOGIN" => Array(
			"NAME" => GetMessage("INTR_ISBN_PARAM_SHOW_LOGIN"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"VALUE" => "Y",
			"DEFAULT" =>"Y",
			"PARENT" => "BASE",
		),

		"DATE_FORMAT" => CComponentUtil::GetDateFormatField(GetMessage("INTR_ISBN_PARAM_DATE_FORMAT"), 'ADDITIONAL_SETTINGS'),
		"DATE_FORMAT_NO_YEAR" => CComponentUtil::GetDateFormatField(GetMessage("INTR_ISBN_PARAM_DATE_FORMAT_NO_YEAR"), 'ADDITIONAL_SETTINGS', true),

		"DATE_TIME_FORMAT" => CComponentUtil::GetDateTimeFormatField(GetMessage("INTR_ISBN_PARAM_DATE_TIME_FORMAT"), 'ADDITIONAL_SETTINGS'),

		'SHOW_YEAR' => array(
			'TYPE' => 'LIST',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'VALUES' => array(
				'Y' => GetMessage('INTR_ISBN_PARAM_SHOW_YEAR_VALUE_Y'),
				'M' => GetMessage('INTR_ISBN_PARAM_SHOW_YEAR_VALUE_M'),
				'N' => GetMessage('INTR_ISBN_PARAM_SHOW_YEAR_VALUE_N')
			),
			'NAME' => GetMessage('INTR_ISBN_PARAM_SHOW_YEAR'),
		),
	
		"USER_PROPERTY" => array(
			"NAME" => GetMessage('INTR_ISBN_PARAM_USER_PROPERTY'),
			"TYPE" => "LIST",
			"VALUES" => $userProp,
			"MULTIPLE" => "Y",
			"DEFAULT" => array('UF_DEPARTMENT', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE'),
		),
		
		"DEPARTMENT" => array(
			"NAME" => GetMessage('INTR_PREDEF_DEPARTMENT'),
			"TYPE" => "LIST",
			'VALUES' => $arSections,
			"DEFAULT" => '',
		),
		
		'CACHE_TIME' => array('DEFAULT' => 3600),
	),
);

if (IsModuleInstalled("video"))
{
	$arComponentParameters["PARAMETERS"]["PATH_TO_VIDEO_CALL"] = array(
			"NAME" => GetMessage("INTR_ISBN_PARAM_PATH_TO_VIDEO_CALL"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/video/#USER_ID#/",
			"PARENT" => "ADDITIONAL_SETTINGS",
		); 
}

?>