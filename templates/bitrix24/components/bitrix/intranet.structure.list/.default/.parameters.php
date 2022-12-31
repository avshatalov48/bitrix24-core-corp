<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arUserFieldNames = array('ID','LOGIN','FULL_NAME', 'NAME','SECOND_NAME','LAST_NAME','EMAIL','DATE_REGISTER','PERSONAL_PROFESSION','PERSONAL_WWW','PERSONAL_BIRTHDAY','PERSONAL_ICQ','PERSONAL_GENDER','PERSONAL_PHOTO','PERSONAL_PHONE','PERSONAL_FAX','PERSONAL_MOBILE','PERSONAL_PAGER','PERSONAL_STREET','PERSONAL_MAILBOX','PERSONAL_CITY','PERSONAL_STATE','PERSONAL_ZIP','PERSONAL_COUNTRY','PERSONAL_NOTES','ADMIN_NOTES', 'WORK_POSITION', 'WORK_PHONE', 'WORK_FAX', 'XML_ID');

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
		$userProp[$val["FIELD_NAME"]] = ($val["EDIT_FORM_LABEL"] <> '' ? $val["EDIT_FORM_LABEL"] : $val["FIELD_NAME"]);
	}
}

$arTemplateParameters = array(
	"USER_PROPERTY"=>array(
		"NAME" => GetMessage('INTR_ISL_TPL_PARAM_USER_PROPERTY'),
		"TYPE" => "LIST",
		"VALUES" => $userProp,
		"MULTIPLE" => "Y",
		"DEFAULT" => array('FULL_NAME', 'PERSONAL_PHONE', 'EMAIL', 'WORK_POSITION', 'UF_DEPARTMENT'),
	),
	"USER_PROPERTY_EXCEL"=>array(
		"NAME" => GetMessage('INTR_ISL_TPL_PARAM_USER_PROPERTY_EXCEL'),
		"TYPE" => "LIST",
		"VALUES" => $userProp,
		"MULTIPLE" => "Y",
		"DEFAULT" => array('FULL_NAME', 'PERSONAL_PHONE', 'EMAIL', 'WORK_POSITION', 'UF_DEPARTMENT'),
	),
); 

?>