<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arUserFieldNames = array('PERSONAL_PHOTO', 'FULL_NAME', 'ID','LOGIN','NAME','SECOND_NAME','LAST_NAME','EMAIL','DATE_REGISTER','PERSONAL_PROFESSION','PERSONAL_WWW','PERSONAL_BIRTHDAY','PERSONAL_ICQ','PERSONAL_GENDER','PERSONAL_PHONE','PERSONAL_FAX','PERSONAL_MOBILE','PERSONAL_PAGER','PERSONAL_STREET','PERSONAL_MAILBOX','PERSONAL_CITY','PERSONAL_STATE','PERSONAL_ZIP','PERSONAL_COUNTRY','WORK_PHONE','PERSONAL_NOTES','ADMIN_NOTES','XML_ID');

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
	'STRUCTURE_PAGE' => array(
		'TYPE' => 'STRING',
		'MULTIPLE' => 'N',
		'DEFAULT' => '/company/structure.php',
		'NAME' => GetMessage('INTR_ISBN_PARAM_STRUCTURE_PAGE'),
		'PARENT' => 'BASE'
	),

	'PM_URL' => array(
		'TYPE' => 'STRING',
		'DEFAULT' => '/messages/chat/#USER_ID#/',
		'NAME' => GetMessage('INTR_ISBN_PARAM_PM_URL'),
		'PARENT' => 'BASE',
	),
	
	'STRUCTURE_FILTER' => array(
		'TYPE' => 'STRING',
		'MULTIPLE' => 'N',
		'DEFAULT' => 'structure',
		'NAME' => GetMessage('INTR_ISBN_PARAM_STRUCTURE_FILTER'),
		'PARENT' => 'BASE'
	),
	
	"USER_PROPERTY" => array(
		"NAME" => GetMessage('INTR_ISBN_PARAM_USER_PROPERTY'),
		"TYPE" => "LIST",
		"VALUES" => $userProp,
		"MULTIPLE" => "Y",
		"DEFAULT" => array('UF_DEPARTMENT', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE'),
	),
); 
?>