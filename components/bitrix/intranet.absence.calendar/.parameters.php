<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CModule::IncludeModule('intranet');

/*
$arIBlockType = array();
$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
while ($arr=$rsIBlockType->Fetch())
{
	if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
		$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
}

$arIBlock=array();
$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE"=>"Y"));
while($arr=$rsIBlock->Fetch())
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];
*/

$arComponentParameters = array(
	'GROUPS' => array(
		'FILTER' => array(
			'NAME' => GetMessage('INTR_ABSC_GROUP_FILTER'),
		),
	),
	
	'PARAMETERS' => array(
/*		'CALENDAR_IBLOCK_TYPE' => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTR_ABSC_PARAM_CALENDAR_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
		),
		'CALENDAR_IBLOCK_ID' => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("INTR_ABSC_PARAM_CALENDAR_IBLOCK_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlock,
		),

		'FILTER_SECTION_CURONLY' => array(
			'PARENT' => 'FILTER',
			'NAME' => GetMessage('INTR_ABSC_PARAM_FILTER_SECTION_CURONLY'),
			'TYPE' => 'LIST',
			'VALUES' => array('Y' => GetMessage('INTR_ABSC_PARAM_FILTER_SECTION_CURONLY_VALUE_Y'), 'N' => GetMessage('INTR_ABSC_PARAM_FILTER_SECTION_CURONLY_VALUE_N')),
			'DEFAULT' => 'Y',
		),*/
		
		'NAME_TEMPLATE' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_ISL_PARAM_NAME_TEMPLATE'),
			'VALUES' => CComponentUtil::GetDefaultNameTemplates(),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'Y',
			'DEFAULT' => "#NOBR##LAST_NAME# #NAME##/NOBR#",
			'PARENT' => 'BASE',
		),
		
		
		'VIEW_START' => array(
			'PARENT' => 'ADDITIONAL',
			'NAME' => GetMessage('INTR_ABSC_PARAM_VIEW_START'),
			'TYPE' => 'LIST',
			'VALUES' => array('day' => GetMessage('INTR_ABSC_PARAM_VIEW_day'), 'week' => GetMessage('INTR_ABSC_PARAM_VIEW_week'), 'month' => GetMessage('INTR_ABSC_PARAM_VIEW_month')),
			'DEFAULT' => 'month',
			'PARENT' => 'BASE'
		),

		'FILTER_CONTROLS' => array(
			'PARENT' => 'ADDITIONAL',
			'NAME' => GetMessage('INTR_ABSC_PARAM_FILTER_CONTROLS'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'Y',
			'VALUES' => array(
				'DATEPICKER' => GetMessage('INTR_ABSC_PARAM_FILTER_CONTROLS_DATEPICKER'),
				'TYPEFILTER' => GetMessage('INTR_ABSC_PARAM_FILTER_CONTROLS_TYPEFILTER'),
				'SHOW_ALL' => GetMessage('INTR_ABSC_PARAM_FILTER_CONTROLS_SHOW_ALL'),
				'DEPARTMENT' => GetMessage('INTR_ABSC_PARAM_FILTER_CONTROLS_DEPARTMENT'),
			),
			'DEFAULT' => array(
				'DATEPICKER', 'TYPEFILTER', 'DEPARTMENT'
			),
			'PARENT' => 'BASE'
		),
		
		'FIRST_DAY' => array(
			'TYPE' => 'LIST',
			'NAME' => GetMessage('INTR_ISL_PARAM_FIRST_DAY'),
			'VALUES' => array(
				'0' => GetMessage('INTR_ISL_PARAM_FIRST_DAY_0'),
				'1' => GetMessage('INTR_ISL_PARAM_FIRST_DAY_1'),
				'2' => GetMessage('INTR_ISL_PARAM_FIRST_DAY_2'),
				'3' => GetMessage('INTR_ISL_PARAM_FIRST_DAY_3'),
				'4' => GetMessage('INTR_ISL_PARAM_FIRST_DAY_4'),
				'5' => GetMessage('INTR_ISL_PARAM_FIRST_DAY_5'),
				'6' => GetMessage('INTR_ISL_PARAM_FIRST_DAY_6'),
			),
			'MULTIPLE' => 'N',
			'ADDITIONAL_VALUES' => 'N',
			'DEFAULT' => "1",
			'PARENT' => 'BASE',
		),
		
		'DAY_START' => array(
			'PARENT' => 'ADDITIONAL',
			'NAME' => GetMessage('INTR_ABSC_PARAM_DAY_START'),
			'TYPE' => 'STRING',
			'DEFAULT' => '9',
			'PARENT' => 'BASE'
		),

		'DAY_FINISH' => array(
			'PARENT' => 'ADDITIONAL',
			'NAME' => GetMessage('INTR_ABSC_PARAM_DAY_FINISH'),
			'TYPE' => 'STRING',
			'DEFAULT' => '18',
			'PARENT' => 'BASE'
		),
		
		'DAY_SHOW_NONWORK' => array(
			'PARENT' => 'ADDITIONAL',
			'NAME' => GetMessage('INTR_ABSC_PARAM_DAY_SHOW_NONWORK'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
			'PARENT' => 'BASE'
		),
		
		"DATE_FORMAT" => CComponentUtil::GetDateFormatField(GetMessage("INTR_ABSC_PARAM_DATE_FORMAT"), 'BASE'),
		"DATETIME_FORMAT" => CComponentUtil::GetDateTimeFormatField(GetMessage("INTR_ABSC_PARAM_DATETIME_FORMAT"), 'BASE'),
	)
);

?>