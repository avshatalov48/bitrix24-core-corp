<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\RequisiteAddress;
use	Bitrix\Sale\BusinessValue;
use Bitrix\Sale\Internals\BusinessValuePersonDomainTable;
use Bitrix\Catalog;

/**
 * @global $DB CDatabase
 */
global $DB;

Loc::loadMessages(__FILE__);

if (!function_exists('OnModuleInstalledEvent'))
{
	function OnModuleInstalledEvent($id)
	{
		foreach (GetModuleEvents("main", "OnModuleInstalled", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($id));
	}
}

if (!Main\ModuleManager::isModuleInstalled('currency'))
{
	$errMsg[] = Loc::getMessage('CRM_CURRENCY_NOT_INSTALLED');
	$bError = true;
	return;
}

if (!Main\Loader::includeModule('sale'))
{
	$errMsg[] = Loc::getMessage('CRM_SALE_NOT_INCLUDED');
	$bError = true;
	return;
}

$bitrix24Path = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/bitrix24/';
$bitrix24 = file_exists($bitrix24Path) && is_dir($bitrix24Path);
unset($bitrix24Path);
$languageId = '';
if (IsModuleInstalled('bitrix24')
	&& CModule::IncludeModule('bitrix24')
	&& method_exists('CBitrix24', 'getLicensePrefix'))
{
	$languageId = CBitrix24::getLicensePrefix();
}
if ($languageId == '')
{
	/** @todo Use SiteTable::getDefaultLanguageId() */
	$siteIterator = \Bitrix\Main\SiteTable::getList(array(
		'select' => array('LID', 'LANGUAGE_ID'),
		'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y'),
		'cache' => ['ttl' => 86400],
	));
	if ($site = $siteIterator->fetch())
		$languageId = (string)$site['LANGUAGE_ID'];
	unset($site, $siteIterator);
}
if ($languageId == '')
	$languageId = 'en';
$countryLangId = '';
switch ($languageId)
{
	case 'ua':
	case 'de':
	case 'en':
	case 'la':
	case 'tc':
	case 'sc':
	case 'in':
	case 'kz':
	case 'br':
	case 'fr':
	case 'by':
	case 'ru':
		$countryLangId = $languageId;
		break;
	default:
		$countryLangId = 'en';
		break;
}
switch ($countryLangId)
{
	case 'ru':
	case 'ua':
	case 'de':
	case 'en':
	case 'la':
	case 'br':
	case 'fr':
		$shopLocalization = $countryLangId;
		$billPsLocalization = $quotePsLocalization = $countryLangId;
		break;
	case 'by':
		$shopLocalization = $quotePsLocalization = 'ru';
		$billPsLocalization = 'by';
		break;
	case 'kz':
		$shopLocalization = $quotePsLocalization = 'ru';
		$billPsLocalization = 'kz';
		break;
	case 'tc':
		$shopLocalization = 'tc';
		$billPsLocalization = $quotePsLocalization = 'en';
		break;
	case 'sc':
		$shopLocalization = 'sc';
		$billPsLocalization = $quotePsLocalization = 'en';
		break;
	case 'in':
		$shopLocalization = 'en';
		$billPsLocalization = $quotePsLocalization = 'en';
		break;
	default:
		$shopLocalization = $countryLangId;
		$billPsLocalization = $quotePsLocalization = 'en';
		break;
}

$currentSiteID = SITE_ID;
if (defined("ADMIN_SECTION"))
{
	/** @todo Use SiteTable::getDefaultSiteId() */
	$siteIterator = Main\SiteTable::getList(array(
		'select' => array('LID', 'LANGUAGE_ID'),
		'filter' => array('=DEF' => 'Y', '=ACTIVE' => 'Y'),
		'cache' => ['ttl' => 86400],
	));
	if ($defaultSite = $siteIterator->fetch())
	{
		$currentSiteID = $defaultSite['LID'];
	}
	unset($defaultSite, $siteIterator);
}

$defCurrency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();
if ($defCurrency !== null)
{
	COption::SetOptionString("sale", "default_currency", $defCurrency);
}

// Create invoice statuses
$statusesSort = array(
	'N' => 100,
	'D' => 140,
	'P' => 130,
	'S' => 110
);
$createStatusList = $statusesSort;

$statusList = \CCrmStatus::GetStatusList('INVOICE_STATUS');
$arExistStatuses = array_keys($statusList);

foreach ($arExistStatuses as $statusId)
{
	if ($statusId === 'N')
		continue;

	unset($createStatusList[$statusId]);
}

if (!empty($createStatusList))
{
	$statusLangFiles = Loc::loadLanguageFile(
		$_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/install/status.php',
		$languageId
	);

	foreach ($createStatusList as $statusId => $statusSort)
	{
		$nameExist = isset($statusLangFiles['CRM_STATUSN_'.$statusId]);
		if (!$nameExist)
			continue;

		$name = $statusLangFiles['CRM_STATUSN_'.$statusId];

		$crmStatus = new \CCrmStatus('INVOICE_STATUS');

		$isSystem = ($statusId === 'N' || $statusId === 'P' || $statusId === 'D');
		$semantics = null;
		if($statusId === 'P')
		{
			$semantics = 'S';
		}
		elseif($statusId === 'D')
		{
			$semantics = 'F';
		}
		$status = array(
			'SORT' => $statusSort,
			'NAME' => $name,
			'SYSTEM' => ($isSystem ? 'Y' : 'N'),
			'NAME_INIT' => ($isSystem ? $name : ''),
			'SEMANTICS' => $semantics,
		);

		if (!in_array($statusId, $arExistStatuses, true))
		{
			$status['STATUS_ID'] = $statusId;
			$crmStatus->Add($status);
		}
		else if ($statusId === 'N')
		{
			$data = $crmStatus->GetStatusByStatusId($statusId);
			$crmStatus->Update($data['ID'], $status, ['ENABLE_NAME_INIT' => true]);
		}
	}
	unset($statusLangFiles);
}


//Create person types
$companyPTID  = $contactPTID = 0;

$dbRes = \Bitrix\Crm\Invoice\PersonType::getList([
	'filter' => [
		"=PERSON_TYPE_SITE.SITE_ID" => $currentSiteID,
		'@CODE' => ['CRM_COMPANY', 'CRM_CONTACT']
	]
]);

while($arPerson = $dbRes->fetch())
{
	if($arPerson["CODE"] == 'CRM_COMPANY')
		$companyPTID = $arPerson["ID"];
	elseif($arPerson["CODE"] == 'CRM_CONTACT')
		$contactPTID = $arPerson["ID"];
}

if($companyPTID <=0 )
{
	$res = \Bitrix\Sale\Internals\PersonTypeTable::add(array(
					"LID" => $currentSiteID,
					"NAME" => Loc::getMessage('CRM_PERSON_TYPE_COMPANY'),
					"CODE" => 'CRM_COMPANY',
					"SORT" => "110",
					"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE,
					"ACTIVE" => "Y"
			)
	);

	$allPersonTypes = BusinessValue::getPersonTypes(true);
	$companyPTID = $personTypeId = $res->getId();
	$domain = BusinessValue::ENTITY_DOMAIN;

	if ($personTypeId > 0)
	{
		\Bitrix\Sale\Internals\PersonTypeSiteTable::add([
			'PERSON_TYPE_ID' => $personTypeId,
			'SITE_ID' => $currentSiteID
		]);

		$dbRes = BusinessValuePersonDomainTable::getList([
			'filter' => [
				'PERSON_TYPE_ID' => $personTypeId,
				'DOMAIN' => $domain
			]
		]);

		if (!$dbRes->fetch())
		{
			$r = BusinessValuePersonDomainTable::add(array(
					'PERSON_TYPE_ID' => $personTypeId,
					'DOMAIN'         => $domain,
			));

			if ($r->isSuccess())
			{
				$allPersonTypes[$personTypeId]['DOMAIN'] = $domain;
				BusinessValue::getPersonTypes(true, $allPersonTypes);
			}
			else
			{
				CEventLog::Add(array(
						'SEVERITY' => 'ERROR',
						'AUDIT_TYPE_ID' => 'SALE_1C_TO_BUSINESS_VALUE_ERROR',
						'MODULE_ID' => 'sale',
						'ITEM_ID' => "sale_link.Contact.Add:".$personTypeId,
						'DESCRIPTION' => 'Unable to set person type "'.$personTypeId.'" domain'."\n".implode("\n", $r->getErrorMessages()),
				));
			}
		}
	}
}


if($contactPTID <=0 )
{
	$res = \Bitrix\Sale\Internals\PersonTypeTable::add(array(
					"LID" => $currentSiteID,
					"NAME" => Loc::getMessage('CRM_PERSON_TYPE_CONTACT'),
					"CODE" => 'CRM_CONTACT',
					"SORT" => "100",
					"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE,
					"ACTIVE" => "Y"
			)
	);

	$allPersonTypes = BusinessValue::getPersonTypes(true);
	$contactPTID = $personTypeId = $res->getId();
	$domain = BusinessValue::INDIVIDUAL_DOMAIN;

	if ($personTypeId > 0)
	{
		\Bitrix\Sale\Internals\PersonTypeSiteTable::add([
			'PERSON_TYPE_ID' => $personTypeId,
			'SITE_ID' => $currentSiteID
		]);

		$dbRes = BusinessValuePersonDomainTable::getList([
			'filter' => [
				'PERSON_TYPE_ID' => $personTypeId,
				'DOMAIN' => $domain
			]
		]);

		if (!$dbRes->fetch())
		{
			$r = BusinessValuePersonDomainTable::add(array(
					'PERSON_TYPE_ID' => $contactPTID,
					'DOMAIN'         => $domain,
			));

			if ($r->isSuccess())
			{
				$allPersonTypes[$personTypeId]['DOMAIN'] = $domain;
				BusinessValue::getPersonTypes(true, $allPersonTypes);
			}
			else
			{
				CEventLog::Add(array(
						'SEVERITY' => 'ERROR',
						'AUDIT_TYPE_ID' => 'SALE_1C_TO_BUSINESS_VALUE_ERROR',
						'MODULE_ID' => 'sale',
						'ITEM_ID' => "sale_link.Contact.Add:".$personTypeId,
						'DESCRIPTION' => 'Unable to set person type "'.$personTypeId.'" domain'."\n".implode("\n", $r->getErrorMessages()),
				));
			}
		}
	}
}


//Order user fields
$obUserField  = new CUserTypeEntity;
$arOrderUserFieldDefault = array(
	'ENTITY_ID' => 'CRM_INVOICE',
	'FIELD_NAME' => 'UF_FIELD',
	'USER_TYPE_ID' => 'string',
	'XML_ID' => 'uf_field',
	'SORT' => '2000',
	'MULTIPLE' => null,
	'MANDATORY' => null,
	'SHOW_FILTER' => 'N',
	'SHOW_IN_LIST' => 'N',
	'EDIT_IN_LIST' => 'N',
	'IS_SEARCHABLE' => null,
	'SETTINGS' => array(
		'DEFAULT_VALUE' => null,
		'SIZE' => '',
		'ROWS' => '1',
		'MIN_LENGTH' => '0',
		'MAX_LENGTH' => '0',
		'REGEXP' => ''
	),
	'EDIT_FORM_LABEL' => array('ru' => '', 'en' => ''),
	'LIST_COLUMN_LABEL' => array('ru' => '', 'en' => ''),
	'LIST_FILTER_LABEL' => array('ru' => '', 'en' => ''),
	'ERROR_MESSAGE' => array('ru' => '', 'en' => ''),
	'HELP_MESSAGE' => array('ru' => '', 'en' => '')
);
$dbRes = $obUserField->GetList(array('SORT' => 'DESC'), array('ENTITY_ID' => 'CRM_INVOICE'));
$maxUFSort = 0;
$i = 0;
$arUFNames = array();
while ($arUF = $dbRes->Fetch())
{
	if ($i++ === 0)
		$maxUFSort = intval($arUF['SORT']);
	$arUFNames[] = $arUF['FIELD_NAME'];
}
unset($dbRes, $arUF, $i);
$ufIndexableFields = [];
$arOrderUserFields = array();
if (!in_array('UF_DEAL_ID', $arUFNames))
{
	$arOrderUserFields[] = array(
		'FIELD_NAME' => 'UF_DEAL_ID',
		'USER_TYPE_ID' => 'integer',
		'XML_ID' => 'uf_deal_id'
	);
	$ufIndexableFields['DEAL_ID'] = 'UF_DEAL_ID';
}
if (!in_array('UF_QUOTE_ID', $arUFNames))
{
	$arOrderUserFields[] = array(
		'FIELD_NAME' => 'UF_QUOTE_ID',
		'USER_TYPE_ID' => 'integer',
		'XML_ID' => 'uf_quote_id'
	);
}
if (!in_array('UF_COMPANY_ID', $arUFNames))
{
	$arOrderUserFields[] = array(
		'FIELD_NAME' => 'UF_COMPANY_ID',
		'USER_TYPE_ID' => 'integer',
		'XML_ID' => 'uf_company_id'
	);
	$ufIndexableFields['COMPANY_ID'] = 'UF_COMPANY_ID';
}
if (!in_array('UF_CONTACT_ID', $arUFNames))
{
	$arOrderUserFields[] = array(
		'FIELD_NAME' => 'UF_CONTACT_ID',
		'USER_TYPE_ID' => 'integer',
		'XML_ID' => 'uf_contact_id'
	);
	$ufIndexableFields['CONTACT_ID'] = 'UF_CONTACT_ID';
}
if (!in_array('UF_MYCOMPANY_ID', $arUFNames))
{
	$arOrderUserFields[] = array(
		'FIELD_NAME' => 'UF_MYCOMPANY_ID',
		'USER_TYPE_ID' => 'integer',
		'XML_ID' => 'uf_mycompany_id'
	);
}
unset($arUFNames);
$sort = $maxUFSort;
foreach ($arOrderUserFields as $field)
{
	$arFields = $arOrderUserFieldDefault;

	if ($sort === 0)
		$sort = $arFields['SORT'];
	else
		$sort += 10;
	$arFields['SORT'] = $sort;

	foreach ($field as $k => $v)
		$arFields[$k] = $v;

	$ID = $obUserField->Add($arFields);
	if ($ID <= 0)
	{
		$errMsg[] = Loc::getMessage(
			'CRM_CANT_ADD_USER_FIELD1',
			[
				'#FIELD_NAME#' => $arFields['FIELD_NAME'],
				"#ENTITY_TYPE#" => CCrmInvoice::GetUserFieldEntityID()
			]
		);
		$bError = true;
	}
}

if ($bError)
	return;

foreach ($ufIndexableFields as $ixNameSuffix => $ufIndexableField)
{
	if ($DB->GetIndexName('b_uts_crm_invoice', [$ufIndexableField], true) === '')
	{
		// IX_UTS_INVOICE_DEAL_ID
		// IX_UTS_INVOICE_CONTACT_ID
		// IX_UTS_INVOICE_COMPANY_ID
		$inName = 'IX_UTS_INVOICE_' . $ixNameSuffix;
		$DB->Query("CREATE INDEX {$inName} ON b_uts_crm_invoice({$ufIndexableField})");
	}
}

//Order Prop Group
$arPropGroup = array();

$dbSaleOrderPropsGroup = CSaleOrderPropsGroup::GetList(array(), array("PERSON_TYPE_ID" => $contactPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ1")), false, false, array("ID"));
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->GetNext())
{
	$arPropGroup["user_fiz"] = $arSaleOrderPropsGroup["ID"];
}
else
{
	$r = \Bitrix\Sale\Internals\OrderPropsGroupTable::add(array("PERSON_TYPE_ID" => $contactPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ1"), "SORT" => 100));
	$arPropGroup["user_fiz"] = $r->getId();
}

$dbSaleOrderPropsGroup = CSaleOrderPropsGroup::GetList(array(), array("PERSON_TYPE_ID" => $contactPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ2")), false, false, array("ID"));
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->GetNext())
{
	$arPropGroup["adres_fiz"] = $arSaleOrderPropsGroup["ID"];
}
else
{
	$r = \Bitrix\Sale\Internals\OrderPropsGroupTable::add(array("PERSON_TYPE_ID" => $contactPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_FIZ2"), "SORT" => 200));
	$arPropGroup["adres_fiz"] = $r->getId();
}

$dbSaleOrderPropsGroup = CSaleOrderPropsGroup::GetList(array(), array("PERSON_TYPE_ID" => $companyPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_UR1")), false, false, array("ID"));
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->GetNext())
{
	$arPropGroup["user_ur"] = $arSaleOrderPropsGroup["ID"];
}
else
{
	$r = \Bitrix\Sale\Internals\OrderPropsGroupTable::add(array("PERSON_TYPE_ID" => $companyPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_UR1"), "SORT" => 300));
	$arPropGroup["user_ur"] = $r->getId();
}

$dbSaleOrderPropsGroup = CSaleOrderPropsGroup::GetList(array(), array("PERSON_TYPE_ID" => $companyPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_UR2")), false, false, array("ID"));
if ($arSaleOrderPropsGroup = $dbSaleOrderPropsGroup->GetNext())
{
	$arPropGroup["adres_ur"] = $arSaleOrderPropsGroup["ID"];
}
else
{
	$r = \Bitrix\Sale\Internals\OrderPropsGroupTable::add(array("PERSON_TYPE_ID" => $companyPTID, "NAME" => Loc::getMessage("CRM_ORD_PROP_GROUP_UR2"), "SORT" => 400));
	$arPropGroup["adres_ur"] = $r->getId();
}

$arProps = array();

$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_6"),
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
	"IS_LOCATION4TAX" => "N",
	"CODE" => "EMAIL",
	"IS_FILTERED" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_9"),
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
	"IS_LOCATION4TAX" => "N",
	"CODE" => "PHONE",
	"IS_FILTERED" => "N",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_4"),
	"TYPE" => "TEXT",
	"REQUIED" => "N",
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
	"IS_LOCATION4TAX" => "N",
	"CODE" => "ZIP",
	"IS_FILTERED" => "N",
	"IS_ZIP" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_21"),
	"TYPE" => "TEXT",
	"REQUIED" => "N",
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
	"IS_LOCATION4TAX" => "N",
	"CODE" => "CITY",
	"IS_FILTERED" => "Y",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_2"),
	"TYPE" => "LOCATION",
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
	"IS_LOCATION4TAX" => "Y",
	"CODE" => "LOCATION",
	"IS_FILTERED" => "N",
	"INPUT_FIELD_LOCATION" => ""
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $contactPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_5"),
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
	"IS_LOCATION4TAX" => "N",
	"CODE" => "ADDRESS",
	"IS_FILTERED" => "N",
);
$arProps[] = array(
	"PERSON_TYPE_ID" => $companyPTID,
	"NAME" => Loc::getMessage("CRM_ORD_PROP_2"),
	"TYPE" => "LOCATION",
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
	"IS_LOCATION4TAX" => "Y",
	"CODE" => "LOCATION",
	"IS_FILTERED" => "N",
);

if ($shopLocalization == "ru")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_13"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "INN",
		"IS_FILTERED" => "N",
	);

	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_14"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "KPP",
		"IS_FILTERED" => "N",
	);
}
elseif ($shopLocalization == "de")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_BLZ"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "BLZ",
		"IS_FILTERED" => "N",
	);

	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_IBAN"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "IBAN",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_BIC_SWIFT"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "BIC_SWIFT",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_UST_IDNR"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "UST_IDNR",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_STEU"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "STEU",
		"IS_FILTERED" => "N",
	);
}
elseif (in_array($shopLocalization, array('en', 'la', 'br', 'fr'), true))
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_IBAN"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "IBAN",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_BIC_SWIFT"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "BIC_SWIFT",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_SORT_CODE"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "SORT_CODE",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_CRN"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_REG_NO",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_TRN"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "TAX_REG_NO",
		"IS_FILTERED" => "N",
	);

}

if($shopLocalization != "ua")
{
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_8"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_7"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_ADR",
		"IS_FILTERED" => "N",
	);

	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_10"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "EMAIL",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_9"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "PHONE",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_11"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "FAX",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_4"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ZIP",
		"IS_FILTERED" => "N",
		"IS_ZIP" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_21"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "CITY",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_12"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "EMAIL",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_40"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_NAME",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_47"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "COMPANY_ADR",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_48"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "EGRPU",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_49"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "INN",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_46"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "NDS",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_44"),
		"TYPE" => "TEXT",
		"REQUIED" => "N",
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ZIP",
		"IS_FILTERED" => "N",
		"IS_ZIP" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_43"),
		"TYPE" => "TEXT",
		"REQUIED" => "Y",
		"DEFAULT_VALUE" => $shopLocation,
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "CITY",
		"IS_FILTERED" => "Y",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_42"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "ADDRESS",
		"IS_FILTERED" => "N",
	);
	$arProps[] = array(
		"PERSON_TYPE_ID" => $companyPTID,
		"NAME" => Loc::getMessage("CRM_ORD_PROP_45"),
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
		"IS_LOCATION4TAX" => "N",
		"CODE" => "PHONE",
		"IS_FILTERED" => "N",
	);

}

$arGeneralInfo = array();

foreach($arProps as $prop)
{
	$dbRes = \Bitrix\Crm\Invoice\Property::getList([
		'select' => ['ID'],
		'filter' => [
			"PERSON_TYPE_ID" => $prop["PERSON_TYPE_ID"],
			"CODE" =>  $prop["CODE"]
		]
	]);

	if (!$dbRes->fetch())
	{
		$prop = CSaleOrderPropsAdapter::convertOldToNew($prop);
		$prop['ENTITY_REGISTRY_TYPE'] = REGISTRY_TYPE_CRM_INVOICE;

		$r = \Bitrix\Sale\Internals\OrderPropsTable::add($prop);
	}
}

$newPSContactParams = $newPSCompanyParams = false;
$rqCountryId = EntityPreset::getCurrentCountryId();
if ($rqCountryId > 0 && in_array($rqCountryId, EntityRequisite::getAllowedRqFieldCountries(), true))
{
	/*$newPSContactParams = (Main\Config\Option::get('crm', '~CRM_TRANSFER_REQUISITES_TO_CONTACT', 'N') !== 'Y');
	$newPSCompanyParams = (Main\Config\Option::get('crm', '~CRM_TRANSFER_REQUISITES_TO_COMPANY', 'N') !== 'Y');*/
	$newPSContactParams = $newPSCompanyParams = true;
}

//PaySystem
$arPaySystems = array();
$paySysName = 'bill';
if (EntityPreset::isUTFMode())
	$billRqCountryId = CCrmPaySystem::getPresetCountryIdByPS($paySysName, $billPsLocalization);
else
	$billRqCountryId = $rqCountryId;
if($billPsLocalization != 'ru')
	$paySysName .= $billPsLocalization;

// INVOICE PAYSYSTEMS -->
if ($billPsLocalization === 'ua')
{
	$psParams = array(
		"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
		"SELLER_COMPANY_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_NAME'),
		"SELLER_COMPANY_ADDRESS" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ADDRESS'),
		"SELLER_COMPANY_PHONE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PHONE'),
		"SELLER_COMPANY_IPN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_IPN'),
		"SELLER_COMPANY_EDRPOY" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_EDRPOY'),
		"SELLER_COMPANY_BANK_ACCOUNT" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_ACCOUNT'),
		"SELLER_COMPANY_BANK_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_NAME'),
		"SELLER_COMPANY_MFO" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_MFO'),
		"SELLER_COMPANY_PDV" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PDV'),
		"ORDER_ID" => array("TYPE" => "ORDER", "VALUE" => "ID"),
		"SELLER_COMPANY_SYS" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_SYS')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_COMPANY', 'bill', $billRqCountryId);
	}
	else
	{
		$buyerParams = array(
			"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "PROPERTY", "VALUE" => "COMPANY_NAME"),
			"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "PROPERTY", "VALUE" => "INN"),
			"BUYER_PERSON_COMPANY_ADDRESS" => array("TYPE" => "PROPERTY", "VALUE" => "COMPANY_ADR"),
			"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "PROPERTY", "VALUE" => "PHONE"),
			"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "PROPERTY", "VALUE" => "FAX")
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams[mb_strtoupper($paySysName)."_PATH_TO_STAMP"] = array("TYPE" => "", "VALUE" => "");
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"PERSON_TYPE_ID" => $companyPTID,
		"ACTION_FILE" => $paySysName,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PARAMS" => serialize($psParams),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
		"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE
	);

	$psParams = array(
		"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
		"SELLER_COMPANY_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_NAME'),
		"SELLER_COMPANY_ADDRESS" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ADDRESS'),
		"SELLER_COMPANY_PHONE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PHONE'),
		"SELLER_COMPANY_IPN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_IPN'),
		"SELLER_COMPANY_EDRPOY" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_EDRPOY'),
		"SELLER_COMPANY_BANK_ACCOUNT" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_ACCOUNT'),
		"SELLER_COMPANY_BANK_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_NAME'),
		"SELLER_COMPANY_MFO" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_MFO'),
		"SELLER_COMPANY_PDV" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PDV')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_CONTACT', 'bill', $billRqCountryId);
	}
	else
	{
		$buyerParams = array(
			"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "PROPERTY", "VALUE" => "FIO"),
			"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "PROPERTY", "VALUE" => "INN"),
			"BUYER_PERSON_COMPANY_ADDRESS" => array("TYPE" => "PROPERTY", "VALUE" => "ADDRESS"),
			"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "PROPERTY", "VALUE" => "PHONE"),
			"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "PROPERTY", "VALUE" => "FAX")
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams[mb_strtoupper($paySysName)."_PATH_TO_STAMP"] = array("TYPE" => "", "VALUE" => "");
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"PERSON_TYPE_ID" => $contactPTID,
		"ACTION_FILE" => $paySysName,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PARAMS" => serialize($psParams),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
		"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE
	);
}
else if ($billPsLocalization === 'kz')
{
	$psParams = array(
		"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
		"PAYMENT_DATE_PAY_BEFORE" => array("TYPE" => "ORDER", "VALUE" => "DATE_PAY_BEFORE"),
		"SELLER_COMPANY_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_NAME'),
		"SELLER_COMPANY_ADDRESS" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ADDRESS'),
		"SELLER_COMPANY_PHONE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PHONE'),
		"SELLER_COMPANY_IIN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_IIN'),
		"SELLER_COMPANY_BIN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BIN'),
		"SELLER_COMPANY_KBE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_KBE'),
		"SELLER_COMPANY_IIK" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_IIK'),
		"SELLER_COMPANY_BANK_BIC" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_BIC'),
		"SELLER_COMPANY_BANK_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_NAME'),
		"SELLER_COMPANY_BANK_CITY" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_CITY'),
		"SELLER_COMPANY_DIRECTOR_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_DIRECTOR_NAME'),
		"SELLER_COMPANY_ACCOUNTANT_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ACCOUNTANT_NAME')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_COMPANY', 'bill', $billRqCountryId);
	}
	else
	{
		$buyerParams = array(
			"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "PROPERTY", "VALUE" => "COMPANY"),
			"BUYER_PERSON_COMPANY_IIN" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_BIN" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_ADDRESS" => array("TYPE" => "PROPERTY", "VALUE" => "COMPANY_ADR"),
			"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "PROPERTY", "VALUE" => "PHONE"),
			"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "PROPERTY", "VALUE" => "FAX"),
			"BUYER_PERSON_COMPANY_NAME_CONTACT" => array("TYPE" => "PROPERTY", "VALUE" => "CONTACT_PERSON")
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams[mb_strtoupper($paySysName)."_COMMENT1"] = array("TYPE" => "ORDER", "VALUE" => "USER_DESCRIPTION");
	$psParams[mb_strtoupper($paySysName)."_COMMENT2"] = array("TYPE" => "", "VALUE" => "");
	$psParams[mb_strtoupper($paySysName)."_PATH_TO_STAMP"] = array("TYPE" => "", "VALUE" => "");
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"ACTION_FILE" => $paySysName,
		"PERSON_TYPE_ID" => $companyPTID,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PARAMS" => serialize($psParams),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
		"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE
	);

	$psParams = array(
		"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
		"PAYMENT_DATE_PAY_BEFORE" => array("TYPE" => "ORDER", "VALUE" => "DATE_PAY_BEFORE"),
		"SELLER_COMPANY_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_NAME'),
		"SELLER_COMPANY_ADDRESS" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ADDRESS'),
		"SELLER_COMPANY_PHONE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PHONE'),
		"SELLER_COMPANY_IIN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_IIN'),
		"SELLER_COMPANY_BIN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BIN'),
		"SELLER_COMPANY_KBE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_KBE'),
		"SELLER_COMPANY_IIK" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_IIK'),
		"SELLER_COMPANY_BANK_BIC" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_BIC'),
		"SELLER_COMPANY_BANK_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_NAME'),
		"SELLER_COMPANY_BANK_CITY" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_CITY'),
		"SELLER_COMPANY_DIRECTOR_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_DIRECTOR_NAME'),
		"SELLER_COMPANY_ACCOUNTANT_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ACCOUNTANT_NAME')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_CONTACT', 'bill', $billRqCountryId);
	}
	else
	{
		$buyerParams = array(
			"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "PROPERTY", "VALUE" => "FIO"),
			"BUYER_PERSON_COMPANY_IIN" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_BIN" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_ADDRESS" => array("TYPE" => "PROPERTY", "VALUE" => "ADDRESS"),
			"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "PROPERTY", "VALUE" => "PHONE")
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams[mb_strtoupper($paySysName)."_COMMENT1"] = array("TYPE" => "ORDER", "VALUE" => "USER_DESCRIPTION");
	$psParams[mb_strtoupper($paySysName)."_COMMENT2"] = array("TYPE" => "", "VALUE" => "");
	$psParams[mb_strtoupper($paySysName)."_PATH_TO_STAMP"] = array("TYPE" => "", "VALUE" => "");
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"ACTION_FILE" => $paySysName,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PERSON_TYPE_ID" => $contactPTID,
		"PARAMS" => serialize($psParams),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
		"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE
	);
}
else if ($billPsLocalization === 'by')
{
	$psParams = array(
		"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
		"PAYMENT_DATE_PAY_BEFORE" => array("TYPE" => "ORDER", "VALUE" => "DATE_PAY_BEFORE"),
		"SELLER_COMPANY_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_NAME'),
		"SELLER_COMPANY_ADDRESS" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ADDRESS'),
		"SELLER_COMPANY_PHONE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PHONE'),
		"SELLER_COMPANY_INN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_INN'),
		"SELLER_COMPANY_BANK_ACCOUNT" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_ACCOUNT'),
		"SELLER_COMPANY_BANK_BIC" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_BIC'),
		"SELLER_COMPANY_BANK_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_NAME'),
		"SELLER_COMPANY_BANK_CITY" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_CITY'),
		"SELLER_COMPANY_DIRECTOR_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_DIRECTOR_NAME'),
		"SELLER_COMPANY_ACCOUNTANT_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ACCOUNTANT_NAME')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_COMPANY', 'bill', $billRqCountryId);
	}
	else
	{
		$buyerParams = array(
			"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "PROPERTY", "VALUE" => "COMPANY"),
			"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "PROPERTY", "VALUE" => "INN"),
			"BUYER_PERSON_COMPANY_ADDRESS" => array("TYPE" => "PROPERTY", "VALUE" => "COMPANY_ADR"),
			"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "PROPERTY", "VALUE" => "PHONE"),
			"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "PROPERTY", "VALUE" => "FAX"),
			"BUYER_PERSON_COMPANY_NAME_CONTACT" => array("TYPE" => "PROPERTY", "VALUE" => "CONTACT_PERSON"),
			"BUYER_PERSON_COMPANY_BANK_NAME" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_BANK_CITY" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_BANK_ACCOUNT" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_BANK_BIC" => array("TYPE" => "", "VALUE" => "")
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams[mb_strtoupper($paySysName)."_COMMENT1"] = array("TYPE" => "ORDER", "VALUE" => "USER_DESCRIPTION");
	$psParams[mb_strtoupper($paySysName)."_COMMENT2"] = array("TYPE" => "", "VALUE" => "");
	$psParams[mb_strtoupper($paySysName)."_PATH_TO_STAMP"] = array("TYPE" => "", "VALUE" => "");
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"ACTION_FILE" => $paySysName,
		"PERSON_TYPE_ID" => $companyPTID,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PARAMS" => serialize($psParams),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
		"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE
	);

	$psParams = array(
		"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
		"PAYMENT_DATE_PAY_BEFORE" => array("TYPE" => "ORDER", "VALUE" => "DATE_PAY_BEFORE"),
		"SELLER_COMPANY_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_NAME'),
		"SELLER_COMPANY_ADDRESS" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ADDRESS'),
		"SELLER_COMPANY_PHONE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PHONE'),
		"SELLER_COMPANY_INN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_INN'),
		"SELLER_COMPANY_BANK_ACCOUNT" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_ACCOUNT'),
		"SELLER_COMPANY_BANK_BIC" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_BIC'),
		"SELLER_COMPANY_BANK_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_NAME'),
		"SELLER_COMPANY_BANK_CITY" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_CITY'),
		"SELLER_COMPANY_DIRECTOR_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_DIRECTOR_NAME'),
		"SELLER_COMPANY_ACCOUNTANT_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ACCOUNTANT_NAME')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_CONTACT', 'bill', $billRqCountryId);
	}
	else
	{
		$buyerParams = array(
			"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "PROPERTY", "VALUE" => "FIO"),
			"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "PROPERTY", "VALUE" => "INN"),
			"BUYER_PERSON_COMPANY_ADDRESS" => array("TYPE" => "PROPERTY", "VALUE" => "ADDRESS"),
			"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "PROPERTY", "VALUE" => "PHONE"),
			"BUYER_PERSON_COMPANY_BANK_NAME" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_BANK_CITY" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_BANK_ACCOUNT" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_BANK_BIC" => array("TYPE" => "", "VALUE" => "")
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams[mb_strtoupper($paySysName)."_COMMENT1"] = array("TYPE" => "ORDER", "VALUE" => "USER_DESCRIPTION");
	$psParams[mb_strtoupper($paySysName)."_COMMENT2"] = array("TYPE" => "", "VALUE" => "");
	$psParams[mb_strtoupper($paySysName)."_PATH_TO_STAMP"] = array("TYPE" => "", "VALUE" => "");
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"ACTION_FILE" => $paySysName,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PERSON_TYPE_ID" => $contactPTID,
		"PARAMS" => serialize($psParams),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
		"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE
	);
}
else
{
	$psParams = array(
		"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
		"PAYMENT_DATE_PAY_BEFORE" => array("TYPE" => "ORDER", "VALUE" => "DATE_PAY_BEFORE"),
		"SELLER_COMPANY_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_NAME'),
		"SELLER_COMPANY_ADDRESS" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ADDRESS'),
		"SELLER_COMPANY_PHONE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PHONE'),
		"SELLER_COMPANY_INN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_INN'),
		"SELLER_COMPANY_KPP" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_KPP'),
		"SELLER_COMPANY_BANK_ACCOUNT" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_ACCOUNT'),
		"SELLER_COMPANY_BANK_ACCOUNT_CORR" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_ACCOUNT_CORR'),
		"SELLER_COMPANY_BANK_BIC" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_BIC'),
		"SELLER_COMPANY_BANK_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_NAME'),
		"SELLER_COMPANY_BANK_CITY" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_CITY'),
		"SELLER_COMPANY_DIRECTOR_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_DIRECTOR_NAME'),
		"SELLER_COMPANY_ACCOUNTANT_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ACCOUNTANT_NAME')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_COMPANY', 'bill', $billRqCountryId);
	}
	else
	{
		$buyerParams = array(
			"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "PROPERTY", "VALUE" => "COMPANY"),
			"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "PROPERTY", "VALUE" => "INN"),
			"BUYER_PERSON_COMPANY_ADDRESS" => array("TYPE" => "PROPERTY", "VALUE" => "COMPANY_ADR"),
			"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "PROPERTY", "VALUE" => "PHONE"),
			"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "PROPERTY", "VALUE" => "FAX"),
			"BUYER_PERSON_COMPANY_NAME_CONTACT" => array("TYPE" => "PROPERTY", "VALUE" => "CONTACT_PERSON")
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams[mb_strtoupper($paySysName)."_COMMENT1"] = array("TYPE" => "ORDER", "VALUE" => "USER_DESCRIPTION");
	$psParams[mb_strtoupper($paySysName)."_COMMENT2"] = array("TYPE" => "", "VALUE" => "");
	$psParams[mb_strtoupper($paySysName)."_PATH_TO_STAMP"] = array("TYPE" => "", "VALUE" => "");
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_UL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"ACTION_FILE" => $paySysName,
		"PERSON_TYPE_ID" => $companyPTID,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PARAMS" => serialize($psParams),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
		"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE,
	);

	$psParams = array(
		"PAYMENT_DATE_INSERT" => array("TYPE" => "ORDER", "VALUE" => "DATE_BILL_DATE"),
		"PAYMENT_DATE_PAY_BEFORE" => array("TYPE" => "ORDER", "VALUE" => "DATE_PAY_BEFORE"),
		"SELLER_COMPANY_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_NAME'),
		"SELLER_COMPANY_ADDRESS" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ADDRESS'),
		"SELLER_COMPANY_PHONE" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_PHONE'),
		"SELLER_COMPANY_INN" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_INN'),
		"SELLER_COMPANY_KPP" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_KPP'),
		"SELLER_COMPANY_BANK_ACCOUNT" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_ACCOUNT'),
		"SELLER_COMPANY_BANK_ACCOUNT_CORR" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_ACCOUNT_CORR'),
		"SELLER_COMPANY_BANK_BIC" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_BIC'),
		"SELLER_COMPANY_BANK_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_NAME'),
		"SELLER_COMPANY_BANK_CITY" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_BANK_CITY'),
		"SELLER_COMPANY_DIRECTOR_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_DIRECTOR_NAME'),
		"SELLER_COMPANY_ACCOUNTANT_NAME" => CCrmPaySystem::getDefaultMyCompanyParamValue('bill', $billRqCountryId, 'SELLER_COMPANY_ACCOUNTANT_NAME')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_CONTACT', 'bill', $billRqCountryId);
	}
	else
	{
		$buyerParams = array(
			"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "PROPERTY", "VALUE" => "FIO"),
			"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "PROPERTY", "VALUE" => "INN"),
			"BUYER_PERSON_COMPANY_ADDRESS" => array("TYPE" => "PROPERTY", "VALUE" => "ADDRESS"),
			"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "PROPERTY", "VALUE" => "PHONE"),
			"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "", "VALUE" => ""),
			"BUYER_PERSON_COMPANY_NAME_CONTACT" => array("TYPE" => "", "VALUE" => "")
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams[mb_strtoupper($paySysName)."_COMMENT1"] = array("TYPE" => "ORDER", "VALUE" => "USER_DESCRIPTION");
	$psParams[mb_strtoupper($paySysName)."_COMMENT2"] = array("TYPE" => "", "VALUE" => "");
	$psParams[mb_strtoupper($paySysName)."_PATH_TO_STAMP"] = array("TYPE" => "", "VALUE" => "");
	$arPaySystems[] = array(
		"NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"PSA_NAME" => Loc::getMessage("CRM_ORD_PS_BILL_FL"),
		"SORT" => 100,
		"DESCRIPTION" => "",
		"ACTION_FILE" => $paySysName,
		"RESULT_FILE" => "",
		"NEW_WINDOW" => "Y",
		"PERSON_TYPE_ID" => $contactPTID,
		"PARAMS" => serialize($psParams),
		"HAVE_PAYMENT" => "Y",
		"HAVE_ACTION" => "N",
		"HAVE_RESULT" => "N",
		"HAVE_PREPAY" => "N",
		"HAVE_RESULT_RECEIVE" => "N",
		"ENTITY_REGISTRY_TYPE" => REGISTRY_TYPE_CRM_INVOICE
	);
}
//<-- INVOICE PAYSYSTEMS

// QUOTE PAYSYSTEMS -->
$customPaySystemPath = COption::GetOptionString('sale', 'path2user_ps_files', '');
if($customPaySystemPath === '')
{
	$customPaySystemPath = BX_ROOT.'/php_interface/include/sale_payment/';
}

if (EntityPreset::isUTFMode())
	$quoteRqCountryId = CCrmPaySystem::getPresetCountryIdByPS('quote', $quotePsLocalization);
else
	$quoteRqCountryId = $rqCountryId;
$quotePaySysName = 'quote_'.$quotePsLocalization;
if ($newPSCompanyParams || $quotePsLocalization !== 'ua')
{
	$psParams = array(
		'DATE_INSERT' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_BILL_DATE'),
		'DATE_PAY_BEFORE' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_PAY_BEFORE')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_COMPANY', 'quote', $quoteRqCountryId);
	}
	else
	{
		$buyerParams = array(
			'BUYER_NAME' => array('TYPE' => 'PROPERTY', 'VALUE' => 'COMPANY'),
			'BUYER_INN' => array('TYPE' => 'PROPERTY', 'VALUE' => 'INN'),
			'BUYER_ADDRESS' => array('TYPE' => 'PROPERTY', 'VALUE' => 'COMPANY_ADR'),
			'BUYER_PHONE' => array('TYPE' => 'PROPERTY', 'VALUE' => 'PHONE'),
			'BUYER_FAX' => array('TYPE' => 'PROPERTY', 'VALUE' => 'FAX'),
			'BUYER_PAYER_NAME' => array('TYPE' => 'PROPERTY', 'VALUE' => 'CONTACT_PERSON'),
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams['COMMENT1'] = array('TYPE' => 'ORDER', 'VALUE' => 'USER_DESCRIPTION');
	foreach (CCrmPaySystem::getDefaultMyCompanyParams('quote', $quoteRqCountryId) as $paramName => $paramValue)
		$psParams[$paramName] = $paramValue;
	$arPaySystems[] = array(
		'NAME' => Loc::getMessage('CRM_QUOTE_PS_COMPANY'),
		'SORT' => 200,
		'DESCRIPTION' => '',
		'PSA_NAME' => Loc::getMessage('CRM_QUOTE_PS_COMPANY'),
		'ACTION_FILE' => $customPaySystemPath.$quotePaySysName,
		'RESULT_FILE' => '',
		'NEW_WINDOW' => 'Y',
		"PERSON_TYPE_ID" => $companyPTID,
		'PARAMS' => serialize($psParams),
		'HAVE_PAYMENT' => 'Y',
		'HAVE_ACTION' => 'N',
		'HAVE_RESULT' => 'N',
		'HAVE_PREPAY' => 'N',
		'HAVE_RESULT_RECEIVE' => 'N',
		'ENTITY_REGISTRY_TYPE' => REGISTRY_TYPE_CRM_QUOTE
	);
	$psParams = array(
		'DATE_INSERT' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_BILL_DATE'),
		'DATE_PAY_BEFORE' => array('TYPE' => 'ORDER', 'VALUE' => 'DATE_PAY_BEFORE')
	);
	if ($newPSCompanyParams)
	{
		$buyerParams = CCrmPaySystem::getDefaultBuyerParams('CRM_CONTACT', 'quote', $quoteRqCountryId);
	}
	else
	{
		$buyerParams = array(
			'BUYER_NAME' => array('TYPE' => 'PROPERTY', 'VALUE' => 'FIO'),
			'BUYER_INN' => array('TYPE' => 'PROPERTY', 'VALUE' => 'INN'),
			'BUYER_ADDRESS' => array('TYPE' => 'PROPERTY', 'VALUE' => 'ADDRESS'),
			'BUYER_PHONE' => array('TYPE' => 'PROPERTY', 'VALUE' => 'PHONE'),
			'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
			'BUYER_PAYER_NAME' => array('TYPE' => '', 'VALUE' => '')
		);
	}
	foreach	($buyerParams as $paramName => $paramValue)
	{
		$psParams[$paramName] = $paramValue;
	}
	unset($buyerParams);
	$psParams['COMMENT1'] = array('TYPE' => 'ORDER', 'VALUE' => 'USER_DESCRIPTION');
	foreach (CCrmPaySystem::getDefaultMyCompanyParams('quote', $quoteRqCountryId) as $paramName => $paramValue)
		$psParams[$paramName] = $paramValue;
	$arPaySystems[] = array(
		'NAME' => Loc::getMessage('CRM_QUOTE_PS_CONTACT'),
		'PSA_NAME' => Loc::getMessage('CRM_QUOTE_PS_CONTACT'),
		'SORT' => 300,
		'DESCRIPTION' => '',
		"PERSON_TYPE_ID" => $contactPTID,
		'ACTION_FILE' => $customPaySystemPath.$quotePaySysName,
		'RESULT_FILE' => '',
		'NEW_WINDOW' => 'Y',
		'PARAMS' => serialize($psParams),
		'HAVE_PAYMENT' => 'Y',
		'HAVE_ACTION' => 'N',
		'HAVE_RESULT' => 'N',
		'HAVE_PREPAY' => 'N',
		'HAVE_RESULT_RECEIVE' => 'N',
		'ENTITY_REGISTRY_TYPE' => REGISTRY_TYPE_CRM_QUOTE
	);
	unset($psParams, $paramName, $paramValue);
}
//<-- QUOTE PAYSYSTEMS

$isPaySystemsEmpty = true;
foreach($arPaySystems as $val)
{
	$dbSalePaySystem = \Bitrix\Sale\PaySystem\Manager::getList(
		array(
			'select' => array('ID', 'NAME'),
			'filter' => array('NAME' => $val['NAME'])
		)
	);

	if ($data = $dbSalePaySystem->fetch())
	{
		$isPaySystemsEmpty = false;
		$result = \Bitrix\Sale\PaySystem\Manager::update($data['ID'], $val);
		$id = $data['ID'];
	}
	else
	{
		$result = \Bitrix\Sale\PaySystem\Manager::add($val);
		$id = $result->getId();
	}

	$psParams = unserialize($val['PARAMS'], ['allowed_classes' => false]);
	foreach ($psParams as $code => $map)
	{
		$tmpMap['PROVIDER_KEY'] = $map['TYPE'];
		$tmpMap['PROVIDER_VALUE'] = $map['VALUE'];
		\Bitrix\Sale\BusinessValue::setMapping($code, 'PAYSYSTEM_'.$id, $val['PERSON_TYPE_ID'], $tmpMap, true);
	}

	if ($val['PERSON_TYPE_ID'])
	{
		$params = array(
			'filter' => array(
				"SERVICE_ID" => $id,
				"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
				"=CLASS_NAME" => '\\'.\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType::class
			)
		);

		$dbRes = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList($params);
		if (!$dbRes->fetch())
		{
			$fields = array(
				"SERVICE_ID" => $id,
				"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
				"SORT" => 100,
				"PARAMS" => array(
					'PERSON_TYPE_ID' => array($val['PERSON_TYPE_ID'])
				)
			);
			\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType::save($fields);
		}
	}

	$updateFields = array('PAY_SYSTEM_ID' => $id);

	if (mb_strpos($val['ACTION_FILE'], '/') !== false)
		$pathImg = $_SERVER["DOCUMENT_ROOT"].$val["ACTION_FILE"]."/logo.gif";
	else
		$pathImg = $_SERVER["DOCUMENT_ROOT"].\Bitrix\Sale\PaySystem\Manager::getPathToHandlerFolder($val["ACTION_FILE"])."/logo.gif";

	if (Bitrix\Main\IO\File::isFileExists($pathImg))
	{
		$updateFields['LOGOTIP'] = CFile::MakeFileArray($pathImg);

		if (array_key_exists("LOGOTIP", $updateFields) && is_array($updateFields["LOGOTIP"]))
			$updateFields["LOGOTIP"]["MODULE_ID"] = "sale";

		CFile::SaveForDB($updateFields, "LOGOTIP", "sale/paysystem/logotip");
	}

	$psParams['BX_PAY_SYSTEM_ID'] = array('TYPE' => '', 'VALUE' => $id);
	$updateFields['PARAMS'] = serialize($psParams);

	\Bitrix\Sale\PaySystem\Manager::update($id, $updateFields);
}

if ($isPaySystemsEmpty)
	\Bitrix\Crm\Requisite\Conversion\PSRequisiteConverter::skipConvert();

include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/crm/install/modules/catalog/module.php");

if (!Main\ModuleManager::isModuleInstalled('catalog'))
{
	$errMsg[] = Loc::getMessage('CRM_CATALOG_NOT_INSTALLED');
	$bError = true;
	return;
}

if (!Main\Loader::includeModule('catalog'))
{
	$errMsg[] = Loc::getMessage('CRM_CATALOG_NOT_INCLUDED');
	$bError = true;
	return;
}

if($billPsLocalization == "ru")
{
	$vat = Catalog\Model\Vat::getRow([
		'select' => [
			'ID',
		],
		'filter' => [
			'=EXCLUDE_VAT' => 'Y',
		],
	]);
	if ($vat === null)
	{
		Catalog\Model\Vat::add([
			'NAME' => Loc::getMessage('CRM_VAT_1'),
			'ACTIVE' => 'Y',
			'SORT' => 100,
			'EXCLUDE_VAT' => 'Y',
			'RATE' => null,
		]);
	}
	$vat = Catalog\Model\Vat::getRow([
		'select' => [
			'ID',
		],
		'filter' => [
			'=EXCLUDE_VAT' => 'N',
			'=RATE' => 0,
		],
	]);
	if ($vat === null)
	{
		Catalog\Model\Vat::add([
			'NAME' => Loc::getMessage('CRM_VAT_0'),
			'ACTIVE' => 'Y',
			'SORT' => 200,
			'EXCLUDE_VAT' => 'N',
			'RATE' => 0,
		]);
	}
	$vat = Catalog\Model\Vat::getRow([
		'select' => [
			'ID',
		],
		'filter' => [
			'=RATE' => 20,
		],
	]);
	if ($vat === null)
	{
		Catalog\Model\Vat::add([
			'NAME' => Loc::getMessage('CRM_VAT_21'),
			'ACTIVE' => 'Y',
			'SORT' => 300,
			'EXCLUDE_VAT' => 'N',
			'RATE' => 20,
		]);
	}
}
elseif($billPsLocalization == "kz")
{
	$vat = Catalog\Model\Vat::getRow([
		'select' => [
			'ID',
		],
		'filter' => [
			'=EXCLUDE_VAT' => 'Y',
		],
	]);
	if ($vat === null)
	{
		Catalog\Model\Vat::add([
			'NAME' => Loc::getMessage('CRM_VAT_1'),
			'ACTIVE' => 'Y',
			'SORT' => 100,
			'EXCLUDE_VAT' => 'Y',
			'RATE' => null,
		]);
	}
	$vat = Catalog\Model\Vat::getRow([
		'select' => [
			'ID',
		],
		'filter' => [
			'=RATE' => 12,
		],
	]);
	if ($vat === null)
	{
		Catalog\Model\Vat::add([
			'NAME' => Loc::getMessage('CRM_VAT_3'),
			'ACTIVE' => 'Y',
			'SORT' => 200,
			'EXCLUDE_VAT' => 'N',
			'RATE' => 12,
		]);
	}
}

// get default vat
$defCatVatId = 0;
$vat = Catalog\Model\Vat::getRow([
	'select' => [
		'ID',
		'SORT',
	],
	'order' => [
		'SORT' => 'ASC',
	],
]);
if ($vat !== null)
{
	$defCatVatId = (int)$vat['ID'];
}

$arActiveLangs = array();
$languageIterator = Main\Localization\LanguageTable::getList(array(
	'select' => array('ID'),
	'filter' => array('=ACTIVE' => 'Y')
));
while ($language = $languageIterator->fetch())
{
	$arActiveLangs[] = $language['ID'];
}
unset($language, $languageIterator);

// create base price
$basePriceId = 0;
$basePrice = array();
$dbRes = CCatalogGroup::GetListEx(array(), array("BASE" => "Y"), false, false, array('ID'));
if(!($basePrice = $dbRes->Fetch()))
{
	$catalogGroupLangFiles = array();
	foreach ($arActiveLangs as &$language)
		$catalogGroupLangFiles[$language] = Loc::loadLanguageFile(__FILE__, $language);
	$arFields = array();
	$arFields["USER_LANG"] = array();
	foreach ($arActiveLangs as &$language)
	{
		if (isset($catalogGroupLangFiles[$language]))
			$arFields["USER_LANG"][$language] = $catalogGroupLangFiles[$language]['CRM_BASE_PRICE_NAME'];
	}
	unset($language);
	unset($catalogGroupLangFiles);
	$arFields["BASE"] = "Y";
	$arFields["SORT"] = 100;
	$arFields["NAME"] = "BASE";
	$arFields["XML_ID"] = "BASE";
	$arFields["USER_GROUP"] = array(1, 2);
	$arFields["USER_GROUP_BUY"] = array(1, 2);
	$basePriceId = CCatalogGroup::Add($arFields);
	if ($basePriceId <= 0)
	{
		$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_003');
		$bError = true;
		return;
	}
}
if ($basePriceId <= 0 && isset($basePrice['ID']) && $basePrice['ID'] > 0) $basePriceId = $basePrice['ID'];
unset($basePrice, $dbRes);

$arCatalogId = array();
$dbCatalogList = CCrmCatalog::GetList();
while ($arCatalog = $dbCatalogList->Fetch())
	$arCatalogId[] = $arCatalog['ID'];
$defCatalogId = CCrmCatalog::EnsureDefaultExists();
if ($defCatalogId > 0)
{
	if (!in_array($defCatalogId, $arCatalogId))
		$arCatalogId[] = $defCatalogId;
}
else
{
	$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_001');
	$bError = true;
	return;
}
if (!empty($arCatalogId) && !$bError)
{
	$CCatalog = new CCatalog();
	if ($CCatalog)
	{
		foreach ($arCatalogId as $catalogId)
		{
			$arFields = array(
				'IBLOCK_ID' => $catalogId,
				'CATALOG' => 'Y'
			);
			if ($defCatVatId > 0)
				$arFields['VAT_ID'] = $defCatVatId;

			// add crm iblock to catalog
			$dbRes = $CCatalog->GetList(array(), array('ID' => $catalogId), false, false, array('ID'));
			if (!$dbRes->Fetch())    // if catalog iblock is not exists
			{
				if ($CCatalog->Add($arFields))
				{
					COption::SetOptionString('catalog', 'save_product_without_price', 'Y');
					COption::SetOptionString('catalog', 'default_can_buy_zero', 'Y');
				}
				else
				{
					$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_002');
					$bError = true;
					return;
				}
			}
			unset($dbRes);
		}

		// transfer crm products to catalog
		if ($basePriceId > 0)
		{
			if (COption::GetOptionString('crm', '~CRM_INVOICE_PRODUCTS_CONVERTED_12_5_7', 'N') !== 'Y')
			{
				if (
					$DB->TableExists('b_crm_product') &&
					$DB->TableExists('b_catalog_product') &&
					$DB->TableExists('b_catalog_price') &&
					$DB->TableExists('b_catalog_group')
				)
				{
					// update iblock element xml_id
					$local_err = 0;

					$strSql = $DB->PrepareUpdateJoin('b_iblock_element', [
							'XML_ID' => $DB->Concat("COALESCE(CP.ORIGINATOR_ID, '')", "'#'", "COALESCE(CP.ORIGIN_ID, '')"),
						],
						[
							['b_crm_product CP', 'b_iblock_element.ID = CP.ID'],
						],
						""
					);

					if (!$DB->Query($strSql, true))
						$local_err = 1;

					if (!$local_err)
					{
						// insert catalog products
						$strSql = PHP_EOL.
							'INSERT INTO b_catalog_product (ID, QUANTITY, QUANTITY_TRACE, RECUR_SCHEME_LENGTH, RECUR_SCHEME_TYPE, VAT_ID, VAT_INCLUDED, CAN_BUY_ZERO)'.PHP_EOL.
							"\t".'SELECT CP.ID, 0, \'D\', 0, \'D\', '.intval($defCatVatId).', \'N\', \'D\' FROM b_crm_product CP'.PHP_EOL.
							"\t".'WHERE ID NOT IN (SELECT CTP.ID FROM b_catalog_product CTP)'.PHP_EOL;

						if (!$DB->Query($strSql, true))
							$local_err = 2;
					}

					if (!$local_err)
					{
						//set base prices
						$strSql = PHP_EOL.
							'INSERT INTO b_catalog_price (PRODUCT_ID, CATALOG_GROUP_ID, PRICE, CURRENCY)'.PHP_EOL.
							"\t".'SELECT CP.ID, '.$basePriceId.', CP.PRICE, CP.CURRENCY_ID FROM b_crm_product CP'.PHP_EOL.
							"\t".'WHERE ID NOT IN (SELECT CPR.PRODUCT_ID FROM b_catalog_price CPR WHERE CPR.CATALOG_GROUP_ID = '.$basePriceId.')'.PHP_EOL;

						if (!$DB->Query($strSql, true))
							$local_err = 3;
					}

					if ($local_err)
					{
						$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_006').' ('.$local_err.')';
						$bError = true;
						return;
					}
					unset($local_err);

					COption::SetOptionString('crm', '~CRM_INVOICE_PRODUCTS_CONVERTED_12_5_7', 'Y');
				}
				else
				{
					$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_005');
					$bError = true;
					return;
				}
			}
		}
		else
		{
			$errMsg[] = Loc::getMessage('CRM_UPDATE_ERR_004');
			$bError = true;
			return;
		}
	}
}

if(Main\ModuleManager::isModuleInstalled('intranet'))
{
	if (!Main\Config\Option::get("sale", "sale_ps_success_path", false))
	{
		Main\Config\Option::set("sale", "sale_ps_success_path", "/pub/payment_result.php?action=success");
	}

	if (!Main\Config\Option::get("sale", "sale_ps_fail_path", false))
	{
		Main\Config\Option::set("sale", "sale_ps_fail_path", "/pub/payment_result.php?action=fail");
	}
}