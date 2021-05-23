<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

CModule::IncludeModule('intranet');

$IBLOCK_ID = COption::GetOptionInt('intranet', 'iblock_structure', false);

$arSections = array(0 => '');
if ($IBLOCK_ID !== false && CModule::IncludeModule('iblock'))
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
		'NUM_USERS' => array(
			'TYPE' => 'STRING',
			'MULTIPLE' => 'N',
			'DEFAULT' => '10',
			'NAME' => GetMessage('INTR_ISIA_PARAM_NUM_USERS'),
			'PARENT' => 'BASE'
		),

		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_ISIA_PARAM_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'Y',
			'DEFAULT' => "",
			'PARENT' => 'BASE',
		),
		"SHOW_LOGIN" => Array(
			"NAME" => GetMessage("INTR_ISIA_PARAM_SHOW_LOGIN"),
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"VALUE" => "Y",
			"DEFAULT" =>"Y",
			"PARENT" => "BASE",
		),
		"DEPARTMENT" => array(
			"NAME" => GetMessage('INTR_PREDEF_DEPARTMENT'),
			"TYPE" => "LIST",
			'VALUES' => $arSections,
			"DEFAULT" => '',
		),
		'PM_URL' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/personal/messages/chat/#USER_ID#/',
			'NAME' => GetMessage('INTR_ISIA_PARAM_PM_URL'),
			'PARENT' => 'BASE',
		),
		'PATH_TO_CONPANY_DEPARTMENT' => array(
			'TYPE' => 'STRING',
			'DEFAULT' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
			'NAME' => GetMessage('INTR_ISIA_PARAM_PATH_TO_CONPANY_DEPARTMENT'),
			'PARENT' => 'BASE',
		),

		"DATE_FORMAT" => CComponentUtil::GetDateFormatField(GetMessage("INTR_ISIA_PARAM_DATE_FORMAT"), 'ADDITIONAL_SETTINGS'),
		"DATE_TIME_FORMAT" => CComponentUtil::GetDateTimeFormatField(GetMessage("INTR_ISIA_PARAM_DATE_TIME_FORMAT"), 'ADDITIONAL_SETTINGS'),

		'SHOW_YEAR' => array(
			'TYPE' => 'LIST',
			'MULTIPLE' => 'N',
			'DEFAULT' => 'Y',
			'VALUES' => array(
				'Y' => GetMessage('INTR_ISIA_PARAM_SHOW_YEAR_VALUE_Y'),
				'M' => GetMessage('INTR_ISIA_PARAM_SHOW_YEAR_VALUE_M'),
				'N' => GetMessage('INTR_ISIA_PARAM_SHOW_YEAR_VALUE_N')
			),
			'NAME' => GetMessage('INTR_ISIA_PARAM_SHOW_YEAR'),
		),
		'AJAX_MODE' => array(),
		'CACHE_TIME' => array('DEFAULT' => 3600),
	),
);

?>