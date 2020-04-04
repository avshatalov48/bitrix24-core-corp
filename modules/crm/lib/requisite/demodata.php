<?php
namespace Bitrix\Crm\Requisite;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DemoData
{
	public static function getRequisiteImpotDemoData($entityTypeIds = array(), $countryIds = array())
	{
		$requisiteDemoData = array();

		$demoCountryIds = array(1, 4, 6, 14, 46, 122);
		$demoEntityTypeIds = array(\CCrmOwnerType::Contact, \CCrmOwnerType::Company);

		if (is_array($countryIds) && !empty($countryIds))
		{
			$idsMap = array();
			foreach ($countryIds as $countryId)
			{
				$countryId = (int)$countryId;
				if (in_array($countryId, $demoCountryIds, true) && !isset($idsMap[$countryId]))
					$idsMap[$countryId] = true;
			}
			$countryIds = array_keys($idsMap);
			unset($idsMap);
		}
		if (!is_array($countryIds) || empty($countryIds))
		{
			$countryIds = $demoCountryIds;
		}

		if (is_array($entityTypeIds) && !empty($entityTypeIds))
		{
			$idsMap = array();
			foreach ($entityTypeIds as $entityTypeId)
			{
				$entityTypeId = (int)$entityTypeId;
				if (in_array($entityTypeId, $demoEntityTypeIds, true) && !isset($idsMap[$entityTypeId]))
				{
					$idsMap[$entityTypeId] = true;
				}
			}
			$entityTypeIds = array_keys($idsMap);
			unset($idsMap);
		}
		if (!is_array($entityTypeIds) || empty($entityTypeIds))
		{
			$entityTypeIds = array(\CCrmOwnerType::Contact, \CCrmOwnerType::Company);
		}

		$langId = '';
		$prevLangId = '';
		$messages = array();
		foreach ($countryIds as $countryId)
		{
			switch ($countryId)
			{
				case 1:                // ru
					$langId = 'ru';
					break;
				case 4:                // by
					$langId = 'by';
					break;
				case 6:                // kz
					$langId = 'kz';
					break;
				case 14:               // ua
					$langId = 'ua';
					break;
				case 46:               // de
					$langId = 'de';
					break;
				case 122:              // us
					$langId = 'en';
					break;
			}

			if (!empty($langId))
			{
				if ($langId !== $prevLangId)
				{
					$messages = Loc::loadLanguageFile(
						Application::getDocumentRoot().'/bitrix/modules/crm/lib/requisite/demodata.php',
						$langId
					);
					$prevLangId = $langId;
				}

				switch ($countryId)
				{
					case 1:    // ru
						$requisiteDemoData[$countryId] = array();
						foreach ($entityTypeIds as $entityTypeId)
						{
							switch ($entityTypeId)
							{
								case \CCrmOwnerType::Contact:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_NAME'),
											'RQ_LAST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_LAST_NAME'),
											'RQ_FIRST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_FIRST_NAME'),
											'RQ_SECOND_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_SECOND_NAME'),
											'RQ_IDENT_DOC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_IDENT_DOC'),
											'RQ_IDENT_DOC_SER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_IDENT_DOC_SER'),
											'RQ_IDENT_DOC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_IDENT_DOC_NUM'),
											'RQ_IDENT_DOC_ISSUED_BY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_IDENT_DOC_ISSUED_BY'),
											'RQ_IDENT_DOC_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_IDENT_DOC_DATE'),
											'RQ_IDENT_DOC_DEP_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_IDENT_DOC_DEP_CODE'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_RQ_ADDR_RG_COUNTRY_CODE')
												),
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD1_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD1_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD1_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD1_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD1_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD1_RQ_SWIFT')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD2_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD2_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD2_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD2_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD2_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ1_BD2_RQ_SWIFT')
												)
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_NAME'),
											'RQ_LAST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_LAST_NAME'),
											'RQ_FIRST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_FIRST_NAME'),
											'RQ_SECOND_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_SECOND_NAME'),
											'RQ_IDENT_DOC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_IDENT_DOC'),
											'RQ_IDENT_DOC_SER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_IDENT_DOC_SER'),
											'RQ_IDENT_DOC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_IDENT_DOC_NUM'),
											'RQ_IDENT_DOC_ISSUED_BY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_IDENT_DOC_ISSUED_BY'),
											'RQ_IDENT_DOC_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_IDENT_DOC_DATE'),
											'RQ_IDENT_DOC_DEP_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_IDENT_DOC_DEP_CODE'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_RQ_ADDR_RG_COUNTRY_CODE')
												),
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD1_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD1_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD1_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD1_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD1_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD1_RQ_SWIFT')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD2_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD2_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD2_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD2_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD2_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_C_RQ2_BD2_RQ_SWIFT')
												)
											)
										)
									);
									break;
								case \CCrmOwnerType::Company:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_NAME'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_INN'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_COMPANY_NAME'),
											'RQ_COMPANY_FULL_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_COMPANY_FULL_NAME'),
											'RQ_OGRN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_OGRN'),
											'RQ_KPP' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_KPP'),
											'RQ_COMPANY_REG_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_COMPANY_REG_DATE'),
											'RQ_OKPO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_OKPO'),
											'RQ_OKTMO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_OKTMO'),
											'RQ_DIRECTOR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_DIRECTOR'),
											'RQ_ACCOUNTANT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ACCOUNTANT'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_RQ_ADDR_LG_COUNTRY_CODE')
												),
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD1_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD1_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD1_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD1_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD1_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD1_RQ_SWIFT'),
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD2_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD2_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD2_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD2_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD2_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ1_BD2_RQ_SWIFT'),
												),
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_NAME'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_INN'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_COMPANY_NAME'),
											'RQ_COMPANY_FULL_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_COMPANY_FULL_NAME'),
											'RQ_OGRN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_OGRN'),
											'RQ_KPP' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_KPP'),
											'RQ_COMPANY_REG_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_COMPANY_REG_DATE'),
											'RQ_OKPO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_OKPO'),
											'RQ_OKTMO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_OKTMO'),
											'RQ_DIRECTOR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_DIRECTOR'),
											'RQ_ACCOUNTANT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ACCOUNTANT'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_RQ_ADDR_LG_COUNTRY_CODE')
												),
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD1_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD1_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD1_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD1_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD1_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD1_RQ_SWIFT')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD2_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD2_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD2_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD2_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD2_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_RU_CO_RQ2_BD2_RQ_SWIFT')
												),
											)
										)
									);
									break;
							}
						}
						break;
					case 4:    // by
						$requisiteDemoData[$countryId] = array();
						foreach ($entityTypeIds as $entityTypeId)
						{
							switch ($entityTypeId)
							{
								case \CCrmOwnerType::Contact:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_NAME'),
											'RQ_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_NAME'),
											'RQ_IDENT_DOC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_IDENT_DOC'),
											'RQ_IDENT_DOC_SER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_IDENT_DOC_SER'),
											'RQ_IDENT_DOC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_IDENT_DOC_NUM'),
											'RQ_IDENT_DOC_PERS_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_IDENT_DOC_PERS_NUM'),
											'RQ_IDENT_DOC_ISSUED_BY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_IDENT_DOC_ISSUED_BY'),
											'RQ_IDENT_DOC_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_IDENT_DOC_DATE'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD1_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD1_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD1_RQ_COR_ACC_NUM'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD1_RQ_BIC'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD1_RQ_ACC_CURRENCY'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD1_RQ_SWIFT'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD1_RQ_BANK_ADDR')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD2_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD2_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD2_RQ_COR_ACC_NUM'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD2_RQ_BIC'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD2_RQ_ACC_CURRENCY'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD2_RQ_SWIFT'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ1_BD2_RQ_BANK_ADDR')
												)
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_NAME'),
											'RQ_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_NAME'),
											'RQ_IDENT_DOC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_IDENT_DOC'),
											'RQ_IDENT_DOC_SER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_IDENT_DOC_SER'),
											'RQ_IDENT_DOC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_IDENT_DOC_NUM'),
											'RQ_IDENT_DOC_PERS_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_IDENT_DOC_PERS_NUM'),
											'RQ_IDENT_DOC_ISSUED_BY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_IDENT_DOC_ISSUED_BY'),
											'RQ_IDENT_DOC_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_IDENT_DOC_DATE'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD1_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD1_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD1_RQ_COR_ACC_NUM'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD1_RQ_BIC'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD1_RQ_ACC_CURRENCY'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD1_RQ_SWIFT'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD1_RQ_BANK_ADDR')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD2_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD2_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD2_RQ_COR_ACC_NUM'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD2_RQ_BIC'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD2_RQ_ACC_CURRENCY'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD2_RQ_SWIFT'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_C_RQ2_BD2_RQ_BANK_ADDR')
												)
											)
										)
									);
									break;
								case \CCrmOwnerType::Company:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_NAME'),
											'RQ_COMPANY_FULL_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_COMPANY_FULL_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_COMPANY_NAME'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_INN'),
											'RQ_COMPANY_REG_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_COMPANY_REG_DATE'),
											'RQ_OKPO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_OKPO'),
											'RQ_DIRECTOR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_DIRECTOR'),
											'RQ_BASE_DOC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_BASE_DOC'),
											'RQ_ACCOUNTANT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ACCOUNTANT'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_RQ_ADDR_LG_COUNTRY_CODE')
												),
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD1_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD1_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD1_RQ_COR_ACC_NUM'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD1_RQ_BIC'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD1_RQ_ACC_CURRENCY'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD1_RQ_SWIFT'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD1_RQ_BANK_ADDR')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD2_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD2_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD2_RQ_COR_ACC_NUM'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD2_RQ_BIC'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD2_RQ_ACC_CURRENCY'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD2_RQ_SWIFT'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ1_BD2_RQ_BANK_ADDR')
												)
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_NAME'),
											'RQ_COMPANY_FULL_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_COMPANY_FULL_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_COMPANY_NAME'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_INN'),
											'RQ_COMPANY_REG_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_COMPANY_REG_DATE'),
											'RQ_OKPO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_OKPO'),
											'RQ_DIRECTOR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_DIRECTOR'),
											'RQ_BASE_DOC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_BASE_DOC'),
											'RQ_ACCOUNTANT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ACCOUNTANT'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_RQ_ADDR_LG_COUNTRY_CODE')
												),
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD1_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD1_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD1_RQ_COR_ACC_NUM'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD1_RQ_BIC'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD1_RQ_ACC_CURRENCY'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD1_RQ_SWIFT'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD1_RQ_BANK_ADDR')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD2_RQ_BIK'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD2_RQ_ACC_NUM'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD2_RQ_COR_ACC_NUM'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD2_RQ_BIC'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD2_RQ_ACC_CURRENCY'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD2_RQ_SWIFT'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_BY_CO_RQ2_BD2_RQ_BANK_ADDR')
												)
											)
										)
									);
									break;
							}
						}
						break;
					case 6:    // kz
						$requisiteDemoData[$countryId] = array();
						foreach ($entityTypeIds as $entityTypeId)
						{
							switch ($entityTypeId)
							{
								case \CCrmOwnerType::Contact:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_NAME'),
											'RQ_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_NAME'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_INN'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD1_RQ_BIK'),
													'RQ_IIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD1_RQ_IIK'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD1_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD1_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD1_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD1_RQ_SWIFT')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD2_RQ_BIK'),
													'RQ_IIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD2_RQ_IIK'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD2_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD2_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD2_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ1_BD2_RQ_SWIFT')
												),
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_NAME'),
											'RQ_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_NAME'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_INN'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD1_RQ_BIK'),
													'RQ_IIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD1_RQ_IIK'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD1_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD1_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD1_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD1_RQ_SWIFT')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD2_RQ_BIK'),
													'RQ_IIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD2_RQ_IIK'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD2_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD2_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD2_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_C_RQ2_BD2_RQ_SWIFT')
												),
											)
										)
									);
									break;
								case \CCrmOwnerType::Company:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_NAME'),
											'RQ_COMPANY_FULL_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_COMPANY_FULL_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_COMPANY_NAME'),
											'RQ_OKPO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_OKPO'),
											'RQ_KBE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_KBE'),
											'RQ_IIN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_IIN'),
											'RQ_BIN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_BIN'),
											'RQ_VAT_CERT_SER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_VAT_CERT_SER'),
											'RQ_VAT_CERT_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_VAT_CERT_NUM'),
											'RQ_VAT_CERT_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_VAT_CERT_DATE'),
											'RQ_RESIDENCE_COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_RESIDENCE_COUNTRY'),
											'RQ_CEO_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_CEO_NAME'),
											'RQ_CEO_WORK_POS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_CEO_WORK_POS'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_RQ_ADDR_LG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD1_RQ_BIK'),
													'RQ_IIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD1_RQ_IIK'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD1_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD1_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD1_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD1_RQ_SWIFT')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD2_RQ_BIK'),
													'RQ_IIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD2_RQ_IIK'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD2_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD2_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD2_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ1_BD2_RQ_SWIFT')
												),
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_NAME'),
											'RQ_COMPANY_FULL_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_COMPANY_FULL_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_COMPANY_NAME'),
											'RQ_OKPO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_OKPO'),
											'RQ_KBE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_KBE'),
											'RQ_IIN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_IIN'),
											'RQ_BIN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_BIN'),
											'RQ_VAT_CERT_SER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_VAT_CERT_SER'),
											'RQ_VAT_CERT_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_VAT_CERT_NUM'),
											'RQ_VAT_CERT_DATE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_VAT_CERT_DATE'),
											'RQ_RESIDENCE_COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_RESIDENCE_COUNTRY'),
											'RQ_CEO_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_CEO_NAME'),
											'RQ_CEO_WORK_POS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_CEO_WORK_POS'),
											'RQ_ADDR' => array(
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_RQ_ADDR_LG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD1_RQ_BIK'),
													'RQ_IIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD1_RQ_IIK'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD1_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD1_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD1_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD1_RQ_SWIFT')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD2_RQ_BIK'),
													'RQ_IIK' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD2_RQ_IIK'),
													'RQ_COR_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD2_RQ_COR_ACC_NUM'),
													'RQ_ACC_CURRENCY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD2_RQ_ACC_CURRENCY'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD2_RQ_BANK_ADDR'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_KZ_CO_RQ2_BD2_RQ_SWIFT')
												),
											)
										)
									);
									break;
							}
						}
						break;
					case 14:    // ua
						$requisiteDemoData[$countryId] = array();
						foreach ($entityTypeIds as $entityTypeId)
						{
							switch ($entityTypeId)
							{
								case \CCrmOwnerType::Contact:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_NAME'),
											'RQ_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_NAME'),
											'RQ_DRFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_DRFO'),
											'RQ_VAT_PAYER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_VAT_PAYER'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_INN'),
											'RQ_VAT_CERT_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_VAT_CERT_NUM'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_MFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_BD1_RQ_MFO'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_BD1_RQ_ACC_NUM')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_MFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_BD2_RQ_MFO'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ1_BD2_RQ_ACC_NUM')
												)
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_NAME'),
											'RQ_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_NAME'),
											'RQ_DRFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_DRFO'),
											'RQ_VAT_PAYER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_VAT_PAYER'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_INN'),
											'RQ_VAT_CERT_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_VAT_CERT_NUM'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_MFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_BD1_RQ_MFO'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_BD1_RQ_ACC_NUM')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_MFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_BD2_RQ_MFO'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_C_RQ2_BD2_RQ_ACC_NUM')
												)
											)
										)
									);
									break;
								case \CCrmOwnerType::Company:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_COMPANY_NAME'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_INN'),
											'RQ_EDRPOU' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_EDRPOU'),
											'RQ_VAT_PAYER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_VAT_PAYER'),
											'RQ_VAT_CERT_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_VAT_CERT_NUM'),
											'RQ_DIRECTOR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_DIRECTOR'),
											'RQ_ACCOUNTANT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ACCOUNTANT'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_RQ_ADDR_LG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_MFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_BD1_RQ_MFO'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_BD1_RQ_ACC_NUM')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_MFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_BD2_RQ_MFO'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ1_BD2_RQ_ACC_NUM')
												)
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_COMPANY_NAME'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_INN'),
											'RQ_EDRPOU' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_EDRPOU'),
											'RQ_VAT_PAYER' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_VAT_PAYER'),
											'RQ_VAT_CERT_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_VAT_CERT_NUM'),
											'RQ_DIRECTOR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_DIRECTOR'),
											'RQ_ACCOUNTANT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ACCOUNTANT'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_RQ_ADDR_LG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_MFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_BD1_RQ_MFO'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_BD1_RQ_ACC_NUM')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_MFO' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_BD2_RQ_MFO'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_UA_CO_RQ2_BD2_RQ_ACC_NUM')
												)
											)
										)
									);
									break;
							}
						}
						break;
					case 46:    // de
						$requisiteDemoData[$countryId] = array();
						foreach ($entityTypeIds as $entityTypeId)
						{
							switch ($entityTypeId)
							{
								case \CCrmOwnerType::Contact:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_NAME'),
											'RQ_LAST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_LAST_NAME'),
											'RQ_FIRST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_FIRST_NAME'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD1_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD1_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD1_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD1_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD1_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD1_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD1_RQ_BIC')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD2_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD2_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD2_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD2_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD2_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD2_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ1_BD2_RQ_BIC')
												)
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_NAME'),
											'RQ_LAST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_LAST_NAME'),
											'RQ_FIRST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_FIRST_NAME'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD1_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD1_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD1_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD1_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD1_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD1_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD1_RQ_BIC')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD2_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD2_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD2_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD2_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD2_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD2_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_C_RQ2_BD2_RQ_BIC')
												)
											)
										)
									);
									break;
								case \CCrmOwnerType::Company:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_COMPANY_NAME'),
											'RQ_VAT_ID' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_VAT_ID'),
											'RQ_USRLE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_USRLE'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_INN'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_RQ_ADDR_LG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD1_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD1_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD1_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD1_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD1_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD1_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD1_RQ_BIC')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD2_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD2_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD2_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD2_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD2_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD2_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ1_BD2_RQ_BIC')
												)
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_COMPANY_NAME'),
											'RQ_VAT_ID' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_VAT_ID'),
											'RQ_USRLE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_USRLE'),
											'RQ_INN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_INN'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_RQ_ADDR_LG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD1_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD1_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD1_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD1_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD1_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD1_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD1_RQ_BIC')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD2_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD2_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD2_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD2_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD2_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD2_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_DE_CO_RQ2_BD2_RQ_BIC')
												)
											)
										)
									);
									break;
							}
						}
						break;
					case 122:    // us
						$requisiteDemoData[$countryId] = array();
						foreach ($entityTypeIds as $entityTypeId)
						{
							switch ($entityTypeId)
							{
								case \CCrmOwnerType::Contact:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_NAME'),
											'RQ_FIRST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_FIRST_NAME'),
											'RQ_LAST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_LAST_NAME'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_RQ_BIC'),
													'COMMENTS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD1_COMMENTS')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_RQ_BIC'),
													'COMMENTS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ1_BD2_COMMENTS')
												)
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_NAME'),
											'RQ_FIRST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_FIRST_NAME'),
											'RQ_LAST_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_LAST_NAME'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// registration address
												4 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_RG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_RG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_RG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_RG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_RG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_RG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_RG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_RQ_ADDR_RG_COUNTRY_CODE')
												)
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_RQ_BIC'),
													'COMMENTS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD1_COMMENTS')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_RQ_BIC'),
													'COMMENTS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_C_RQ2_BD2_COMMENTS')
												)
											)
										)
									);
									break;
								case \CCrmOwnerType::Company:
									$requisiteDemoData[$countryId][$entityTypeId] = array(
										// 1st requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_COMPANY_NAME'),
											'RQ_VAT_ID' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_VAT_ID'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_RQ_ADDR_LG_COUNTRY_CODE')
												),
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_RQ_BIC'),
													'COMMENTS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD1_COMMENTS')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_RQ_BIC'),
													'COMMENTS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ1_BD2_COMMENTS')
												)
											)
										),
										// 2nd requisite
										array(
											'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_NAME'),
											'RQ_COMPANY_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_COMPANY_NAME'),
											'RQ_VAT_ID' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_VAT_ID'),
											'RQ_ADDR' => array(
												// actual address
												1 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_AC_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_AC_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_AC_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_AC_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_AC_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_AC_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_AC_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_AC_COUNTRY_CODE')
												),
												// legal address
												6 => array(
													'ADDRESS_1' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_LG_ADDRESS_1'),
													'ADDRESS_2' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_LG_ADDRESS_2'),
													'CITY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_LG_CITY'),
													'POSTAL_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_LG_POSTAL_CODE'),
													'REGION' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_LG_REGION'),
													'PROVINCE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_LG_PROVINCE'),
													'COUNTRY' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_LG_COUNTRY'),
													'COUNTRY_CODE' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_RQ_ADDR_LG_COUNTRY_CODE')
												),
											),
											'BANK_DETAILS' => array(
												// 1st bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_RQ_BIC'),
													'COMMENTS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD1_COMMENTS')
												),
												// 2nd bank detail
												array(
													'NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_NAME'),
													'RQ_BANK_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_RQ_BANK_NAME'),
													'RQ_BANK_ADDR' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_RQ_BANK_ADDR'),
													'RQ_BANK_ROUTE_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_RQ_BANK_ROUTE_NUM'),
													'RQ_ACC_NAME' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_RQ_ACC_NAME'),
													'RQ_ACC_NUM' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_RQ_ACC_NUM'),
													'RQ_IBAN' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_RQ_IBAN'),
													'RQ_SWIFT' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_RQ_SWIFT'),
													'RQ_BIC' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_RQ_BIC'),
													'COMMENTS' => self::getMessage($messages, 'CRM_RQ_IMP_DMO_US_CO_RQ2_BD2_COMMENTS')
												)
											)
										)
									);
									break;
							}
						}
						break;
				}
			}
		}

		return $requisiteDemoData;
	}
	public static function getMessage(array $messages, $code)
	{
		if (isset($messages[$code]))
			return $messages[$code];
		
		return '';
	}
}