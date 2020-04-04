<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arUserFieldNames = array('PERSONAL_PHOTO','ID','LOGIN','NAME','SECOND_NAME','LAST_NAME','EMAIL','DATE_REGISTER','PERSONAL_PROFESSION','PERSONAL_WWW','PERSONAL_BIRTHDAY','PERSONAL_ICQ','PERSONAL_GENDER','PERSONAL_PHONE','PERSONAL_FAX','PERSONAL_MOBILE','PERSONAL_PAGER','PERSONAL_STREET','PERSONAL_MAILBOX','PERSONAL_CITY','PERSONAL_STATE','PERSONAL_ZIP','PERSONAL_COUNTRY','WORK_PHONE', 'PERSONAL_NOTES','ADMIN_NOTES','XML_ID');

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

$arTemplateParameters = array(
	"SHOW_FROM_ROOT" => array(
		"NAME" => GetMessage('INTR_IS_TPL_PARAM_SHOW_FROM_ROOT'),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => 'N',
		'REFRESH' => 'Y',
	),

	"MAX_DEPTH" => array(
		"NAME" => GetMessage('INTR_IS_TPL_PARAM_MAX_DEPTH'),
		"TYPE" => "STRING",
		"DEFAULT" => '2',
	),
	
	"MAX_DEPTH_FIRST" => array(
		"NAME" => GetMessage('INTR_IS_TPL_PARAM_MAX_DEPTH_FIRST'),
		"TYPE" => "STRING",
		"DEFAULT" => '0',
	),
	
	"COLUMNS" => array(
		"NAME" => GetMessage('INTR_IS_TPL_PARAM_COLUMNS'),
		"TYPE" => "STRING",
		"DEFAULT" => '2',
	),

	"COLUMNS_FIRST" => array(
		"NAME" => GetMessage('INTR_IS_TPL_PARAM_COLUMNS_FIRST'),
		"TYPE" => "STRING",
		"DEFAULT" => '2',
	),

	"SHOW_SECTION_INFO" => array(
		"NAME" => GetMessage('INTR_IS_TPL_PARAM_SHOW_SECTION_INFO'),
		"TYPE" => "CHECKBOX",
		"DEFAULT" => 'Y',
	),

	"USER_PROPERTY" => array(
		"NAME" => GetMessage('INTR_IS_TPL_PARAM_USER_PROPERTY'),
		"TYPE" => "LIST",
		"VALUES" => $userProp,
		"MULTIPLE" => "Y",
		"DEFAULT" => array('EMAIL', 'PERSONAL_ICQ', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'UF_PHONE_INNER'),
	),
); 

if ($arCurrentValues['SHOW_FROM_ROOT'] && $arCurrentValues['SHOW_FROM_ROOT'] == 'Y')
	unset($arTemplateParameters['MAX_DEPTH']);
?>