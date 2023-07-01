<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

global $DB;

if (!CModule::IncludeModule('catalog'))
{
	$errMsg[] = GetMessage('CRM_CATALOG_NOT_INSTALLED');
	$bError = true;
	return;
}

if (!CModule::IncludeModule('sale'))
{
	$errMsg[] = GetMessage('CRM_SALE_NOT_INSTALLED');
	$bError = true;
	return;
}

$languageId = '';
if (IsModuleInstalled('bitrix24')
	&& CModule::IncludeModule('bitrix24')
	&& method_exists('CBitrix24', 'getLicensePrefix')
)
{
	$languageId = CBitrix24::getLicensePrefix();
}

if ($languageId == '')
{
	$siteIterator = \Bitrix\Main\SiteTable::getList(array(
		'select' => array('LID', 'LANGUAGE_ID'),
		'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
	));
	if ($site = $siteIterator->fetch())
	{
		$languageId = (string)$site['LANGUAGE_ID'];
	}

	unset($site, $siteIterator);
}
if ($languageId == '')
{
	$languageId = 'en';
}

switch ($languageId)
{
	case 'ru':
	case 'ua':
	case 'de':
	case 'en':
	case 'la':
	case 'br':
	case 'fr':
		$shopLocalization = $languageId;
		break;
	case 'by':
		$shopLocalization = 'ru';
		break;
	case 'kz':
		$shopLocalization = 'ru';
		break;
	case 'tc':
		$shopLocalization = 'tc';
		break;
	case 'sc':
		$shopLocalization = 'sc';
		break;
	case 'in':
		$shopLocalization = 'en';
		break;
	default:
		$shopLocalization = $languageId;
		break;
}

$currentSiteID = SITE_ID;
if (defined("ADMIN_SECTION"))
{
	$siteIterator = \Bitrix\Main\SiteTable::getList(array(
		'select' => array('LID', 'LANGUAGE_ID'),
		'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y')
	));
	if ($defaultSite = $siteIterator->fetch())
	{
		$currentSiteID = $defaultSite['LID'];
	}
	unset($defaultSite, $siteIterator);
}

$companyPTID = $contactPTID = 0;

$runtimeFields = [];

$dbPerson = \Bitrix\Sale\Internals\BusinessValuePersonDomainTable::getList([
	'select' => [
		'DOMAIN',
		'PT_ID' => 'PERSON_TYPE_REFERENCE.ID',
	],
	'filter' => [
		'=PERSON_TYPE_REFERENCE.ENTITY_REGISTRY_TYPE' => \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER,
		'=PERSON_TYPE_REFERENCE.LID' => $currentSiteID,
	]
]);

while ($arPerson = $dbPerson->fetch())
{
	if ($arPerson["DOMAIN"] == 'E')
	{
		$companyPTID = $arPerson["PT_ID"];
	}
	elseif ($arPerson["DOMAIN"] == 'I')
	{
		$contactPTID = $arPerson["PT_ID"];
	}
}

if ($contactPTID === 0)
{
	$res = \Bitrix\Sale\Internals\PersonTypeTable::add(array(
		"LID" => $currentSiteID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage('CRM_SALE_PERSON_TYPE_I'),
		"SORT" => "100",
		"CODE" => "CRM_CONTACT",
		"ENTITY_REGISTRY_TYPE" => \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER,
		"ACTIVE" => "Y"
	));

	if ($res->isSuccess())
	{
		$contactPTID = $res->getId();

		\Bitrix\Sale\Internals\PersonTypeSiteTable::add([
			'PERSON_TYPE_ID' => $contactPTID,
			'SITE_ID' => $currentSiteID
		]);

		$dbRes = \Bitrix\Sale\Internals\BusinessValuePersonDomainTable::getList([
			'filter' => ['PERSON_TYPE_ID' => $contactPTID, 'DOMAIN' => 'I']
		]);
		if (!$dbRes->fetch())
		{
			\Bitrix\Sale\Internals\BusinessValuePersonDomainTable::add([
				'PERSON_TYPE_ID' => $contactPTID,
				'DOMAIN' => 'I'
			]);
		}
	}
}

if ($companyPTID === 0)
{
	$res = \Bitrix\Sale\Internals\PersonTypeTable::add(array(
		"LID" => $currentSiteID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage('CRM_SALE_PERSON_TYPE_E'),
		"SORT" => "110",
		"CODE" => "CRM_COMPANY",
		"ENTITY_REGISTRY_TYPE" => \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER,
		"ACTIVE" => "Y"
	));

	if ($res->isSuccess())
	{
		$companyPTID = $res->getId();

		\Bitrix\Sale\Internals\PersonTypeSiteTable::add([
			'PERSON_TYPE_ID' => $companyPTID,
			'SITE_ID' => $currentSiteID
		]);

		$dbRes = \Bitrix\Sale\Internals\BusinessValuePersonDomainTable::getList([
			'filter' => ['PERSON_TYPE_ID' => $companyPTID, 'DOMAIN' => 'E']
		]);
		if (!$dbRes->fetch())
		{
			\Bitrix\Sale\Internals\BusinessValuePersonDomainTable::add([
				'PERSON_TYPE_ID' => $companyPTID,
				'DOMAIN' => 'E'
			]);
		}
	}
}

//Order Prop Group
$arPropGroup = array();

$dbSaleOrderPropsGroup = \Bitrix\Sale\Internals\OrderPropsGroupTable::getList([
	'filter' => [
		"PERSON_TYPE_ID" => $contactPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ1")
	],
	'select' => ["ID"]
]);
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->fetch())
{
	$arPropGroup["user_fiz"] = $arSaleOrderPropsGroup["ID"];
}
else
{
	$r = \Bitrix\Sale\Internals\OrderPropsGroupTable::add([
		"PERSON_TYPE_ID" => $contactPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ1"),
		"SORT" => 100
	]);

	$arPropGroup["user_fiz"] = $r->getId();
}

$dbSaleOrderPropsGroup = \Bitrix\Sale\Internals\OrderPropsGroupTable::getList([
	'filter' => [
		"PERSON_TYPE_ID" => $contactPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ2")
	],
	'select' => ["ID"]
]);
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->fetch())
{
	$arPropGroup["adres_fiz"] = $arSaleOrderPropsGroup["ID"];
}
else
{
	$r = \Bitrix\Sale\Internals\OrderPropsGroupTable::add([
		"PERSON_TYPE_ID" => $contactPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ2"),
		"SORT" => 200
	]);

	$arPropGroup["adres_fiz"] = $r->getId();
}

$dbSaleOrderPropsGroup = \Bitrix\Sale\Internals\OrderPropsGroupTable::getList([
	'filter' => [
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_GROUP_UR1")
	],
	'select' => ["ID"]
]);
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->fetch())
{
	$arPropGroup["user_ur"] = $arSaleOrderPropsGroup["ID"];
}
else
{
	$r = \Bitrix\Sale\Internals\OrderPropsGroupTable::add([
		"PERSON_TYPE_ID" => $contactPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_GROUP_UR1"),
		"SORT" => 300
	]);

	$arPropGroup["user_ur"] = $r->getId();
}

$dbSaleOrderPropsGroup = \Bitrix\Sale\Internals\OrderPropsGroupTable::getList([
	'filter' => [
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_GROUP_UR2")
	],
	'select' => ["ID"]
]);
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->fetch())
{
	$arPropGroup["adres_ur"] = $arSaleOrderPropsGroup["ID"];
}
else
{
	$r = \Bitrix\Sale\Internals\OrderPropsGroupTable::add([
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_GROUP_UR2"),
		"SORT" => 400
	]);

	$arPropGroup["adres_ur"] = $r->getId();
}

$arProps = array();

$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_6_2"),
	"TYPE" => "TEXT",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 100,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["user_fiz"],
	"SIZE1" => 40,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "Y",
	"IS_PAYER" => "Y",
	"IS_PHONE" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "FIO",
	"IS_FILTERED" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => "E-Mail",
	"TYPE" => "TEXT",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 110,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["user_fiz"],
	"SIZE1" => 40,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "Y",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_PHONE" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "EMAIL",
	"IS_FILTERED" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_9"),
	"TYPE" => "TEXT",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 120,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["user_fiz"],
	"SIZE1" => 0,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_PHONE" => "Y",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "PHONE",
	"IS_FILTERED" => "N",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_4"),
	"TYPE" => "TEXT",
	"REQUIED" => "N",
	"ACTIVE" => "N",
	"DEFAULT_VALUE" => "101000",
	"SORT" => 130,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["adres_fiz"],
	"SIZE1" => 8,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_PHONE" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "ZIP",
	"IS_FILTERED" => "N",
	"IS_ZIP" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_21"),
	"TYPE" => "TEXT",
	"REQUIED" => "N",
	"ACTIVE" => "N",
	"DEFAULT_VALUE" => "",
	"SORT" => 145,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["adres_fiz"],
	"SIZE1" => 40,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_PHONE" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "CITY",
	"IS_FILTERED" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_2"),
	"TYPE" => "LOCATION",
	"ACTIVE" => "N",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 140,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "Y",
	"PROPS_GROUP_ID" => $arPropGroup["adres_fiz"],
	"SIZE1" => 40,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_PHONE" => "N",
	"IS_LOCATION4TAX" => "Y",
	"CODE" => "LOCATION",
	"IS_FILTERED" => "N",
	"INPUT_FIELD_LOCATION" => ""
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_5"),
	"TYPE" => "TEXTAREA",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => 150,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "N",
	"PROPS_GROUP_ID" => $arPropGroup["adres_fiz"],
	"SIZE1" => 30,
	"SIZE2" => 3,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_PHONE" => "N",
	"IS_LOCATION4TAX" => "N",
	"CODE" => "ADDRESS",
	"IS_FILTERED" => "N",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $companyPTID,
	"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_2"),
	"TYPE" => "LOCATION",
	"ACTIVE" => "N",
	"REQUIED" => "Y",
	"DEFAULT_VALUE" => "",
	"SORT" => ($shopLocalization == "ua") ? 185 : 290,
	"USER_PROPS" => "Y",
	"IS_LOCATION" => "Y",
	"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
	"SIZE1" => 40,
	"SIZE2" => 0,
	"DESCRIPTION" => "",
	"IS_EMAIL" => "N",
	"IS_PROFILE_NAME" => "N",
	"IS_PAYER" => "N",
	"IS_PHONE" => "N",
	"IS_LOCATION4TAX" => "Y",
	"CODE" => "LOCATION",
	"IS_FILTERED" => "N",
);

if ($shopLocalization == "ru")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_13"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 220,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "INN",
		"IS_FILTERED" => "N",
	);

	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_14"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 230,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "KPP",
		"IS_FILTERED" => "N",
	);
}
elseif ($shopLocalization == "de")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_BLZ"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 220,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "BLZ",
		"IS_FILTERED" => "N",
	);

	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_IBAN"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 230,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "IBAN",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_BIC_SWIFT"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 240,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "BIC_SWIFT",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_UST_IDNR"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 250,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "UST_IDNR",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_STEU"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 260,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "STEU",
		"IS_FILTERED" => "N",
	);
}
elseif (in_array($shopLocalization, array('en', 'la', 'br', 'fr'), true))
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_IBAN"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 230,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "IBAN",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_BIC_SWIFT"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 240,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "BIC_SWIFT",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_SORT_CODE"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 250,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "SORT_CODE",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_CRN"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 260,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_REG_NO",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_TRN"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 270,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "TAX_REG_NO",
		"IS_FILTERED" => "N",
	);

}

if($shopLocalization != "ua")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_8"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 200,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "Y",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_7"),
		"TYPE" => "TEXTAREA",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 210,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["user_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_ADR",
		"IS_FILTERED" => "N",
	);

	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_10"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 240,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "Y",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "CONTACT_PERSON",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => "E-Mail",
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 250,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "Y",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "EMAIL",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_9"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" =>260,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "Y",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "PHONE",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_11"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 270,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 0,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "FAX",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_4"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"ACTIVE" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 280,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 8,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ZIP",
		"IS_FILTERED" => "N",
		"IS_ZIP" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_21"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 285,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "CITY",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_12"),
		"TYPE" => "TEXTAREA",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 300,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 4,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ADDRESS",
		"IS_FILTERED" => "N",
	);
}
elseif($shopLocalization == "ua")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => "E-Mail",
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 110,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "Y",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "EMAIL",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_40"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 130,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "Y",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_NAME",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_47"),
		"TYPE" => "TEXTAREA",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 140,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 40,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_ADR",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_48"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 150,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "EGRPU",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_49"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 160,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "INN",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_46"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 170,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "NDS",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_44"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
		"ACTIVE" => "N",
		"DEFAULT_VALUE" => "",
		"SORT" => 180,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 8,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ZIP",
		"IS_FILTERED" => "N",
		"IS_ZIP" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_43"),
		"TYPE" => "TEXT",
		"ACTIVE" => "N",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => '',
		"SORT" => 190,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "CITY",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_42"),
		"TYPE" => "TEXTAREA",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 200,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 3,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "N",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ADDRESS",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => \Bitrix\Main\Localization\Loc::getMessage("CRM_ORD_PROP_45"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => "",
		"SORT" => 210,
		"USER_PROPS" => "Y",
		"IS_LOCATION" => "N",
		"PROPS_GROUP_ID" => $arPropGroup["adres_ur"],
		"SIZE1" => 30,
		"SIZE2" => 0,
		"DESCRIPTION" => "",
		"IS_EMAIL" => "N",
		"IS_PROFILE_NAME" => "N",
		"IS_PAYER" => "N",
		"IS_PHONE" => "Y",
		"IS_LOCATION4TAX" => "N",
		"CODE" => "PHONE",
		"IS_FILTERED" => "N",
	);

}

foreach($arProps as $prop)
{
	$dbRes = \Bitrix\Crm\Order\Property::getList([
		'select' => ['ID'],
		'filter' => [
			"PERSON_TYPE_ID" => $prop["PERSON_TYPE_ID"],
			"CODE" =>  $prop["CODE"]
		]
	]);

	if (!$dbRes->fetch())
	{
		$prop['NAME'] = $prop['NAME'] ?? ' ';
		$prop = CSaleOrderPropsAdapter::convertOldToNew($prop);
		$prop['ENTITY_REGISTRY_TYPE'] = 'ORDER';
		$id = \Bitrix\Sale\Internals\OrderPropsTable::add($prop);
	}
}


// match order properties to company/contact
$fixedPresetList = \Bitrix\Crm\EntityRequisite::getFixedPresetList();
$currentCountryId = (int)Bitrix\Main\Config\Option::get('crm', 'crm_requisite_preset_country_id', '0');

$currentPresetInfo = [];
$currentMap = [];

foreach ($fixedPresetList as $presetList)
{
	if ($presetList['COUNTRY_ID'] != $currentCountryId)
	{
		continue;
	}

	if (mb_substr($presetList['XML_ID'], -13) === '_LEGALENTITY#' || mb_substr($presetList['XML_ID'], -9) === '_COMPANY#')
	{
		$currentPresetInfo['CRM_COMPANY'] = $presetList;
		$currentMap[$presetList['XML_ID']] = 'CRM_COMPANY';
	}
	elseif (mb_substr($presetList['XML_ID'], -8) === '_PERSON#')
	{
		$currentPresetInfo['CRM_CONTACT'] = $presetList;
		$currentMap[$presetList['XML_ID']] = 'CRM_CONTACT';
	}
}

unset($fixedPresetList, $currentCountryId);

$presetImport = [];

if (!empty($currentPresetInfo))
{
	$strWhere = '';

	foreach ($currentPresetInfo as $info)
	{
		if (!empty($strWhere))
		{
			$strWhere .= ' OR ';
		}

		$strWhere .= "`XML_ID` = '{$info['XML_ID']}'";
	}

	$res = $DB->Query("SELECT `ID`, `NAME`, `XML_ID` FROM `b_crm_preset` WHERE {$strWhere}");
	while ($preset = $res->Fetch())
	{
		$presetImport[$currentMap[$preset['XML_ID']]] = $preset;
	}
}

$matches = [
	// contact binding
	'CRM_CONTACT' => [
		'FIO' => [
			'CRM_ENTITY_TYPE' => 3,
			'CRM_FIELD_TYPE' => 1,
			'CRM_FIELD_CODE' => 'FULL_NAME',
			'SETTINGS' => [],
		],
		'EMAIL' => [
			'CRM_ENTITY_TYPE' => 3,
			'CRM_FIELD_TYPE' => 2,
			'CRM_FIELD_CODE' => 'EMAIL_WORK',
			'SETTINGS' => [],
		],
		'PHONE' => [
			'CRM_ENTITY_TYPE' => 3,
			'CRM_FIELD_TYPE' => 2,
			'CRM_FIELD_CODE' => 'PHONE_WORK',
			'SETTINGS' => [],
		],
	],

	// company binding
	'CRM_COMPANY' => [
		'COMPANY' => [
			'CRM_ENTITY_TYPE' => 4,
			'CRM_FIELD_TYPE' => 1,
			'CRM_FIELD_CODE' => 'TITLE',
			'SETTINGS' => [],
		],
		'EMAIL' => [
			'CRM_ENTITY_TYPE' => 4,
			'CRM_FIELD_TYPE' => 2,
			'CRM_FIELD_CODE' => 'EMAIL_WORK',
			'SETTINGS' => [],
		],
		'PHONE' => [
			'CRM_ENTITY_TYPE' => 4,
			'CRM_FIELD_TYPE' => 2,
			'CRM_FIELD_CODE' => 'PHONE_WORK',
			'SETTINGS' => [],
		],
		'CONTACT_PERSON' => [
			'CRM_ENTITY_TYPE' => 3,
			'CRM_FIELD_TYPE' => 1,
			'CRM_FIELD_CODE' => 'FULL_NAME',
			'SETTINGS' => [],
		],
	],
];

if (!empty($presetImport['CRM_CONTACT']) && !empty($currentPresetInfo['CRM_CONTACT']['SETTINGS']['FIELDS']))
{
	$rqName = $presetImport['CRM_CONTACT']['NAME'];
	$rqPresetId = $presetImport['CRM_CONTACT']['ID'];

	foreach ($currentPresetInfo['CRM_CONTACT']['SETTINGS']['FIELDS'] as $field)
	{
		if ($field['FIELD_NAME'] === 'RQ_ADDR')
		{
			$matches['CRM_CONTACT']['ZIP'] = [
				'CRM_ENTITY_TYPE' => 3,
				'CRM_FIELD_TYPE' => 3,
				'CRM_FIELD_CODE' => 'RQ_ADDR',
				'SETTINGS' => [
					'RQ_NAME' => $rqName,
					'RQ_PRESET_ID' => $rqPresetId,
					'RQ_ADDR_TYPE' => 1,
					'RQ_ADDR_CODE' => 'POSTAL_CODE',
				],
			];
			$matches['CRM_CONTACT']['CITY'] = [
				'CRM_ENTITY_TYPE' => 3,
				'CRM_FIELD_TYPE' => 3,
				'CRM_FIELD_CODE' => 'RQ_ADDR',
				'SETTINGS' => [
					'RQ_NAME' => $rqName,
					'RQ_PRESET_ID' => $rqPresetId,
					'RQ_ADDR_TYPE' => 1,
					'RQ_ADDR_CODE' => 'CITY',
				],
			];
			$matches['CRM_CONTACT']['LOCATION'] = [
				'CRM_ENTITY_TYPE' => 3,
				'CRM_FIELD_TYPE' => 3,
				'CRM_FIELD_CODE' => 'RQ_ADDR',
				'SETTINGS' => [
					'RQ_NAME' => $rqName,
					'RQ_PRESET_ID' => $rqPresetId,
					'RQ_ADDR_TYPE' => 1,
					'RQ_ADDR_CODE' => 'LOCATION',
				],
			];
			$matches['CRM_CONTACT']['ADDRESS'] = [
				'CRM_ENTITY_TYPE' => 3,
				'CRM_FIELD_TYPE' => 3,
				'CRM_FIELD_CODE' => 'RQ_ADDR',
				'SETTINGS' => [
					'RQ_NAME' => $rqName,
					'RQ_PRESET_ID' => $rqPresetId,
					'RQ_ADDR_TYPE' => 1,
					'RQ_ADDR_CODE' => 'ADDRESS_1',
				],
			];
		}
	}
}

if (!empty($presetImport['CRM_COMPANY']) && !empty($currentPresetInfo['CRM_COMPANY']['SETTINGS']['FIELDS']))
{
	$rqName = $presetImport['CRM_COMPANY']['NAME'];
	$rqPresetId = $presetImport['CRM_COMPANY']['ID'];

	foreach ($currentPresetInfo['CRM_COMPANY']['SETTINGS']['FIELDS'] as $field)
	{
		if ($field['FIELD_NAME'] === 'RQ_ADDR')
		{
			$matches['CRM_COMPANY']['LOCATION'] = [
				'CRM_ENTITY_TYPE' => 4,
				'CRM_FIELD_TYPE' => 3,
				'CRM_FIELD_CODE' => 'RQ_ADDR',
				'SETTINGS' => [
					'RQ_NAME' => $rqName,
					'RQ_PRESET_ID' => $rqPresetId,
					'RQ_ADDR_TYPE' => 6,
					'RQ_ADDR_CODE' => 'LOCATION',
				],
			];
			$matches['CRM_COMPANY']['ZIP'] = [
				'CRM_ENTITY_TYPE' => 4,
				'CRM_FIELD_TYPE' => 3,
				'CRM_FIELD_CODE' => 'RQ_ADDR',
				'SETTINGS' => [
					'RQ_NAME' => $rqName,
					'RQ_PRESET_ID' => $rqPresetId,
					'RQ_ADDR_TYPE' => 6,
					'RQ_ADDR_CODE' => 'POSTAL_CODE',
				],
			];
			$matches['CRM_COMPANY']['COMPANY_ADR'] = [
				'CRM_ENTITY_TYPE' => 4,
				'CRM_FIELD_TYPE' => 3,
				'CRM_FIELD_CODE' => 'RQ_ADDR',
				'SETTINGS' => [
					'RQ_NAME' => $rqName,
					'RQ_PRESET_ID' => $rqPresetId,
					'RQ_ADDR_TYPE' => 6,
					'RQ_ADDR_CODE' => 'ADDRESS_1',
				],
			];
			$matches['CRM_COMPANY']['ADDRESS'] = [
				'CRM_ENTITY_TYPE' => 4,
				'CRM_FIELD_TYPE' => 3,
				'CRM_FIELD_CODE' => 'RQ_ADDR',
				'SETTINGS' => [
					'RQ_NAME' => $rqName,
					'RQ_PRESET_ID' => $rqPresetId,
					'RQ_ADDR_TYPE' => 1,
					'RQ_ADDR_CODE' => 'ADDRESS_1',
				],
			];
		}
		elseif ($field['FIELD_NAME'] === 'RQ_INN')
		{
			$matches['CRM_COMPANY']['INN'] = [
				'CRM_ENTITY_TYPE' => 4,
				'CRM_FIELD_TYPE' => 3,
				'CRM_FIELD_CODE' => 'RQ_INN',
				'SETTINGS' => [
					'RQ_NAME' => $rqName,
					'RQ_PRESET_ID' => $rqPresetId,
				],
			];
		}
	}
}

$businessValueMap = [
	Bitrix\Sale\BusinessValue::INDIVIDUAL_DOMAIN => "CRM_CONTACT",
	Bitrix\Sale\BusinessValue::ENTITY_DOMAIN => "CRM_COMPANY",
];

$res = $DB->Query("SELECT * FROM `b_sale_person_type` WHERE ENTITY_REGISTRY_TYPE = 'ORDER'");
while ($personType = $res->Fetch())
{
	$domain = \Bitrix\Sale\Internals\BusinessValuePersonDomainTable::getList([
		'filter' => array("PERSON_TYPE_ID" => $personType['ID'])
	])->fetch()['DOMAIN'];

	$personTypeMatches = $matches[$businessValueMap[$domain]];

	if ($personTypeMatches)
	{
		$existingProps = [];
		$propRes = $DB->Query("SELECT `ID`, `CODE` FROM `b_sale_order_props` WHERE `PERSON_TYPE_ID` = ".$personType['ID']." AND ENTITY_REGISTRY_TYPE = 'ORDER'");
		while ($property = $propRes->Fetch())
		{
			if (!empty($property['CODE']))
			{
				$existingProps[$property['ID']] = $property;
			}
		}

		if (!empty($existingProps))
		{
			$matchedProps = [];
			$matchedPropRes = $DB->Query("SELECT `SALE_PROP_ID` FROM `b_crm_order_props_match` WHERE `SALE_PROP_ID` IN (".join(', ', array_keys($existingProps)).")");
			while ($property = $matchedPropRes->Fetch())
			{
				$matchedProps[$property['SALE_PROP_ID']] = true;
			}

			foreach ($existingProps as $existingProp)
			{
				if (empty($matchedProps[$existingProp['ID']]) && !empty($personTypeMatches[$existingProp['CODE']]))
				{
					$propMatch = $personTypeMatches[$existingProp['CODE']];

					$DB->Query(
						"INSERT INTO `b_crm_order_props_match`
							(`SALE_PROP_ID`, `CRM_ENTITY_TYPE`, `CRM_FIELD_TYPE`, `CRM_FIELD_CODE`, `SETTINGS`)
							VALUES ('{$existingProp['ID']}', '{$propMatch['CRM_ENTITY_TYPE']}', '{$propMatch['CRM_FIELD_TYPE']}',
							'{$propMatch['CRM_FIELD_CODE']}', '".serialize($propMatch['SETTINGS'])."')"
					);
				}
			}
		}
	}
}

unset($currentPresetInfo, $currentMap, $presetImport, $matches, $businessValueMap);


// Create order statuses
$statusList = \Bitrix\Crm\Order\OrderStatus::getDefaultStatuses();
foreach ($statusList as $status)
{
	$dbRes = \Bitrix\Sale\Internals\StatusTable::getList([
		'filter' => ['=ID' => $status['STATUS_ID']]
	]);

	if (!$dbRes->fetch())
	{
		$result = \Bitrix\Sale\Internals\StatusTable::add([
			'ID' => $status['STATUS_ID'],
			'SORT' => $status['SORT'],
			'TYPE' => \Bitrix\Crm\Order\OrderStatus::TYPE
		]);

		if ($result->isSuccess())
		{
			$resultLang = \Bitrix\Sale\Internals\StatusLangTable::add([
				'STATUS_ID' => $result->getId(),
				'NAME' => $status['NAME'],
				'LID' => $languageId
			]);
		}
	}
}

// Create delivery statuses
$statusList = \Bitrix\Crm\Order\DeliveryStatus::getDefaultStatuses();
foreach ($statusList as $status)
{
	$dbRes = \Bitrix\Sale\Internals\StatusTable::getList([
		'filter' => ['=ID' => $status['STATUS_ID']]
	]);

	if (!$dbRes->fetch())
	{
		$result = \Bitrix\Sale\Internals\StatusTable::add([
			'ID' => $status['STATUS_ID'],
			'SORT' => $status['SORT'],
			'TYPE' => \Bitrix\Crm\Order\DeliveryStatus::TYPE
		]);

		if ($result->isSuccess())
		{
			$resultLang = \Bitrix\Sale\Internals\StatusLangTable::add([
				'STATUS_ID' => $result->getId(),
				'NAME' => $status['NAME'],
				'LID' => $languageId
			]);
		}
	}
}

$res = Bitrix\Sale\PaySystem\Manager::getList([
	'select' => ['ID'],
	'filter' => [
		'ENTITY_REGISTRY_TYPE' => \Bitrix\Sale\Registry::REGISTRY_TYPE_ORDER,
		'!=ID' => \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId()
	]
]);

if (!$res->fetch())
{
	$paySystemList = [
		[
			'NAME' =>\Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_PAY_SYSTEM_CASH_NAME'),
			'PSA_NAME' => \Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_PAY_SYSTEM_CASH_NAME'),
			'DESCRIPTION' => \Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_PAY_SYSTEM_CASH_DESC'),
			'ACTION_FILE' => 'cash',
			'ACTIVE' => 'Y',
			'IS_CASH' => 'Y',
			'NEW_WINDOW' => 'N',
			'LOGOTIP' => $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/crm/install/modules/sale/images/cash.png',
			'ENTITY_REGISTRY_TYPE' => 'ORDER',
			'SORT' => 100,
		]
	];

	if (\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isEnabled())
	{
		$agents = CAgent::GetList(
			[],
			[
				"NAME" => '\\Bitrix\\DocumentGenerator\\Driver::installDefaultTemplatesForCurrentRegion();',
				"MODULE_ID" => 'documentgenerator',
				"ACTIVE" => 'Y',
			]
		);
		$agentIsRunning = false;
		if ($agent = $agents->Fetch())
		{
			$agentIsRunning = ($agent['RUNNING'] ?? 'N') === 'Y'; // agent is running right now
			if (!$agentIsRunning)
			{
				CAgent::Delete($agent['ID']);
			}
		}
		if (!$agentIsRunning)
		{
			\Bitrix\DocumentGenerator\Driver::installDefaultTemplatesForCurrentRegion();
		}

		$template = \Bitrix\DocumentGenerator\Model\TemplateTable::getList([
			'select' => ['ID'],
			'filter' => [
				'=CODE' => 'BILL_RU',
			],
		])->fetch();
		$templateId = $template['ID'] ?? null;
		if ($templateId > 0)
		{
			$paySystemList[] = [
				'NAME' => \Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_PAY_SYSTEM_ORDERDOCUMENT_NAME_V2'),
				'PSA_NAME' => \Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_PAY_SYSTEM_ORDERDOCUMENT_NAME_V2'),
				'DESCRIPTION' => \Bitrix\Main\Localization\Loc::getMessage('CRM_ORDER_PAY_SYSTEM_ORDERDOCUMENT_DESC'),
				'ACTION_FILE' => 'orderdocument',
				'ACTIVE' => 'Y',
				'LOGOTIP' => $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/crm/install/modules/sale/images/bill.png',
				'PS_MODE' => $templateId,
				'ENTITY_REGISTRY_TYPE' => 'ORDER',
				'SORT' => 200,
			];
		}
	}

	foreach ($paySystemList as $item)
	{
		if (file_exists($item["LOGOTIP"]))
		{
			$item["LOGOTIP"] = CFile::MakeFileArray($item["LOGOTIP"]);
			$item["LOGOTIP"]["MODULE_ID"] = "sale";
			CFile::SaveForDB($item, "LOGOTIP", "sale/paysystem/logotip");
		}
		else
		{
			unset($item["LOGOTIP"]);
		}

		$dbRes = \Bitrix\Sale\PaySystem\Manager::add($item);
	}
}

$newStoreId = 0;
$data = \Bitrix\Catalog\StoreTable::getRow([
	'select' => [
		'ID',
	],
	'filter' => [
		'=ACTIVE' => 'Y',
	],
]);
if (!$data)
{
	$storeImageId = 0;
	$storeImage = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/crm/install/modules/sale/images/store.png');

	if (!empty($storeImage) && is_array($storeImage))
	{
		$storeImage['MODULE_ID'] = 'catalog';
		$storeImageId =  CFile::SaveFile($storeImage, 'catalog');
	}

	$storeFields = [
		'TITLE' => GetMessage('CRM_CATALOG_STORE_NAME'),
		'ADDRESS' => GetMessage('CRM_CATALOG_STORE_ADR'),
		'DESCRIPTION' => GetMessage('CRM_CATALOG_STORE_DESCR'),
		'GPS_N' => GetMessage('CRM_CATALOG_STORE_GPS_N'),
		'GPS_S' => GetMessage('CRM_CATALOG_STORE_GPS_S'),
		'PHONE' => GetMessage('CRM_CATALOG_STORE_PHONE'),
		'SCHEDULE' => GetMessage('CRM_CATALOG_STORE_SCHEDULE'),
		'IS_DEFAULT' => 'Y',
	];

	if($storeImageId > 0)
	{
		$storeFields['IMAGE_ID'] = $storeImageId;
	}

	$res = \Bitrix\Catalog\StoreTable::add($storeFields);

	if($res->isSuccess())
	{
		$newStoreId = (int)$res->getId();
	}
}
else
{
	$newStoreId = (int)$data['ID'];
}

$res = \Bitrix\Sale\Delivery\Services\Table::getList([
	'filter' => ['!=CLASS_NAME' => '\Bitrix\Sale\Delivery\Services\EmptyDeliveryService']
]);

if(!$res->fetch())
{
	if(in_array($languageId,['ru', 'by', 'kz']))
	{
		$courierPrice = 500;
	}
	else
	{
		$courierPrice = 30;
	}

	$defCurrency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();

	$deliveryItems = [
		[
			'NAME' => GetMessage('CRM_DELIVERY_COURIER'),
			'DESCRIPTION' => GetMessage('CRM_DELIVERY_COURIER_DESCR'),
			'CLASS_NAME' => '\Bitrix\Sale\Delivery\Services\Configurable',
			'CURRENCY' => $defCurrency,
			'SORT' => 100,
			'ACTIVE' => 'Y',
			'LOGOTIP' => $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/crm/install/modules/sale/images/courier_logo.png',
			'CONFIG' => [
				'MAIN' => [
					'PRICE' => $courierPrice,
					'CURRENCY' => $defCurrency,
					'PERIOD' => [
						'FROM' => 1,
						'TO' => 3,
						'TYPE' => 'D'
					]
				]
			]
		],
		[
			'NAME' => GetMessage('CRM_DELIVERY_PICKUP'),
			'DESCRIPTION' => GetMessage('CRM_DELIVERY_PICKUP_DESCR'),
			'CLASS_NAME' => '\Bitrix\Sale\Delivery\Services\Configurable',
			'CURRENCY' => $defCurrency,
			'SORT' => 200,
			'ACTIVE' => 'Y',
			'LOGOTIP' => $_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/crm/install/modules/sale/images/self_logo.png',
			'CONFIG' => [
				'MAIN' => [
					'PRICE' => 0,
					'CURRENCY' => $defCurrency,
					'PERIOD' => [
						'FROM' => 0,
						'TO' => 0,
						'TYPE' => 'D'
					]
				]
			]
		]
	];

	foreach($deliveryItems as $fields)
	{
		if (file_exists($fields["LOGOTIP"]))
		{
			$fields["LOGOTIP"] = CFile::MakeFileArray($fields["LOGOTIP"]);
			$fields["LOGOTIP"]["MODULE_ID"] = "sale";
			CFile::SaveForDB($fields, "LOGOTIP", "sale/delivery/logotip");
		}

		try
		{
			if($service = \Bitrix\Sale\Delivery\Services\Manager::createObject($fields))
			{
				$fields = $service->prepareFieldsForSaving($fields);
			}
		}
		catch(\Bitrix\Main\SystemException $e)
		{
			continue;
		}

		$res = \Bitrix\Sale\Delivery\Services\Manager::add($fields);

		if($res->isSuccess())
		{
			if($fields["NAME"] == GetMessage("CRM_DELIVERY_PICKUP") && $newStoreId > 0)
			{
				\Bitrix\Sale\Delivery\ExtraServices\Manager::saveStores(
					$res->getId(),
					[$newStoreId]
				);
			}
		}
	}
}

if (Bitrix\Main\Config\Option::get("sale", "~IS_SALE_CRM_SITE_MASTER_FINISH", "N") !== "Y")
{
	//Install robot presets
	\Bitrix\Crm\Automation\Demo\Wizard::installOrderPresets();

	COption::SetOptionString("sale", "status_on_paid", '');
	COption::SetOptionString("sale", "status_on_half_paid", '');
	COption::SetOptionString("sale", "status_on_allow_delivery", '');
	COption::SetOptionString("sale", "status_on_allow_delivery_one_of", '');
	COption::SetOptionString("sale", "status_on_shipped_shipment", '');
	COption::SetOptionString("sale", "status_on_shipped_shipment_one_of", '');
	COption::SetOptionString("sale", "shipment_status_on_allow_delivery", '');
	COption::SetOptionString("sale", "shipment_status_on_shipped", '');
	COption::SetOptionString("sale", "status_on_payed_2_allow_delivery", '');
	COption::SetOptionString("sale", "status_on_change_allow_delivery_after_paid", 'N');
	COption::SetOptionString("sale", "allow_deduction_on_delivery", '');
}

// Add groups for crm shop
$groupObject = new CGroup;
$groupsData = array(
	array(
		"ACTIVE" => "Y",
		"C_SORT" => 100,
		"NAME" => GetMessage("SALE_USER_GROUP_SHOP_ADMIN_NAME"),
		"STRING_ID" => "CRM_SHOP_ADMIN",
		"DESCRIPTION" => GetMessage("SALE_USER_GROUP_SHOP_ADMIN_DESC"),
		"BASE_RIGHTS" => array("sale" => "W"),
		"TASK_RIGHTS" => array("catalog" => "W", "main" => "R", "iblock" => "X")
	),
	array(
		"ACTIVE" => "Y",
		"C_SORT" => 100,
		"NAME" => GetMessage("SALE_USER_GROUP_SHOP_MANAGER_NAME"),
		"STRING_ID" => "CRM_SHOP_MANAGER",
		"DESCRIPTION" => GetMessage("SALE_USER_GROUP_SHOP_MANAGER_DESC"),
		"BASE_RIGHTS" => array("sale" => "U"),
		"TASK_RIGHTS" => array("catalog" => "W", "iblock" => "W")
	),
);
global $APPLICATION;
foreach ($groupsData as $groupData)
{
	$groupId = $groupObject->add($groupData);
	if ($groupObject->LAST_ERROR == '' && $groupId)
	{
		foreach($groupData["BASE_RIGHTS"] as $moduleId => $letter)
		{
			$APPLICATION->setGroupRight($moduleId, $groupId, $letter, false);
		}
		foreach($groupData["TASK_RIGHTS"] as $moduleId => $letter)
		{
			switch ($moduleId)
			{
				case "iblock":
					CIBlockRights::setGroupRight($groupId, "CRM_PRODUCT_CATALOG", $letter);
					break;
				default:
					CGroup::SetModulePermission($groupId, $moduleId, CTask::GetIdByLetter($letter, $moduleId));
			}
		}
	}
}

$adminGroupId = CCrmSaleHelper::getShopGroupIdByType('admin');
if($adminGroupId>0)
{
	global $USER;

	$currentUserId = 0;
	if ($USER instanceof \CUser)
	{
		$currentUserId = (int)$USER->GetID();
	}

	$userId = 1;
	\CUser::AppendUserGroup($userId, $adminGroupId);
	if ($currentUserId == $userId)
	{
		$USER->CheckAuthActions();
	}
}

$groupId = 0;

$dbGroupList = CGroup::GetListEx(
	['NAME' => 'ASC'],
	[
		'IS_SYSTEM' => 'Y',
		'STRING_ID' => 'CRM_SHOP_BUYER'
	],
	false, false,
	['ID', 'NAME']
);
if ($arGroup = $dbGroupList->Fetch())
{
	$groupId = (int)$arGroup['ID'];
}

if ($groupId <= 0)
{
	$groupObject = new CGroup;
	$groupId = $groupObject->add([
		'ACTIVE' => 'Y',
		'C_SORT' => 10,
		'NAME' => Bitrix\Main\Localization\Loc::getMessage('CRM_SALE_USER_GROUP_SHOP_BUYER_NAME'),
		'STRING_ID' => 'CRM_SHOP_BUYER',
		'IS_SYSTEM' => 'Y',
		'DESCRIPTION' => Bitrix\Main\Localization\Loc::getMessage('CRM_SALE_USER_GROUP_SHOP_BUYER_DESC'),
	]);
}

\Bitrix\Main\Config\Option::set('crm', 'shop_buyer_group', $groupId);

$anonymousId = \Bitrix\Main\Config\Option::get('sale', 'anonymous_user_id', 0);
if ($anonymousId > 0)
{
	\CUser::AppendUserGroup($anonymousId, $groupId);
}

// default trading platforms
if (\Bitrix\Main\Config\Option::get('catalog', 'default_use_store_control', 'N') === 'Y')
{
	$platformCode = \Bitrix\Crm\Order\TradingPlatform\RealizationDocument::TRADING_PLATFORM_CODE;
	$platform = \Bitrix\Crm\Order\TradingPlatform\RealizationDocument::getInstanceByCode($platformCode);
	if (!$platform->isInstalled())
	{
		$platform->install();
	}
}

$platformCode = \Bitrix\Crm\Order\TradingPlatform\Activity::TRADING_PLATFORM_CODE;
$platform = \Bitrix\Crm\Order\TradingPlatform\Activity::getInstanceByCode($platformCode);
if (!$platform->isInstalled())
{
	$platform->install();
}

$dynamicEntityTypeList = [\CCrmOwnerType::Deal, \CCrmOwnerType::SmartInvoice];
foreach ($dynamicEntityTypeList as $type)
{
	$platformCode = \Bitrix\Crm\Order\TradingPlatform\DynamicEntity::getCodeByEntityTypeId($type);
	$platform = \Bitrix\Crm\Order\TradingPlatform\DynamicEntity::getInstanceByCode($platformCode);
	if (!$platform->isInstalled())
	{
		$platform->install();
	}
}

if ($DB->TableExists("b_crm_shipment_realization") && $DB->TableExists("b_sale_order_delivery"))
{
	$DB->query("INSERT IGNORE INTO b_crm_shipment_realization (SHIPMENT_ID, IS_REALIZATION) SELECT ID, 'Y' FROM b_sale_order_delivery;");
}

// catalog rights
if (!\Bitrix\Catalog\Access\Permission\PermissionTable::getCount())
{
	\Bitrix\Catalog\Access\Install\AccessInstaller::installClean();
}

\Bitrix\Main\Config\Option::set('crm', 'enable_entity_commodity_item_creation', 'N');

$platformCode = \Bitrix\Crm\Order\TradingPlatform\Terminal::TRADING_PLATFORM_CODE;
$platform = \Bitrix\Crm\Order\TradingPlatform\Terminal::getInstanceByCode($platformCode);
if (!$platform->isInstalled())
{
	$platform->install();
}
