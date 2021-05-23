<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule('intranet');

$arComponentParameters = array(
	'GROUPS' => array(
	),
	'PARAMETERS' => array(
		'DETAIL_URL' => array(
			'TYPE' => 'STRING',
			'NAME' => GetMessage('INTR_ISV_PARAM_DETAIL_URL'),
			'DEFAULT' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'N',
			'PARENT' => 'BASE',
		),
		'PROFILE_URL' => array(
			'TYPE' => 'STRING',
			'NAME' => GetMessage('INTR_ISV_PARAM_PROFILE_URL'),
			'DEFAULT' => '/company/personal/user/#ID#/',
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'N',
			'PARENT' => 'BASE',
		),
		'PM_URL' => array(
			'TYPE' => 'STRING',
			'NAME' => GetMessage('INTR_ISV_PARAM_PM_URL'),
			'DEFAULT' => '/company/personal/messages/chat/#ID#/',
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'N',
			'PARENT' => 'BASE',
		),
		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_ISV_PARAM_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'Y',
			'DEFAULT' => "",
			'PARENT' => 'BASE',
		),
		'SHOW_LOGIN' => Array(
			"NAME" => GetMessage('INTR_ISV_PARAM_SHOW_LOGIN'),
			"TYPE" => 'CHECKBOX',
			"MULTIPLE" => 'N',
			"VALUE" => 'Y',
			"DEFAULT" => 'Y',
			"PARENT" => 'BASE',
		),
		'USE_USER_LINK' => array(
			'TYPE' => 'CHECKBOX',
			'NAME' => GetMessage('INTR_ISV_PARAM_USE_USER_LINK'),
			'DEFAULT' => 'Y',
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'N',
			'PARENT' => 'BASE',
		),

		'CACHE_TIME' => array('DEFAULT' => 86400*30),
	),
);

if (IsModuleInstalled("video"))
{
	$arComponentParameters["PARAMETERS"]["PATH_TO_VIDEO_CALL"] = array(
			"NAME" => GetMessage("INTR_ISV_PARAM_PATH_TO_VIDEO_CALL"),
			"TYPE" => "STRING",
			"DEFAULT" => "/company/personal/video/#USER_ID#/",
			"PARENT" => "ADDITIONAL_SETTINGS",
		);
}

?>