<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use	Bitrix\Sale\BusinessValue;

global $DB;

$firstPass = (COption::GetOptionString('crm', '~CRM_INVOICE_EXCH1C_UPDATE_12_5_17', 'N') !== 'Y');

if ($firstPass)
{
	$iblockType = CCrmCatalog::GetCatalogTypeID();
	$catalogId = CCrmCatalog::EnsureDefaultExists();
	$currentLocalization = LANGUAGE_ID;
	$currentSiteID = SITE_ID;
	if (defined("ADMIN_SECTION"))
	{
		$site = new CSite();
		$obSite = $site->GetList("def", "desc", array("ACTIVE" => "Y"));
		if ($arSite = $obSite->Fetch())
		{
			$currentLocalization = $arSite["LANGUAGE_ID"];
			$currentSiteID = $arSite["LID"];
		}
		unset($arSite, $obSite, $site);
	}

	// update catalog xml_id for exchange with 1C
	$ib = new CIBlock();
	$ib->Update($catalogId, array('XML_ID' => 'FUTURE-1C-CATALOG'));
	unset($ib);

	$arExch1cOptions = array(
		array('catalog', '1CE_ELEMENTS_PER_STEP', '100'),
		array('catalog', '1CE_GROUP_PERMISSIONS', ''),
		array('catalog', '1CE_IBLOCK_ID', $catalogId),
		array('catalog', '1CE_INTERVAL', '30'),
		array('catalog', '1CE_USE_ZIP', 'Y'),
		array('catalog', '1C_DETAIL_HEIGHT', '300'),
		array('catalog', '1C_DETAIL_RESIZE', 'N'),
		array('catalog', '1C_DETAIL_WIDTH', '300'),
		array('catalog', '1C_ELEMENT_ACTION', 'D'),
		array('catalog', '1C_FILE_SIZE_LIMIT', '204800'),
		array('catalog', '1C_FORCE_OFFERS', 'N'),
		array('catalog', '1C_GENERATE_PREVIEW', 'N'),
		array('catalog', '1C_GROUP_PERMISSIONS', ''),
		array('catalog', '1C_IBLOCK_TYPE', $iblockType),
		array('catalog', '1C_INTERVAL', '30'),
		array('catalog', '1C_PREVIEW_HEIGHT', '100'),
		array('catalog', '1C_PREVIEW_WIDTH', '100'),
		array('catalog', '1C_SECTION_ACTION', 'D'),
		array('catalog', '1C_SITE_LIST', $currentSiteID),
		array('catalog', '1C_SKIP_ROOT_SECTION', 'N'),
		array('catalog', '1C_TRANSLIT_ON_ADD', 'N'),
		array('catalog', '1C_TRANSLIT_ON_UPDATE', 'N'),
		array('catalog', '1C_USE_CRC', 'Y'),
		array('catalog', '1C_USE_IBLOCK_PICTURE_SETTINGS', 'N'),
		array('catalog', '1C_USE_IBLOCK_TYPE_ID', 'N'),
		array('catalog', '1C_USE_OFFERS', 'N'),
		array('catalog', '1C_USE_ZIP', 'Y'),
		array('sale', '1C_EXPORT_ALLOW_DELIVERY_ORDERS', 'N'),
		array('sale', '1C_EXPORT_FINAL_ORDERS', ''),
		array('sale', '1C_EXPORT_PAYED_ORDERS', 'N'),
		array('sale', '1C_FINAL_STATUS_ON_DELIVERY', ''),
		array('sale', '1C_REPLACE_CURRENCY', ($currentLocalization === 'ru') ? GetMessage('CRM_EXCH1C_UPDATE_PS_BILL_RUB') : ' '),
		array('sale', '1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX', 'CRM_'),
		array('sale', '1C_SALE_GROUP_PERMISSIONS', ''),
		array('sale', '1C_SALE_SITE_LIST', $currentSiteID),
		array('sale', '1C_SALE_USE_ZIP', 'Y')
	);
	foreach ($arExch1cOptions as $optionInfo)
		COption::SetOptionString($optionInfo[0], $optionInfo[1], $optionInfo[2]);

	// set sale option "Upon receipt of payment, change order status to ..."
	COption::SetOptionString("sale", "status_on_paid", "P");

	COption::SetOptionString('crm', 'crm_exch1c_enable', 'N');

	$arBaseCatalogGroup = CCatalogGroup::GetBaseGroup();
	$priceTypeId = intval($arBaseCatalogGroup['ID']);
	unset($arBaseCatalogGroup);
	COption::SetOptionInt('crm', 'selected_catalog_group_id', $priceTypeId);

	// convert PREVIEW_TEXT field to DETAIL_TEXT
	if (!empty($iblockType))
	{
		$strSql = 'UPDATE b_iblock_element '.
			'SET DETAIL_TEXT = PREVIEW_TEXT, DETAIL_TEXT_TYPE = PREVIEW_TEXT_TYPE '.
			'WHERE IBLOCK_ID IN (SELECT B.ID FROM b_iblock B WHERE B.IBLOCK_TYPE_ID = \''.$iblockType.'\') '.
			'AND PREVIEW_TEXT IS NOT NULL AND DETAIL_TEXT IS NULL';
		$DB->Query($strSql, true);
	}

	if (intval($catalogId) > 0)
	{
		$strSql = 'UPDATE b_iblock_element SET XML_ID = ID WHERE IBLOCK_ID = '.intval($catalogId).' AND (XML_ID = \'#\' OR XML_ID LIKE \'%#CRM_DEMO_%\')';
		$DB->Query($strSql, true);
	}
}

// fill invoice export profiles
$arPersonTypeIds = CCrmPaySystem::getPersonTypeIDs();
if (isset($arPersonTypeIds['COMPANY']) && isset($arPersonTypeIds['CONTACT']))
{
	$arPersonTypeIdCode = array(
		$arPersonTypeIds['COMPANY'] => 'COMPANY',
		$arPersonTypeIds['CONTACT'] => 'CONTACT'
	);
	$arProps = array();
	$dbRes = \Bitrix\Crm\Invoice\Property::getList([
		'select' => ['ID', 'PERSON_TYPE_ID', 'CODE', 'SORT'],
		'filter' => ['=ACTIVE' => 'Y'],
		'order' => ['SORT' => 'ASC', 'ID' => 'ASC']
	]);

	while ($prop = $dbRes->fetch())
	{
		if ($prop['PERSON_TYPE_ID'] == $arPersonTypeIds['COMPANY'] || $prop['PERSON_TYPE_ID'] == $arPersonTypeIds['CONTACT'])
			$arProps[$arPersonTypeIdCode[$prop['PERSON_TYPE_ID']]][$prop['CODE']] = $prop['ID'];
	}
	unset($dbRes, $prop);

	if (count($arProps) > 0 &&
		isset($arProps['CONTACT']) && count($arProps['CONTACT']) > 0 &&
		isset($arProps['COMPANY']) && count($arProps['COMPANY']) > 0)
	{
		// contact
		$dbr = CSaleExport::GetList(array('ID' => 'DESC'), array('PERSON_TYPE_ID' => $arPersonTypeIds['CONTACT']));
		$contactProfileExists = ($dbr && $dbr->Fetch()) ? true : false;
		unset($dbr);
		if (!$contactProfileExists)
		{
			$params = array();
			if (isset($arProps['CONTACT']['FIO']))
			{
				$params['AGENT_NAME'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['CONTACT']['FIO']);
				$params['FULL_NAME'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['CONTACT']['FIO']);
			}
			if (isset($arProps['CONTACT']['ADDRESS']))
			{
				$params['ADDRESS_FULL'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['CONTACT']['ADDRESS']);
				$params['STREET'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['CONTACT']['ADDRESS']);
			}
			if (isset($arProps['CONTACT']['ZIP']))
				$params['INDEX'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['CONTACT']['ZIP']);
			if (isset($arProps['CONTACT']['LOCATION']))
			{
				$params['COUNTRY'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['CONTACT']['LOCATION'].'_COUNTRY');
				$params['CITY'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['CONTACT']['LOCATION'].'_CITY');
			}
			if (isset($arProps['CONTACT']['EMAIL']))
				$params['EMAIL'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['CONTACT']['EMAIL']);
			if (isset($arProps['CONTACT']['CONTACT_PERSON']))
				$params['CONTACT_PERSON'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['CONTACT']['CONTACT_PERSON']);
			$params['IS_FIZ'] = 'Y';
			$val = serialize($params);
			unset($params);

			$allPersonTypes = BusinessValue::getPersonTypes(true);
			$personTypeId = $arPersonTypeIds['CONTACT'];
			$domain = BusinessValue::INDIVIDUAL_DOMAIN;

			if(!isset($allPersonTypes[$personTypeId]['DOMAIN']))
			{
				$r = Bitrix\Sale\Internals\BusinessValuePersonDomainTable::add(array(
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
							'ITEM_ID' => "exch1c.Contact.Add:".$personTypeId,
							'DESCRIPTION' => 'Unable to set person type "'.$personTypeId.'" domain'."\n".implode("\n", $r->getErrorMessages()),
					));
				}

			}

			CSaleExport::Add(array('PERSON_TYPE_ID' => $arPersonTypeIds['CONTACT'], 'VARS' => $val));
		}

		// company
		$dbr = CSaleExport::GetList(array('ID' => 'DESC'), array('PERSON_TYPE_ID' => $arPersonTypeIds['COMPANY']));
		$companyProfileExists = ($dbr && $dbr->Fetch()) ? true : false;
		unset($dbr);
		if (!$companyProfileExists)
		{
			$params = array();
			if (isset($arProps['COMPANY']['COMPANY']))
			{
				$params['AGENT_NAME'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['COMPANY']);
				$params['FULL_NAME'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['COMPANY']);
			}
			else if (isset($arProps['COMPANY']['COMPANY_NAME']))    // ua company name hack
			{
				$params['AGENT_NAME'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['COMPANY_NAME']);
				$params['FULL_NAME'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['COMPANY_NAME']);
			}
			if (isset($arProps['COMPANY']['COMPANY_ADR']))
			{
				$params['ADDRESS_FULL'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['COMPANY_ADR']);
				$params['STREET'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['COMPANY_ADR']);
			}
			if (isset($arProps['COMPANY']['LOCATION']))
			{
				$params['COUNTRY'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['LOCATION'].'_COUNTRY');
				$params['CITY'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['LOCATION'].'_CITY');
				$params['F_COUNTRY'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['LOCATION'].'_COUNTRY');
				$params['F_CITY'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['LOCATION'].'_CITY');
			}
			if (isset($arProps['COMPANY']['INN']))
				$params['INN'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['INN']);
			if (isset($arProps['COMPANY']['KPP']))
				$params['KPP'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['KPP']);
			if (isset($arProps['COMPANY']['PHONE']))
				$params['PHONE'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['PHONE']);
			if (isset($arProps['COMPANY']['EMAIL']))
				$params['EMAIL'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['EMAIL']);
			if (isset($arProps['COMPANY']['CONTACT_PERSON']))
				$params['CONTACT_PERSON'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['CONTACT_PERSON']);
			if (isset($arProps['COMPANY']['ADDRESS']))
			{
				$params['F_ADDRESS_FULL'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['ADDRESS']);
				$params['F_STREET'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['ADDRESS']);
			}
			if (isset($arProps['COMPANY']['ZIP']))
			{
				$params['F_INDEX'] = array('TYPE' => 'PROPERTY', 'VALUE' => $arProps['COMPANY']['ZIP']);
			}
			$params['IS_FIZ'] = 'N';
			$val = serialize($params);
			unset($params);

			$allPersonTypes = BusinessValue::getPersonTypes(true);
			$personTypeId = $arPersonTypeIds['COMPANY'];
			$domain = BusinessValue::ENTITY_DOMAIN;

			if(!isset($allPersonTypes[$personTypeId]['DOMAIN']))
			{
				$r = Bitrix\Sale\Internals\BusinessValuePersonDomainTable::add(array(
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
							'ITEM_ID' => "exch1c.Company.Add:".$personTypeId,
							'DESCRIPTION' => 'Unable to set person type "'.$personTypeId.'" domain'."\n".implode("\n", $r->getErrorMessages()),
					));
				}
			}

			CSaleExport::Add(array('PERSON_TYPE_ID' => $arPersonTypeIds['COMPANY'], 'VARS' => $val));
		}
	}
}
