<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule('intranet');

$arLang = array();
$dbRes = CLanguage::GetList($by = 'def', $order = 'desc', array('ACTIVE' => 'Y'));
while ($arRes = $dbRes->Fetch())
{
	$arLang[$arRes['LID']] = '['.$arRes['LID'].'] '.$arRes['NAME'];
}

$arComponentParameters = array(
	'GROUPS' => array(
		'FILTER' => array(
			'NAME' => GetMessage('INTR_COMP_IS_GROUP_FILTER'),
		),
	),
	'PARAMETERS' => array(
		'AJAX_MODE' => array(),
		'STRUCTURE_PAGE' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'structure.php',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_STRUCTURE_PAGE'),
			'PARENT' => 'BASE'
		),

		'PM_URL' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/personal/messages/chat/#USER_ID#/',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_PM_URL'),
			'PARENT' => 'BASE'
		),

		"PATH_TO_USER_EDIT" => array(
			"NAME" => GetMessage('INTR_PARAM_PATH_TO_USER_EDIT'),
			"TYPE" => "STRING",
			"DEFAULT" => '/company/personal/user/#user_id#/edit/',
			'PARENT' => 'BASE'
		),

		'PATH_TO_CONPANY_DEPARTMENT' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_PATH_TO_CONPANY_DEPARTMENT'),
			'PARENT' => 'BASE'
		),
		
		'STRUCTURE_FILTER' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'structure',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_STRUCTURE_FILTER'),
			'PARENT' => 'BASE'
		),
		
		'FILTER_1C_USERS' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'N',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_FILTER_1C_USERS'),
			'PARENT' => 'BASE'
		),
		
		'FILTER_NAME' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'users',
			'PARENT' => 'FILTER',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_FILTER_NAME'),
		),

		'FILTER_DEPARTMENT_SINGLE' => array(
			'TYPE' => 'LIST',
			'VALUES' => array('Y' => GetMessage('INTR_COMP_IS_PARAM_FILTER_DEPARTMENT_SINGLE_VALUE_Y'), 'N' => GetMessage('INTR_COMP_IS_PARAM_FILTER_DEPARTMENT_SINGLE_VALUE_N')),
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'PARENT' => 'FILTER',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_FILTER_DEPARTMENT_SINGLE'),
		),

		'FILTER_SESSION' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'PARENT' => 'FILTER',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_FILTER_SESSION'),
		),
		
		'FILTER_SECTION_CURONLY' => array(
			'TYPE' => 'LIST',
			'VALUES' => array('Y' => GetMessage('INTR_COMP_IS_PARAM_FILTER_SECTION_CURONLY_VALUE_Y'), 'N' => GetMessage('INTR_COMP_IS_PARAM_FILTER_SECTION_CURONLY_VALUE_N')),
			'MULTIPLE' => 'N',
			'DEFAULT' => 'N',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_FILTER_SECTION_CURONLY'),
			'PARENT' => 'BASE'
		),

		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_ISL_PARAM_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'Y',
			'DEFAULT' => '',
			'PARENT' => 'BASE',
		),

		"SHOW_LOGIN" => Array(
			"NAME" => GetMessage("INTR_ISL_PARAM_SHOW_LOGIN"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"VALUE" => "Y",
			"DEFAULT" =>"Y",
			"PARENT" => "BASE",
		),	
		
		'SHOW_ERROR_ON_NULL' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_SHOW_ERROR_ON_NULL'),
			'PARENT' => 'BASE'
		),
		
		'ALPHABET_LANG' => array(
			'TYPE' => 'LIST',
			'MULTIPLE' => 'Y',
			'ADDITIONAL_VALUES' => 'Y',
			'VALUES' => $arLang,
			'DEFAULT' => array(LANGUAGE_ID),
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_ALPHABET_LANG'),
			'PARENT' => 'BASE',
		),
		'NAV_TITLE' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => GetMessage('INTR_COMP_IS_PARAM_NAV_TITLE_DEFAULT'),
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_NAV_TITLE'),
			'PARENT' => 'BASE'
		),
		
		'SHOW_NAV_TOP' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_SHOW_NAV_TOP'),
			'PARENT' => 'BASE'
		),
		
		'SHOW_NAV_BOTTOM' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_SHOW_NAV_BOTTOM'),
			'PARENT' => 'BASE'
		),
		'SHOW_UNFILTERED_LIST' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'N',
			'NAME' => GetMessage('INTR_COMP_IS_PARAM_SHOW_UNFILTERED_LIST'),
			'PARENT' => 'BASE'
		),
		
		"DATE_FORMAT" => CComponentUtil::GetDateFormatField(GetMessage("INTR_COMP_IS_PARAM_DATE_FORMAT"), 'ADDITIONAL_SETTINGS'),
		"DATE_FORMAT_NO_YEAR" => CComponentUtil::GetDateFormatField(GetMessage("INTR_COMP_IS_PARAM_DATE_FORMAT_NO_YEAR"), 'ADDITIONAL_SETTINGS', true),
		"DATE_TIME_FORMAT" => CComponentUtil::GetDateTimeFormatField(GetMessage("INTR_COMP_IS_PARAM_DATE_TIME_FORMAT"), 'ADDITIONAL_SETTINGS'),

		'SHOW_DEP_HEAD_ADDITIONAL' => array(
			'TYPE'     => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT'  => 'N',
			'NAME'     => GetMessage('INTR_ISL_PARAM_SHOW_DEP_HEAD_ADDITIONAL'),
			'PARENT'   => 'ADDITIONAL_SETTINGS'
		),

		"SHOW_YEAR" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("INTR_COMP_IS_PARAM_SHOW_YEAR"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"Y" => GetMessage("INTR_COMP_IS_PARAM_SHOW_YEAR_VALUE_Y"),
				"M" => GetMessage("INTR_COMP_IS_PARAM_SHOW_YEAR_VALUE_M"),
				"N" => GetMessage("INTR_COMP_IS_PARAM_SHOW_YEAR_VALUE_N")
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "Y"
		),	
		
		'CACHE_TIME' => array('DEFAULT' => 3600),
	),
);

if (IsModuleInstalled("video"))
{
	$arComponentParameters["PARAMETERS"]["PATH_TO_VIDEO_CALL"] = array(
			"NAME" => GetMessage("INTR_COMP_IS_PARAM_PATH_TO_VIDEO_CALL"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/video/#USER_ID#/",
			"PARENT" => "ADDITIONAL_SETTINGS",
		); 
}

//in intranet:structure.list not implement pagination in grouping, so do not always show the number of records per page
if(($arCurrentValues['DEFAULT_VIEW'] == 'list' && $arCurrentValues['LIST_VIEW'] != 'group') || ($arCurrentValues['DEFAULT_VIEW'] == 'table' && $arCurrentValues['TABLE_VIEW'] != 'group_table'))
{
	$arComponentParameters["PARAMETERS"]['USERS_PER_PAGE'] = array(
		'TYPE'     => 'STRING',
		'MULTIPLE' => 'N',
		'DEFAULT'  => '20',
		'NAME'     => GetMessage('INTR_COMP_IS_PARAM_USERS_PER_PAGE'),
		'PARENT'   => 'BASE'
	);
}
?>