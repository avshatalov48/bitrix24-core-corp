<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("iblock"))
	return;

$arUserFieldNames = array('LOGIN','NAME','SECOND_NAME','LAST_NAME','EMAIL','PERSONAL_PROFESSION','PERSONAL_WWW','PERSONAL_BIRTHDAY','PERSONAL_ICQ','PERSONAL_GENDER','PERSONAL_PHOTO','PERSONAL_PHONE','PERSONAL_FAX','PERSONAL_MOBILE','PERSONAL_PAGER','PERSONAL_STREET','PERSONAL_CITY','PERSONAL_STATE','PERSONAL_ZIP','PERSONAL_COUNTRY','WORK_POSITION','WORK_PHONE');

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
		$userProp[$val["FIELD_NAME"]] = (strLen($val["EDIT_FORM_LABEL"]) > 0 ? $val["EDIT_FORM_LABEL"] : $val["FIELD_NAME"]);
	}
}

$userPropValue = $userProp;
$arExcludedProperties = array('LOGIN', 'PASSWORD', 'EMAIL', 'UF_STATE_FIRST', 'UF_STATE_LAST', 'UF_1C');
foreach ($arExcludedProperties as $prop) unset($userPropValue[$prop]);
$userPropValue = array_keys($userPropValue);

$arIBlockType = array(
	"-" => '',
);

$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
while ($arr=$rsIBlockType->Fetch())
{
	if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
	{
		$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
	}
}

if ($arCurrentValues['IBLOCK_TYPE'] && $arCurrentValues['IBLOCK_TYPE'] != '-')
{
	$rsIBlock = CIblock::GetList(array('SORT' => 'ASC'), array('TYPE' => $arCurrentValues['IBLOCK_TYPE']));
	while ($arIBlock = $rsIBlock->Fetch())
	{
		$arIBLockList[$arIBlock['ID']] = $arIBlock['NAME'];
	}
}

$arUGroupsEx = Array();
$dbUGroups = CGroup::GetList($by = "c_sort", $order = "asc");
while($arUGroups = $dbUGroups -> Fetch())
{
	$arUGroupsEx[$arUGroups["ID"]] = $arUGroups["NAME"];
}

$bLDAP = CModule::IncludeModule('ldap');
if ($bLDAP)
{
	$dbRes = CLDAPServer::GetList();
	$arLDAPServers = array(
		'' => GetMessage('CP_BCI1_LDAP_SERVER_CHOOSE'),
	);
	while ($arRes = $dbRes->Fetch())
	{
		$arLDAPServers[$arRes['ID']] = $arRes['NAME'];
	}
}

$arSites = array();
$siteDefault = 's1';
$dbRes = CSite::GetList($by = 'sort', $order = 'asc', array('active' => 'Y'));
while ($arSite = $dbRes->Fetch())
{
	$arSites[$arSite['ID']] = '['.$arSite['ID'].'] '.$arSite['NAME'];
	if ($arSite['DEF'] == 'Y') $siteDefault = $arSite['ID'];
}

$arComponentParameters = array(
	"GROUPS" => array(
		"EMAIL" => array(
			"NAME" => GetMessage("CP_BCI1_EMAIL"),
		),
/*
		"XML" => array(
			"NAME" => GetMessage("CP_BCI1_XML"),
		),
*/
	),
	"PARAMETERS" => array(
		"IBLOCK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCI1_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => 'Y',
		),

		'DEPARTMENTS_IBLOCK_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCI1_DEPARTMENTS_IBLOCK_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $arIBLockList,
			'ADDITIONAL_VALUES' => "Y",
		),

		'ABSENCE_IBLOCK_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCI1_ABSENCE_IBLOCK_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $arIBLockList,
			'ADDITIONAL_VALUES' => "Y",
		),
		'ABSENCE_TYPE_PROP_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCI1_ABSENCE_TYPE_PROP_ID'),
			'TYPE' => 'LIST',
			'VALUES' => array(),
			'ADDITIONAL_VALUES' => "Y",
		),

		'STATE_HISTORY_IBLOCK_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCI1_STATE_HISTORY_IBLOCK_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $arIBLockList,
			'ADDITIONAL_VALUES' => "Y",
		),

		'SITE_ID' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCI1_SITE_ID'),
			'TYPE' => 'LIST',
			'VALUES' => $arSites,
			'DEFAULT' => $siteDefault,
		),
/*
		'STRUCTURE_CHECK' => array(
			'PARENT' => 'BASE',
			'NAME' => GetMessage('CP_BCI1_STRUCTURE_CHECK'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y'
		),

		"INTERVAL" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCI1_INTERVAL"),
			"TYPE" => "STRING",
			"DEFAULT" => 30,
		),
*/
		"GROUP_PERMISSIONS" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BCI1_GROUP_PERMISSIONS"),
			"TYPE" => "LIST",
			"VALUES" => $arUGroupsEx,
			"DEFAULT" => array(1),
			"MULTIPLE" => "Y",
		),
/*
		"FILE_SIZE_LIMIT" => array(
			"PARENT" => "ADDITIONAL",
			"NAME" => GetMessage("CP_BCI1_FILE_SIZE_LIMIT"),
			"TYPE" => "STRING",
			"DEFAULT" => 200*1024,
		),
		"USE_ZIP" => array(
			"PARENT" => "ADDITIONAL",
			"NAME" => GetMessage("CP_BCI1_USE_ZIP"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
*/
		'DEFAULT_EMAIL' => array(
			'PARENT' => 'ADDITIONAL',
			'NAME' => GetMessage('CP_BCI1_DEFAULT_EMAIL'),
			'TYPE' => 'STRING',
			'DEFAULT' => COption::GetOptionString('main', 'email_from', "admin@".$SERVER_NAME),
		),

		'UNIQUE_EMAIL' => array(
			'PARENT' => 'ADDITIONAL',
			'NAME' => GetMessage('CP_BCI1_UNIQUE_EMAIL'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
		),

		'LOGIN_TEMPLATE' => array(
			'PARENT' => 'ADDITIONAL',
			'NAME' => GetMessage('CP_BCI1_LOGIN_TEMPLATE'),
			'TYPE' => 'STRING',
			'DEFAULT' => 'user_#',
		),

		'EMAIL_NOTIFY' => array(
			'PARENT' => 'EMAIL',
			'NAME' => GetMessage('CP_BCI1_EMAIL_NOTIFY'),
			'TYPE' => 'LIST',
			'VALUES' => array(
				'N' => GetMessage('CP_BCI1_EMAIL_NOTIFY_N'),
				'E' => GetMessage('CP_BCI1_EMAIL_NOTIFY_E'),
				'Y' => GetMessage('CP_BCI1_EMAIL_NOTIFY_Y'),
			),
			'DEFAULT' => 'E',
		),

		'EMAIL_NOTIFY_IMMEDIATELY' => array(
			'PARENT' => 'EMAIL',
			'NAME' => GetMessage('CP_BCI1_EMAIL_NOTIFY_IMMEDIATELY'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'N',
		),

		'UPDATE_PROPERTIES' => array(
			'PARENT' => 'ADDITIONAL',
			'NAME' => GetMessage('CP_BCI1_UPDATE_PROPERTIES'),
			'TYPE' => 'LIST',
			'MULTIPLE' => 'Y',
			'VALUES' => $userProp,
			'DEFAULT' => $userPropValue,
		),
/*
		'EMAIL_PROPERTY_XML_ID' => array(
			"PARENT" => "XML",
			"NAME" => GetMessage("CP_BCI1_EMAIL_PROPERTY_XML_ID"),
			"TYPE" => "STRING",
		),
		
		'LOGIN_PROPERTY_XML_ID' => array(
			"PARENT" => "XML",
			"NAME" => GetMessage("CP_BCI1_LOGIN_PROPERTY_XML_ID"),
			"TYPE" => "STRING",
		),

		'PASSWORD_PROPERTY_XML_ID' => array(
			"PARENT" => "XML",
			"NAME" => GetMessage("CP_BCI1_PASSWORD_PROPERTY_XML_ID"),
			"TYPE" => "STRING",
		),
*/
	),
);

if ($bLDAP)
{
	$arComponentParameters['GROUPS']['LDAP'] = array('NAME' => 'LDAP');
	$arComponentParameters['PARAMETERS']['LDAP_ID_PROPERTY_XML_ID'] = array(
		'PARENT' => 'LDAP',
		'NAME' => GetMessage('CP_BCI1_LDAP_AD_ID_PROPERTY_XML_ID'),
		'TYPE' => 'STRING',
		'DEFAULT' => '',
	);
	$arComponentParameters['PARAMETERS']['LDAP_SERVER'] = array(
		'PARENT' => 'LDAP',
		'NAME' => GetMessage('CP_BCI1_LDAP_SERVER'),
		'TYPE' => 'LIST',
		'MULTIPLE' => 'N',
		'VALUES' => $arLDAPServers,
	);

}

?>
