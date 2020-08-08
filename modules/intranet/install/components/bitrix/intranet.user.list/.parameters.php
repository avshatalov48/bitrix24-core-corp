<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

global $USER_FIELD_MANAGER;

CModule::IncludeModule('intranet');

$userFieldsNameList = [];

$userFieldsList = [
	'PERSONAL_PHOTO',
	'FULL_NAME',
	'NAME',
	'SECOND_NAME',
	'LAST_NAME',
	'EMAIL',
	'DATE_REGISTER',
	'PERSONAL_PROFESSION',
	'PERSONAL_WWW',
	'PERSONAL_BIRTHDAY',
	'PERSONAL_ICQ',
	'PERSONAL_GENDER',
	'PERSONAL_PHONE',
	'PERSONAL_FAX',
	'PERSONAL_MOBILE',
	'PERSONAL_PAGER',
	'PERSONAL_STREET',
	'PERSONAL_MAILBOX',
	'PERSONAL_CITY',
	'PERSONAL_STATE',
	'PERSONAL_ZIP',
	'PERSONAL_COUNTRY',
	'PERSONAL_NOTES',
	'WORK_POSITION',
	'WORK_COMPANY',
	'WORK_PHONE',
	'WORK_FAX',
	'ADMIN_NOTES',
	'XML_ID',
	'TAGS'
];

$userProp = [];

foreach ($userFieldsList as $name)
{
	$userFieldsNameList[$name] = Loc::getMessage('INTRANET_USER_LIST_PARAMS_FIELD_'.$name);
}

$userPropertiesList = $USER_FIELD_MANAGER->getUserFields('USER', 0, LANGUAGE_ID);
if (!empty($userPropertiesList))
{
	foreach ($userPropertiesList as $key => $val)
	{
		$userFieldsNameList[$val["FIELD_NAME"]] = '* '.($val["EDIT_FORM_LABEL"] <> '' ? $val["EDIT_FORM_LABEL"] : $val["FIELD_NAME"]);
	}
}

$userPropertyListDefault = \Bitrix\Intranet\Component\UserList::getUserPropertyListDefault();

$arComponentParameters = array(
	'PARAMETERS' => array(
		'PATH_TO_DEPARTMENT' => array(
			'TYPE' => 'STRING',
			'NAME' => Loc::getMessage('INTRANET_USER_LIST_PARAMS_PATH_TO_DEPARTMENT'),
			'DEFAULT' => '/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#',
			'PARENT' => 'BASE'
		),
		'PATH_TO_USER' => array(
			"TYPE" => "STRING",
			"NAME" => Loc::getMessage('INTRANET_USER_LIST_PARAMS_PATH_TO_USER'),
			"DEFAULT" => '/company/personal/user/#user_id#/',
			'PARENT' => 'BASE'
		),
		'USER_PROPERTY_LIST' => array(
			"TYPE" => "LIST",
			"NAME" => Loc::getMessage('INTRANET_USER_LIST_PARAMS_USER_PROPERTY_LIST'),
			"VALUES" => $userFieldsNameList,
			"MULTIPLE" => "Y",
			'DEFAULT' => $userPropertyListDefault,
			'PARENT' => 'BASE'
		),
	),
);

?>