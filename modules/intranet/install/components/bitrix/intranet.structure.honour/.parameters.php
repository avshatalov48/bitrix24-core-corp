<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule('intranet');

$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'NUM_USERS' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '50',
			'NAME' => GetMessage('INTR_ISBN_PARAM_NUM_USERS'),
			'PARENT' => 'BASE',
		),
		
		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_ISH_PARAM_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'Y',
			'DEFAULT' => "",
			'PARENT' => 'BASE',
		),

		"SHOW_LOGIN" => Array(
			"NAME" => GetMessage("INTR_ISH_PARAM_SHOW_LOGIN"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"VALUE" => "Y",
			"DEFAULT" =>"Y",
			"PARENT" => "BASE",
		),	

		'PM_URL' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/personal/messages/chat/#USER_ID#/',
			'NAME' => GetMessage('INTR_ISH_PARAM_PM_URL'),
			'PARENT' => 'BASE',
		),
		
		'PATH_TO_CONPANY_DEPARTMENT' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
			'NAME' => GetMessage('INTR_ISH_PARAM_PATH_TO_CONPANY_DEPARTMENT'),
			'PARENT' => 'BASE',
		),

		"DATE_FORMAT" => CComponentUtil::GetDateFormatField(GetMessage("INTR_ISH_PARAM_DATE_FORMAT"), 'ADDITIONAL_SETTINGS'),
		"DATE_FORMAT_NO_YEAR" => CComponentUtil::GetDateFormatField(GetMessage("INTR_ISH_PARAM_DATE_FORMAT_NO_YEAR"), 'ADDITIONAL_SETTINGS', true),
		"DATE_TIME_FORMAT" => CComponentUtil::GetDateTimeFormatField(GetMessage("INTR_ISH_PARAM_DATE_TIME_FORMAT"), 'ADDITIONAL_SETTINGS'),

		"SHOW_YEAR" => Array(
			"PARENT" => "ADDITIONAL_SETTINGS",
			"NAME" => GetMessage("INTR_ISH_PARAM_SHOW_YEAR"),
			"TYPE" => "LIST",
			"VALUES" => array(
				"Y" => GetMessage("INTR_ISH_PARAM_SHOW_YEAR_VALUE_Y"),
				"M" => GetMessage("INTR_ISH_PARAM_SHOW_YEAR_VALUE_M"),
				"N" => GetMessage("INTR_ISH_PARAM_SHOW_YEAR_VALUE_N")
			),
			"MULTIPLE" => "N",
			"DEFAULT" => "Y"
		),	
		
		"CACHE_TIME"  =>  Array("DEFAULT"=>3600), 
	),
);

if (IsModuleInstalled("video"))
{
	$arComponentParameters["PARAMETERS"]["PATH_TO_VIDEO_CALL"] = array(
			"NAME" => GetMessage("INTR_ISH_PARAM_PATH_TO_VIDEO_CALL"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/video/#USER_ID#/",
			"PARENT" => "ADDITIONAL_SETTINGS",
		); 
}

?>