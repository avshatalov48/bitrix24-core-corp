<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule('intranet');
if(!CModule::IncludeModule("socialnetwork"))
	return false;

$arComponentParameters = array(
	'GROUPS' => array(
		'FILTER' => array(
			'NAME' => GetMessage('INTR_IS_GROUP_FILTER'),
		),
	),
	'PARAMETERS' => array(
		'AJAX_MODE' => array(),
		'SEARCH_URL' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'search.php',
			'NAME' => GetMessage('INTR_IS_PARAM_SEARCH_URL'),
			'PARENT' => 'BASE'
		),

		'PM_URL' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/personal/messages/chat/#USER_ID#/',
			'NAME' => GetMessage('INTR_IS_PARAM_PM_URL'),
			'PARENT' => 'BASE',
		),

		'PATH_TO_CONPANY_DEPARTMENT' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
			'NAME' => GetMessage('INTR_IS_PARAM_PATH_TO_CONPANY_DEPARTMENT'),
			'PARENT' => 'BASE',
		),
		
		/*'STRUCTURE_FILTER' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'structure',
			'NAME' => '',
			'PARENT' => 'BASE'
		),*/
		
		'FILTER_1C_USERS' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'NAME' => GetMessage('INTR_IS_PARAM_FILTER_1C_USERS'),
			'PARENT' => 'FILTER'
		),
		
		'FILTER_NAME' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'users',
			'PARENT' => 'FILTER',
			'NAME' => GetMessage('INTR_IS_PARAM_FILTER_NAME'),
		),

		'USERS_PER_PAGE' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '10',
			'NAME' => GetMessage('INTR_IS_PARAM_USERS_PER_PAGE'),
			'PARENT' => 'BASE'
		),

		'FILTER_SECTION_CURONLY' => array(
			'TYPE' => 'LIST',
			'VALUES' => array('Y' => GetMessage('INTR_IS_PARAM_FILTER_SECTION_CURONLY_VALUE_Y'), 'N' => GetMessage('INTR_IS_PARAM_FILTER_SECTION_CURONLY_VALUE_N')),
			'MULTIPLE' => 'N',
			'DEFAULT' => 'N',
			'NAME' => GetMessage('INTR_IS_PARAM_FILTER_SECTION_CURONLY'),
			'PARENT' => 'BASE'
		),

		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_IS_PARAM_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'Y',
			'DEFAULT' => '',
			'PARENT' => 'BASE',
		),

		"SHOW_LOGIN" => Array(
			"NAME" => GetMessage("INTR_IS_PARAM_SHOW_LOGIN"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"VALUE" => "Y",
			"DEFAULT" =>"Y",
			"PARENT" => "BASE",
		),	
		
		'SHOW_ERROR_ON_NULL' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'N',
			'NAME' => GetMessage('INTR_IS_PARAM_SHOW_ERROR_ON_NULL'),
			'PARENT' => 'BASE'
		),
		
		'NAV_TITLE' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => GetMessage('INTR_IS_PARAM_NAV_TITLE_DEFAULT'),
			'NAME' => GetMessage('INTR_IS_PARAM_NAV_TITLE'),
			'PARENT' => 'BASE'
		),
		
		'SHOW_NAV_TOP' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'NAME' => GetMessage('INTR_IS_PARAM_SHOW_NAV_TOP'),
			'PARENT' => 'BASE'
		),
		
		'SHOW_NAV_BOTTOM' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'NAME' => GetMessage('INTR_IS_PARAM_SHOW_NAV_BOTTOM'),
			'PARENT' => 'BASE'
		),
		'SHOW_UNFILTERED_LIST' => array(
			'TYPE' => 'CHECKBOX',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'N',
			'NAME' => GetMessage('INTR_IS_PARAM_SHOW_UNFILTERED_LIST'),
			'PARENT' => 'BASE'
		),
		"DATE_FORMAT" => CComponentUtil::GetDateFormatField(GetMessage("INTR_IS_PARAM_DATE_FORMAT"), 'ADDITIONAL_SETTINGS'),
		"DATE_FORMAT_NO_YEAR" => CComponentUtil::GetDateFormatField(GetMessage("INTR_IS_PARAM_DATE_FORMAT_NO_YEAR"), 'ADDITIONAL_SETTINGS', true),
		"SHOW_YEAR" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("INTR_IS_PARAM_SHOW_YEAR"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"Y" => GetMessage("INTR_IS_PARAM_SHOW_YEAR_VALUE_Y"),
				"M" => GetMessage("INTR_IS_PARAM_SHOW_YEAR_VALUE_M"),
				"N" => GetMessage("INTR_IS_PARAM_SHOW_YEAR_VALUE_N")
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "Y"
		),	
		'CACHE_TIME' => array('DEFAULT' => 3600),
	),
);
$arComponentParameters["PARAMETERS"]["DATE_TIME_FORMAT"] = CComponentUtil::GetDateTimeFormatField(GetMessage("INTR_IS_PARAM_DATE_TIME_FORMAT"), 'ADDITIONAL_SETTINGS');
?>