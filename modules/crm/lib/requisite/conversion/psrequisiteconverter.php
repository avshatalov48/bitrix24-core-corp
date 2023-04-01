<?php

namespace Bitrix\Crm\Requisite\Conversion;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Crm\Settings\CompanySettings;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Invoice;

class PSRequisiteConverter
{
	private static $startTime = null;
	private static $stepTime = 3;
	private static $stepInvoiceCount = 20;

	private static $paySystemData = null;
	private static $convPresets = null;
	private static $convMap = null;
	private static $convFieldSets = null;
	private static $convCountries = null;
	private static $convData = null;
	private static $entityTree = null;
	private static $psEntityTreeMap = null;
	private static $progressData = null;

	private static $presetCache = array();

	public static function getProgressData()
	{
		if (self::$progressData === null)
		{
			$progressData = \COption::GetOptionString('crm', '~CRM_PS_REQUISITES_TRANSFER_PROGRESS', '');
			$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
			if (!is_array($progressData))
				$progressData = array();
			self::$progressData = $progressData;
		}

		return self::$progressData;
	}

	private static function setProgressData($progressData)
	{
		if (!is_array($progressData))
			$progressData = array();
		self::$progressData = $progressData;
		\COption::SetOptionString('crm', '~CRM_PS_REQUISITES_TRANSFER_PROGRESS', serialize(self::$progressData));

		return self::$progressData;
	}

	private static function updateProgressData($progressData)
	{
		if (is_array($progressData) && !empty($progressData))
		{
			$data = self::getProgressData();
			if (!is_array($data))
				$data = array();

			foreach ($progressData as $k => $v)
				$data[$k] = $v;

			self::$progressData = $data;
			\COption::SetOptionString('crm', '~CRM_PS_REQUISITES_TRANSFER_PROGRESS', serialize(self::$progressData));
		}

		return self::$progressData;
	}

	private static function removeProgressData()
	{
		\COption::RemoveOption('crm', '~CRM_PS_REQUISITES_TRANSFER_PROGRESS');
	}

	private static function removeTriggerOption()
	{
		\COption::RemoveOption('crm', '~CRM_TRANSFER_PS_PARAMS_TO_REQUISITES');
	}

	private static function getConvMap()
	{
		if (self::$convMap === null)
		{
			$idRU = GetCountryIdByCode('RU');
			$idKZ = GetCountryIdByCode('KZ');
			$idUA = GetCountryIdByCode('UA');
			$idDE = GetCountryIdByCode('DE');
			$idUS = GetCountryIdByCode('US');
			self::$convMap = array(
				'bill' => array(
					'ru' => array(
						$idRU => array(
							'SELLER_COMPANY_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_COMPANY_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_COMPANY_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_INN',
								'MAX_LENGTH' => 15
							),
							'SELLER_COMPANY_KPP' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_KPP',
								'MAX_LENGTH' => 9
							),
							'SELLER_COMPANY_BANK_ACCOUNT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_COMPANY_BANK_NAME' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_CITY' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ADDR',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_ACCOUNT_CORR' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_COR_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_COMPANY_BANK_BIC' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BIK',
								'MAX_LENGTH' => 9
							),
							'SELLER_COMPANY_DIRECTOR_NAME' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_DIRECTOR',
								'MAX_LENGTH' => 150
							),
							'SELLER_COMPANY_ACCOUNTANT_NAME' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'MAX_LENGTH' => 150
							)
						),
						$idKZ => array(
							'SELLER_COMPANY_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_COMPANY_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_COMPANY_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_INN',
								'MAX_LENGTH' => 15
							),
							'SELLER_COMPANY_BANK_ACCOUNT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_IIK',
								'MAX_LENGTH' => 20
							),
							'SELLER_COMPANY_BANK_NAME' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_CITY' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ADDR',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_ACCOUNT_CORR' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_COR_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_COMPANY_BANK_BIC' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BIK',
								'MAX_LENGTH' => 9
							),
							'SELLER_COMPANY_DIRECTOR_POSITION' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_CEO_WORK_POS',
								'MAX_LENGTH' => 150
							)
						)
					),
					'de' => array(
						$idDE => array(
							'SELLER_COMPANY_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_COMPANY_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_COMPANY_EMAIL' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'EMAIL',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_COMPANY_BANK_ACCOUNT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_COMPANY_BANK_NAME' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_BIC' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ROUTE_NUM',
								'MAX_LENGTH' => 9
							),
							'SELLER_COMPANY_BANK_IBAN' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_IBAN',
								'MAX_LENGTH' => 34
							),
							'SELLER_COMPANY_BANK_SWIFT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_SWIFT',
								'MAX_LENGTH' => 11
							),
							'SELLER_COMPANY_EU_INN' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_VAT_ID',
								'MAX_LENGTH' => 20
							),
							'SELLER_COMPANY_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_INN',
								'MAX_LENGTH' => 15
							),
							'SELLER_COMPANY_REG' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_USRLE',
								'MAX_LENGTH' => 20
							)
						)
					),
					'en' => array(
						$idUS => array(
							'SELLER_COMPANY_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_COMPANY_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_COMPANY_BANK_NAME' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_ACCOUNT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_COMPANY_BANK_ADDR' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ADDR',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_ACCOUNT_CORR' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ROUTE_NUM',
								'MAX_LENGTH' => 9
							),
							'SELLER_COMPANY_BANK_SWIFT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_SWIFT',
								'MAX_LENGTH' => 11
							)
						)
					),
					'la' => array(
						$idUS => array(
							'SELLER_COMPANY_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_COMPANY_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_COMPANY_BANK_NAME' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_ACCOUNT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_COMPANY_BANK_ADDR' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ADDR',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_ACCOUNT_CORR' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ROUTE_NUM',
								'MAX_LENGTH' => 9
							),
							'SELLER_COMPANY_BANK_SWIFT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_SWIFT',
								'MAX_LENGTH' => 11
							)
						)
					),
					'ua' => array(
						$idUA => array(
							'SELLER_COMPANY_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_BANK_ACCOUNT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_COMPANY_BANK_NAME' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_COMPANY_MFO' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_MFO',
								'MAX_LENGTH' => 6
							),
							'SELLER_COMPANY_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_COMPANY_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_COMPANY_EDRPOY' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_EDRPOU',
								'MAX_LENGTH' => 10
							),
							'SELLER_COMPANY_IPN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_INN',
								'MAX_LENGTH' => 15
							),
							'SELLER_COMPANY_PDV' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_VAT_CERT_NUM',
								'MAX_LENGTH' => 15
							),
							'SELLER_COMPANY_ACCOUNTANT_NAME' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'MAX_LENGTH' => 150
							)
						)
					)
				),
				'quote' => array(
					'ru' => array(
						$idRU => array(
							'SELLER_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_INN',
								'MAX_LENGTH' => 15
							),
							'SELLER_KPP' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_KPP',
								'MAX_LENGTH' => 9
							),
							'SELLER_RS' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_BANK' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_BCITY' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ADDR',
								'MAX_LENGTH' => 255
							),
							'SELLER_KS' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_COR_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_BIK' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BIK',
								'MAX_LENGTH' => 9
							),
							'SELLER_DIR' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_DIRECTOR',
								'MAX_LENGTH' => 150
							),
							'SELLER_ACC' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'MAX_LENGTH' => 150
							)
						),
						$idKZ => array(
							'SELLER_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_INN',
								'MAX_LENGTH' => 15
							),
							'SELLER_RS' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_IIK',
								'MAX_LENGTH' => 20
							),
							'SELLER_BANK' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_BCITY' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ADDR',
								'MAX_LENGTH' => 255
							),
							'SELLER_KS' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_COR_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_BIK' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BIK',
								'MAX_LENGTH' => 9
							),
							'SELLER_DIR_POS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_CEO_WORK_POS',
								'MAX_LENGTH' => 150
							)
						),
						$idUA => array(
							'SELLER_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_INN',
								'MAX_LENGTH' => 15
							),
							'SELLER_RS' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_BANK' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_DIR' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_DIRECTOR',
								'MAX_LENGTH' => 150
							),
							'SELLER_ACC' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ACCOUNTANT',
								'MAX_LENGTH' => 150
							)
						)
					),
					'de' => array(
						$idDE => array(
							'SELLER_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_EMAIL' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'EMAIL',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_BANK_ACCNO' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_BANK' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_BANK_BLZ' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ROUTE_NUM',
								'MAX_LENGTH' => 9
							),
							'SELLER_BANK_IBAN' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_IBAN',
								'MAX_LENGTH' => 34
							),
							'SELLER_BANK_SWIFT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_SWIFT',
								'MAX_LENGTH' => 11
							),
							'SELLER_EU_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_VAT_ID',
								'MAX_LENGTH' => 20
							),
							'SELLER_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_INN',
								'MAX_LENGTH' => 15
							),
							'SELLER_REG' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_USRLE',
								'MAX_LENGTH' => 20
							)
						)
					),
					'en' => array(
						$idUS => array(
							'SELLER_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_EMAIL' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'EMAIL',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_BANK_ACCNO' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_BANK' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_BANK_BLZ' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ROUTE_NUM',
								'MAX_LENGTH' => 9
							),
							'SELLER_BANK_IBAN' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_IBAN',
								'MAX_LENGTH' => 34
							),
							'SELLER_BANK_SWIFT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_SWIFT',
								'MAX_LENGTH' => 11
							),
							'SELLER_EU_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_VAT_ID',
								'MAX_LENGTH' => 20
							)
						)
					),
					'la' => array(
						$idUS => array(
							'SELLER_NAME' => array(
								'ENTITY_ABBR' => 'CO',
								'FIELD_NAME' => 'TITLE',
								'MAX_LENGTH' => 255
							),
							'SELLER_ADDRESS' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_ADDR',
								'MAX_LENGTH' => 256,
								'ADDR_TYPE' => RequisiteAddress::Registered
							),
							'SELLER_PHONE' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'PHONE',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_EMAIL' => array(
								'ENTITY_ABBR' => 'CO.MF',
								'FIELD_NAME' => 'EMAIL',
								'MAX_LENGTH' => 255,
								'MF_VALUE_TYPE' => 'WORK'
							),
							'SELLER_BANK_ACCNO' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_ACC_NUM',
								'MAX_LENGTH' => 34
							),
							'SELLER_BANK' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_NAME',
								'MAX_LENGTH' => 255
							),
							'SELLER_BANK_BLZ' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_BANK_ROUTE_NUM',
								'MAX_LENGTH' => 9
							),
							'SELLER_BANK_IBAN' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_IBAN',
								'MAX_LENGTH' => 34
							),
							'SELLER_BANK_SWIFT' => array(
								'ENTITY_ABBR' => 'BD',
								'FIELD_NAME' => 'RQ_SWIFT',
								'MAX_LENGTH' => 11
							),
							'SELLER_EU_INN' => array(
								'ENTITY_ABBR' => 'RQ',
								'FIELD_NAME' => 'RQ_VAT_ID',
								'MAX_LENGTH' => 20
							)
						)
					)
				)
			);
		}

		return self::$convMap;
	}

	private static function parsePaySystemData()
	{
		if (self::$paySystemData === null)
		{
			$arCrmPt = \CCrmPaySystem::getPersonTypeIDs();
			$arCrmPtIDs = array_values($arCrmPt);
			$dbPaySystems = \CSalePaySystem::GetList(
				array('ID' => 'asc'),
				array(
					"PERSON_TYPE_ID" => $arCrmPtIDs,
					"!ID" => \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId()
				)
			);

			$convMap = null;
			$ruAccNumDefValue = $enAccNumDefValue = null;
			self::$paySystemData = array();
			while ($arPaySys = $dbPaySystems->Fetch())
			{
				if (isset($arPaySys['PSA_ACTION_FILE']))
				{
					$matches = array();
					$curPsLocalization = '';
					$psType = '';
					if (preg_match('/(bill|quote)(_\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $arPaySys['PSA_ACTION_FILE'], $matches))
					{
						$psType = $matches[1];
						if (count($matches) === 2 && $psType === 'bill')
						{
							$curPsLocalization = 'ru';
						}
						else if (count($matches) === 3 && mb_strlen($matches[2]) > 2)
						{
							$curPsLocalization = mb_substr($matches[2], 1, 2);
						}
					}
					$origPsaParams = \CSalePaySystemAction::UnSerializeParams($arPaySys['PSA_PARAMS']);
					if (!empty($curPsLocalization)
						&& in_array($curPsLocalization, array('ru', 'de', 'en', 'la', 'ua'), true)
						&& is_array($origPsaParams) && !empty($origPsaParams)
					)
					{
						$presetCountryId = \CCrmPaySystem::getPresetCountryIdByPS($psType, $curPsLocalization);
						if ($convMap === null)
							$convMap = self::getConvMap();
						if (is_array($convMap) && $presetCountryId > 0
							&& (EntityPreset::isUTFMode() || $presetCountryId === EntityPreset::getCurrentCountryId())
							&& isset($convMap[$psType][$curPsLocalization][$presetCountryId])
						)
						{
							// prepare params
							$allParamsEmpty = 'Y';
							$hasCompanyParams = 'N';
							$hasRequisiteParams = 'N';
							$hasBankDetailParams = 'N';
							$psaParams = array();
							foreach ($origPsaParams as $paramName => $paramInfo)
							{
								if (isset($convMap[$psType][$curPsLocalization][$presetCountryId][$paramName])
									&& is_array($paramInfo) && (!isset($paramInfo['TYPE']) || empty($paramInfo['TYPE']))
									&& (!isset($paramInfo['VALUE']) || !is_array($paramInfo['VALUE']))
								)
								{
									$value = trim(strval(isset($paramInfo['VALUE']) ? $paramInfo['VALUE'] : ''));

									// bank account number default value as empty
									if ($ruAccNumDefValue === null)
									{
										$ruAccNumDefValue = '';
										$messages = Loc::loadLanguageFile(__FILE__, 'ru');
										if (!empty($messages) && isset($messages['CRM_PS_RQ_CONV_ACC_NUM_DEF_VAL']))
											$ruAccNumDefValue = $messages['CRM_PS_RQ_CONV_ACC_NUM_DEF_VAL'];
									}
									if ($enAccNumDefValue === null)
									{
										$enAccNumDefValue = '';
										$messages = Loc::loadLanguageFile(__FILE__, 'en');
										if (!empty($messages) && isset($messages['CRM_PS_RQ_CONV_ACC_NUM_DEF_VAL']))
											$enAccNumDefValue = $messages['CRM_PS_RQ_CONV_ACC_NUM_DEF_VAL'];
									}
									if (($paramName === 'SELLER_RS' || $paramName === 'SELLER_COMPANY_BANK_ACCOUNT')
										&& ($value === $ruAccNumDefValue || $value === $enAccNumDefValue)
									)
									{
										$value = '';
									}

									if (!empty($value))
										$allParamsEmpty = 'N';

									$convInfo = $convMap[$psType][$curPsLocalization][$presetCountryId][$paramName];
									if (isset($convInfo['MAX_LENGTH']) && mb_strlen($value) <= $convInfo['MAX_LENGTH'])
									{
										if (!empty($value) && isset($convInfo['ENTITY_ABBR']))
										{
											switch ($convInfo['ENTITY_ABBR'])
											{
												case 'CO':
												case 'CO.MF':
													$hasCompanyParams = 'Y';
													break;
												case 'RQ':
													$hasRequisiteParams = 'Y';
													break;
												case 'BD':
													$hasBankDetailParams = 'Y';
													break;
											}
										}
										$convInfo['VALUE'] = $value;
										$psaParams[$paramName] = $convInfo;
									}
								}
							}

							if (!empty($psaParams))
							{
								self::$paySystemData[$arPaySys['ID']] = array(
									'PS_ID' => $arPaySys['ID'],
									'PSA_ID' => $arPaySys['PSA_ID'],
									'PS_TYPE' => $psType,
									'PSA_PERSON_TYPE_ID' => $arPaySys['PSA_PERSON_TYPE_ID'],
									'PS_NAME' => $arPaySys['NAME'],
									'PS_LOCALIZATION' => $curPsLocalization,
									'PRESET_COUNTRY_ID' => $presetCountryId,
									'PSA_PARAMS_ORIG' => $origPsaParams,
									'PSA_PARAMS' => $psaParams,
									'PSA_PARAMS_ALL_EMPTY' => $allParamsEmpty,
									'PSA_PARAMS_HAS_CO' => $hasCompanyParams,
									'PSA_PARAMS_HAS_RQ' => $hasRequisiteParams,
									'PSA_PARAMS_HAS_BD' => $hasBankDetailParams
								);
							}
						}
					}
				}
			}
		}

		self::updateProgressData(array('PS_COUNT' => count(self::$paySystemData)));
	}

	private static function prepareConvFieldSets()
	{
		if (is_array(self::$paySystemData) && self::$convFieldSets === null)
		{
			$psData = self::$paySystemData;
			if (is_array($psData) && !empty($psData))
			{
				// prepare fieldsets
				$fieldSets = array(
					'CO' => array(),
					'RQ' => array(),
					'BD' => array()
				);
				foreach ($psData as $psInfo)
				{
					if (is_array($psInfo) && is_array($psInfo['PSA_PARAMS'])
						&& isset($psInfo['PS_ID']) && $psInfo['PS_ID'] > 0
						&& isset($psInfo['PRESET_COUNTRY_ID']) && $psInfo['PRESET_COUNTRY_ID'] > 0)
					{
						$presetCountryId = (int)$psInfo['PRESET_COUNTRY_ID'];
						$psaParams = $psInfo['PSA_PARAMS'];
						if (!empty($psaParams))
						{
							foreach ($psaParams as $paramName => $paramInfo)
							{
								switch ($paramInfo['ENTITY_ABBR'])
								{
									case 'CO':
									case 'CO.MF':
										if (!isset($fieldSets['CO'][$paramInfo['FIELD_NAME']]))
											$fieldSets['CO'][$paramInfo['FIELD_NAME']] = array();
										$fieldSets['CO'][$paramInfo['FIELD_NAME']][(int)$psInfo['PS_ID']] = $paramName;
										break;
									case 'RQ':
										if (!isset($fieldSets['RQ'][$presetCountryId][$paramInfo['FIELD_NAME']]))
											$fieldSets['RQ'][$presetCountryId][$paramInfo['FIELD_NAME']] = array();
										$fieldSets['RQ'][$presetCountryId][$paramInfo['FIELD_NAME']][(int)$psInfo['PS_ID']] = $paramName;
										break;
									case 'BD':
										if (!isset($fieldSets['BD'][$presetCountryId][$paramInfo['FIELD_NAME']]))
											$fieldSets['BD'][$presetCountryId][$paramInfo['FIELD_NAME']] = array();
										$fieldSets['BD'][$presetCountryId][$paramInfo['FIELD_NAME']][(int)$psInfo['PS_ID']] = $paramName;
										break;
								}
							}
						}
					}
				}
				self::$convFieldSets = $fieldSets;
			}
		}
	}

	private static function preparePaySystemConvData()
	{
		if (self::$convData === null)
		{
			$convCountries = array();
			$convData = array();

			$psData = self::$paySystemData;
			if (is_array($psData) && !empty($psData))
			{
				$fieldSets = self::$convFieldSets;
				foreach ($psData as $psInfo)
				{
					if (is_array($psInfo) && is_array($psInfo['PSA_PARAMS'])
						&& isset($psInfo['PS_ID']) && $psInfo['PS_ID'] > 0
						&& isset($psInfo['PRESET_COUNTRY_ID']) && $psInfo['PRESET_COUNTRY_ID'] > 0)
					{
						$psId = (int)$psInfo['PS_ID'];
						$presetCountryId = (int)$psInfo['PRESET_COUNTRY_ID'];
						$psaParams = $psInfo['PSA_PARAMS'];
						if (!isset($convCountries[$presetCountryId]))
							$convCountries[$presetCountryId] = true;
						$psConvData = array(
							'CO' => array(),
							'RQ' => array(),
							'BD' => array()
						);
						$hashData = '';
						foreach ($fieldSets['CO'] as $fieldName => $fieldParam)
						{
							$value = '';
							$paramName = isset($fieldParam[$psId]) ? $fieldParam[$psId] : '';
							if (!empty($paramName))
							{
								$value = isset($psaParams[$paramName]['VALUE']) ?
									$psaParams[$paramName]['VALUE'] : '';
							}
							$psConvData['CO'][$fieldName] = $value;
							if ($fieldName === 'TITLE')
							{
								$psConvData['RQ']['RQ_COMPANY_NAME'] = $value;
								$hashData = '|'.$value;
							}
						}
						$psConvData['CO_H'] = md5($hashData);
						unset($hashData);
						$hashData = '';
						if (is_array($fieldSets['RQ'][$presetCountryId]))
						{
							$value = isset($psConvData['RQ']['RQ_COMPANY_NAME']) ?
								$psConvData['RQ']['RQ_COMPANY_NAME'] : '';
							$hashData = '|'.$presetCountryId.'|'.$value;
							foreach ($fieldSets['RQ'][$presetCountryId] as $fieldName => $fieldParam)
							{
								$value = '';
								$paramName = isset($fieldParam[$psId]) ? $fieldParam[$psId] : '';
								if (!empty($paramName))
								{
									$value = isset($psaParams[$paramName]['VALUE']) ?
										$psaParams[$paramName]['VALUE'] : '';
								}
								$psConvData['RQ'][$fieldName] = $value;
								$hashData .= '|'.$value;
							}
						}
						$psConvData['RQ_H'] = md5($hashData);
						unset($hashData);
						$hashData = '';
						if (is_array($fieldSets['BD'][$presetCountryId]))
						{
							$hashData = '|'.$presetCountryId;
							foreach ($fieldSets['BD'][$presetCountryId] as $fieldName => $fieldParam)
							{
								$value = '';
								$paramName = isset($fieldParam[$psId]) ? $fieldParam[$psId] : '';
								if (!empty($paramName))
								{
									$value = isset($psaParams[$paramName]['VALUE']) ?
										$psaParams[$paramName]['VALUE'] : '';
								}
								$psConvData['BD'][$fieldName] = $value;
								$hashData .= '|'.$value;
							}
						}
						$psConvData['BD_H'] = md5($hashData);
						unset($hashData);
						if (!is_array($convData[$presetCountryId]))
							$convData[$presetCountryId] = array();
						$convData[$presetCountryId][$psId] = $psConvData;
					}
				}
			}
			self::$convCountries = array_keys($convCountries);
			self::$convData = $convData;
		}
	}

	private static function prepareEntityTree()
	{
		if (is_array(self::$convFieldSets) && is_array(self::$convData))
		{
			self::$entityTree = array();
			foreach (self::$convData as $presetCountryId => $list)
			{
				foreach ($list as $psId => $psConvData)
				{
					if ($psId > 0
						&& isset($psConvData['CO']['TITLE']) && !empty($psConvData['CO']['TITLE'])
						&& isset($psConvData['CO_H']))
					{
						// company
						$psInfo = self::$paySystemData[$psId];
						$coInfo = null;
						if (!isset(self::$entityTree[$psConvData['CO_H']]))
						{
							self::$entityTree[$psConvData['CO_H']] = array(
								'PS' => array(),
								'CO' => array(
									'TITLE' => $psConvData['CO']['TITLE'],
									'PHONE' => array(),
									'EMAIL' => array(),
								),
								'RQ' => array()
							);
						}
						$coInfo = &self::$entityTree[$psConvData['CO_H']];
						$coInfo['PS'][] = $psId;
						$mf = array('PHONE', 'EMAIL');
						foreach ($mf as $mfName)
						{
							if (isset($psConvData['CO'][$mfName]) && !empty($psConvData['CO'][$mfName]))
							{
								if (!in_array($psConvData['CO'][$mfName], $coInfo['CO'][$mfName], true))
									$coInfo['CO'][$mfName][] = $psConvData['CO'][$mfName];
							}
						}

						// requisite
						if (isset($psInfo['PSA_PARAMS_HAS_RQ']) && $psInfo['PSA_PARAMS_HAS_RQ'] === 'Y')
						{
							if (!isset($coInfo['RQ'][$presetCountryId][$psConvData['RQ_H']]))
							{
								if (!is_array($coInfo['RQ'][$presetCountryId]))
									$coInfo['RQ'][$presetCountryId] = array();
								$coInfo['RQ'][$presetCountryId][$psConvData['RQ_H']] = array(
									'PS' => array(),
									'RQ' => array(),
									'BD' => array()
								);
							}
							$rqInfo = &$coInfo['RQ'][$presetCountryId][$psConvData['RQ_H']];
							$rqInfo['PS'][] = $psId;
							foreach ($psConvData['RQ'] as $rqName => $rqValue)
							{
								if (!empty($rqValue))
									$rqInfo['RQ'][$rqName] = $rqValue;
							}
						}

						// bank details
						if (isset($psInfo['PSA_PARAMS_HAS_BD']) && $psInfo['PSA_PARAMS_HAS_BD'] === 'Y')
						{
							if (!isset($rqInfo['BD'][$psConvData['BD_H']]))
							{
								$rqInfo['BD'][$psConvData['BD_H']] = array(
									'PS' => array(),
									'BD' => array()
								);
							}
							$bdInfo = &$rqInfo['BD'][$psConvData['BD_H']];
							$bdInfo['PS'][] = $psId;
							foreach ($psConvData['BD'] as $bdName => $bdValue)
							{
								if (!empty($bdValue))
									$bdInfo['BD'][$bdName] = $bdValue;
							}
						}

						unset($coInfo, $rqInfo, $bdInfo);
					}
				}
			}
		}
	}

	private static function updateCompanyMF($id, $info)
	{
		
		// update IS_MY_COMPANY flag
		$res = \CCrmCompany::GetListEx(
			array(),
			array('=ID' => $id),
			false,
			array('nTopCount' => 1),
			array('ID', 'IS_MY_COMPANY')
		);
		$row = $res->Fetch();
		if (isset($row['IS_MY_COMPANY']) && $row['IS_MY_COMPANY'] !== 'Y')
		{
			$connection = Main\Application::getConnection();
			$strSql = '';
			if ($connection instanceof Main\DB\MysqlCommonConnection)
			{
				$strSql = "UPDATE b_crm_company SET IS_MY_COMPANY = 'Y' WHERE ID = $id";
			}
			elseif ($connection instanceof Main\DB\MssqlConnection
				|| $connection instanceof Main\DB\OracleConnection)
			{
				$strSql = "UPDATE B_CRM_COMPANY SET IS_MY_COMPANY = 'Y' WHERE ID = $id";
			}
			if (!empty($strSql))
			{
				$connection->queryExecute($strSql);
			}
		}


		if ($id > 0 && is_array($info) && is_array($info['CO'])
			&& ((is_array($info['CO']['PHONE']) && !empty($info['CO']['PHONE']))
				|| (is_array($info['CO']['EMAIL'] && !empty($info['CO']['EMAIL'])))))
		{
			$phoneList = array();
			$emailList = array();
			$phoneToAdd = array();
			$emailToAdd = array();

			$res = \CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => (int)$id)
			);
			while ($row = $res->Fetch())
			{
				if ($row['COMPLEX_ID'] === 'PHONE_WORK')
				{
					$phoneList[] = trim(strval($row['VALUE']));
				}
				else if ($row['COMPLEX_ID'] === 'EMAIL_WORK')
				{
					$emailList[] = trim(strval($row['VALUE']));
				}
			}

			if (!empty($info['CO']['PHONE']))
			{
				if (empty($phoneList))
				{
					$phoneToAdd = $info['CO']['PHONE'];
				}
				else
				{
					foreach ($info['CO']['PHONE'] as $value)
					{
						if (!in_array($value, $phoneList, true))
							$phoneToAdd[] = $value;
					}
				}
			}

			if (!empty($info['CO']['EMAIL']))
			{
				if (empty($emailList))
				{
					$emailToAdd = $info['CO']['EMAIL'];
				}
				else
				{
					foreach ($info['CO']['EMAIL'] as $value)
					{
						if (!in_array($value, $emailList, true))
							$emailToAdd[] = $value;
					}
				}
			}

			$fieldMulti = new \CCrmFieldMulti();
			foreach ($phoneToAdd as $value)
			{
				$fieldMulti->Add(array(
					'ENTITY_ID' => 'COMPANY',
					'ELEMENT_ID' => (int)$id,
					'TYPE_ID' => 'PHONE',
					'VALUE_TYPE' => 'WORK',
					'COMPLEX_ID' => 'PHONE_WORK',
					'VALUE' => $value
				));
			}
			foreach ($emailToAdd as $value)
			{
				$fieldMulti->Add(array(
					'ENTITY_ID' => 'COMPANY',
					'ELEMENT_ID' => (int)$id,
					'TYPE_ID' => 'EMAIL',
					'VALUE_TYPE' => 'WORK',
					'COMPLEX_ID' => 'EMAIL_WORK',
					'VALUE' => $value
				));
			}
		}
	}

	private static function addCompany($info)
	{
		$companyId = 0;
		
		$fields = array(
			'TITLE' => $info['CO']['TITLE'],
			'COMPANY_TYPE' => 'OTHER',
			'INDUSTRY' => 'OTHER',
			'CURRENCY_ID' => \CCrmCurrency::GetBaseCurrencyID(),
			'OPENED' => CompanySettings::getCurrent()->getOpenedFlag() ? 'Y' : 'N',
			'IS_MY_COMPANY' => 'Y',
			'FM' => array(),
		);
		foreach (array('EMAIL', 'PHONE') as $mfType)
		{
			if (is_array($info['CO'][$mfType]))
			{
				$i = 1;
				foreach ($info['CO'][$mfType] as $value)
				{
					if (!is_array($fields['FM'][$mfType]))
						$fields['FM'][$mfType] = array();
					$fields['FM'][$mfType]['n'.$i++] = array(
						'VALUE' => $value,
						'VALUE_TYPE' => 'WORK'
					);
				}
			}
		}
		$company = new \CCrmCompany(false);
		$id = $company->Add($fields);
		if ($id > 0)
			$companyId = $id;

		return $companyId;
	}

	private static function addRequisite($info)
	{
		$requisiteId = 0;

		$preset = new EntityPreset();
		$requisite = new EntityRequisite();
		$psId = isset($info['PS'][0]) ? (int)$info['PS'][0] : 0;
		$companyId = isset($info['CO_ID']) ? (int)$info['CO_ID'] : 0;
		$presetCountryId = isset($info['PRESET_COUNTRY_ID']) ? (int)$info['PRESET_COUNTRY_ID'] : 0;
		if ($psId > 0 && $companyId > 0 && $presetCountryId > 0 && is_array($info['RQ']) && !empty($info['RQ']))
		{
			if (!isset(self::$presetCache[$presetCountryId]))
			{
				$countryCode = EntityPreset::getCountryCodeById($presetCountryId);
				if (!empty($countryCode))
				{
					$presetXmlId = '#CRM_REQUISITE_PRESET_'.$countryCode.'_SELLER#';
					$res = $preset->getList(
						array(
							'filter' => array('=XML_ID' => $presetXmlId, '=COUNTRY_ID' => $presetCountryId),
							'select' => array('ID'),
							'limit' => 1
						)
					);
					if ($row = $res->fetch())
					{
						if (is_array($row) && isset($row['ID']))
							self::$presetCache[$presetCountryId] = (int)$row['ID'];
					}
					unset($res, $row);

				}
			}
			$presetId = isset(self::$presetCache[$presetCountryId]) ? self::$presetCache[$presetCountryId] : 0;
			$psName = isset(self::$paySystemData[$psId]['PS_NAME']) ? self::$paySystemData[$psId]['PS_NAME'] : '';
			if ($presetId > 0 && !empty($psName))
			{
				// sort value
				$sort = 500;
				$res = $requisite->getList(
					array(
						'order' => array('SORT' => 'DESC', 'ID' => 'DESC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
							'=ENTITY_ID' => $companyId
						),
						'select' => array('SORT'),
						'limit' => 1
					)
				);
				if ($row = $res->fetch())
				{
					if (isset($row['SORT']))
						$sort = (int)$row['SORT'];
				}
				unset($res, $row);
				$sort = $sort - ($sort % 10) + 10;

				$fields = array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
					'ENTITY_ID' => $companyId,
					'PRESET_ID' => $presetId,
					'NAME' => $psName,
					'ACTIVE' => 'Y',
					'SORT' => $sort
				);
				foreach ($info['RQ'] as $rqName => $rqValue)
				{
					if ($rqName === EntityRequisite::ADDRESS)
					{
						$addrType = RequisiteAddress::Registered;
						$addrValue = array($addrType => array());
						$addrFields = array_keys($requisite->getAddressFieldMap(RequisiteAddress::Primary));
						foreach ($addrFields as $addrFieldName)
						{
							if ($addrFieldName === 'ADDRESS_1')
								$addrValue[$addrType][$addrFieldName] = $rqValue;
							else
								$addrValue[$addrType][$addrFieldName] = '';
						}
						$fields[$rqName] = $addrValue;
					}
					else
					{
						$fields[$rqName] = $rqValue;
					}
				}
				$result = $requisite->add($fields);
				if ($result->isSuccess())
					$requisiteId = $result->getId();
			}
		}

		return $requisiteId;
	}

	private static function addBankDetail(&$info)
	{
		$bankDetailId = 0;

		$bankDetail = new EntityBankDetail();
		$psId = isset($info['PS'][0]) ? (int)$info['PS'][0] : 0;
		$requisiteId = isset($info['RQ_ID']) ? (int)$info['RQ_ID'] : 0;
		$countryId = isset($info['COUNTRY_ID']) ? (int)$info['COUNTRY_ID'] : 0;
		if ($psId > 0 && $requisiteId > 0 && $countryId > 0
			&& is_array($info['BD']) && !empty($info['BD']))
		{
			$psName = isset(self::$paySystemData[$psId]['PS_NAME']) ? self::$paySystemData[$psId]['PS_NAME'] : '';
			if (!empty($psName))
			{
				// sort value
				$sort = 500;
				$res = $bankDetail->getList(
					array(
						'order' => array('SORT' => 'DESC', 'ID' => 'DESC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
							'=ENTITY_ID' => $requisiteId
						),
						'select' => array('SORT'),
						'limit' => 1
					)
				);
				if ($row = $res->fetch())
				{
					if (isset($row['SORT']))
						$sort = (int)$row['SORT'];
				}
				unset($res, $row);
				$sort = $sort - ($sort % 10) + 10;

				$fields = array(
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
					'ENTITY_ID' => $requisiteId,
					'COUNTRY_ID' => $countryId,
					'NAME' => $psName,
					'SORT' => $sort
				);
				foreach ($info['BD'] as $bdName => $bdValue)
					$fields[$bdName] = $bdValue;
				$result = $bankDetail->add($fields);
				if ($result->isSuccess())
					$bankDetailId = $result->getId();
			}
		}

		return $bankDetailId;
	}

	private static function saveCompanyRequisite(&$info)
	{
		if (self::$psEntityTreeMap === null)
			self::$psEntityTreeMap = array();

		if (isset($info['ID']) && $info['ID'] > 0 && is_array($info['RQ']) && !empty($info['RQ'])
			&& is_array(self::$convFieldSets) && is_array(self::$convFieldSets['RQ']))
		{
			// prepare hashes of existing requisites
			$rqHashList = array();
			if (isset($info['IS_NEW']) && $info['IS_NEW'] === 'N')
			{
				$requisite = new EntityRequisite();
				$res = $requisite->getList(
					array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
							'=ENTITY_ID' => (int)$info['ID']
						),
						'select' => array_merge(
							array('ID', 'PRESET_COUNTRY_ID' => 'PRESET.COUNTRY_ID'),
							$requisite->getRqFields()
						)
					)
				);
				$fieldSets = self::$convFieldSets;
				while ($row = $res->fetch())
				{
					$requisiteId = isset($row['ID']) ? (int)$row['ID'] : 0;
					$presetCountryId = isset($row['PRESET_COUNTRY_ID']) ? (int)$row['PRESET_COUNTRY_ID'] : 0;
					if ($requisiteId > 0 && $presetCountryId > 0)
					{
						$value = isset($row['RQ_COMPANY_NAME']) ? trim(strval($row['RQ_COMPANY_NAME'])) : '';
						$hashData = '|'.$presetCountryId.'|'.$value;
						if (is_array($fieldSets['RQ'][$presetCountryId]))
						{
							foreach (array_keys($fieldSets['RQ'][$presetCountryId]) as $fieldName)
							{
								if ($fieldName === EntityRequisite::ADDRESS)
								{
									$value = '';
									$addrValue = RequisiteAddress::getByOwner(
										RequisiteAddress::Registered,
										\CCrmOwnerType::Requisite,
										$requisiteId
									);
									if (is_array($addrValue) && isset($addrValue['ADDRESS_1']))
										$value = trim(strval($addrValue['ADDRESS_1']));
								}
								else
								{
									$value = isset($row[$fieldName]) ? trim(strval($row[$fieldName])) : '';
								}
								$hashData .= '|'.$value;
							}
						}
						if (!is_array($rqHashList[$presetCountryId]))
							$rqHashList[$presetCountryId] = array();
						$rqHashList[$presetCountryId][md5($hashData)] = $requisiteId;
					}
				}
				unset($res, $row, $hashData, $value, $requisiteId, $presetCountryId);
			}

			// add requisites
			foreach ($info['RQ'] as $presetCountryId => &$rqList)
			{
				foreach ($rqList as $rqHash => &$rqInfo)
				{
					$rqInfo['CO_ID'] = (int)$info['ID'];
					$rqInfo['PRESET_COUNTRY_ID'] = $presetCountryId;
					$requisiteId = isset($rqHashList[$presetCountryId][$rqHash]) ?
						(int)$rqHashList[$presetCountryId][$rqHash] : 0;
					if ($requisiteId > 0)
					{
						$rqInfo['IS_NEW'] = 'N';
					}
					else
					{
						$requisiteId = self::addRequisite($rqInfo);
						$rqInfo['IS_NEW'] = 'Y';
					}
					$rqInfo['ID'] = $requisiteId;
					if ($requisiteId > 0)
					{
						if (is_array($rqInfo['PS']))
						{
							foreach ($rqInfo['PS'] as $psId)
							{
								self::$psEntityTreeMap[$psId] = array(
									'CO' => $rqInfo['CO_ID'],
									'RQ' => $requisiteId,
									'BD' => 0
								);
							}
						}
						self::saveRequisiteBankDetail($rqInfo);
					}
				}
				unset($rqInfo);
			}
			unset($rqList);
		}
	}

	private static function saveRequisiteBankDetail(&$info)
	{
		if (self::$psEntityTreeMap === null)
			self::$psEntityTreeMap = array();

		$presetCountryId = isset($info['PRESET_COUNTRY_ID']) ? (int)$info['PRESET_COUNTRY_ID'] : 0;
		if (isset($info['ID']) && $info['ID'] > 0
			&& isset($info['CO_ID']) && $info['CO_ID'] > 0
			&& $presetCountryId > 0
			&& is_array($info['BD']) && is_array($info['BD']) && !empty($info['BD'])
			&& is_array(self::$convFieldSets['BD'][$presetCountryId])
			&& !empty(self::$convFieldSets['BD'][$presetCountryId]))
		{
			// prepare hashes of existing bank details
			$bdHashList = array();
			if (isset($info['IS_NEW']) && $info['IS_NEW'] === 'N')
			{
				$bankDetail = new EntityBankDetail();
				$rqFieldsByCountry = $bankDetail->getRqFieldByCountry();
				$rqFieldsByCountry = isset($rqFieldsByCountry[$presetCountryId]) ?
					$rqFieldsByCountry[$presetCountryId] : array();
				$res = $bankDetail->getList(
					array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \CCrmOwnerType::Requisite,
							'=ENTITY_ID' => (int)$info['ID'],
							'=COUNTRY_ID' => $presetCountryId
						),
						'select' => array_merge(array('ID'), $rqFieldsByCountry)
					)
				);
				while ($row = $res->fetch())
				{
					$bankDetailId = isset($row['ID']) ? (int)$row['ID'] : 0;
					if ($bankDetailId > 0 && $presetCountryId > 0)
					{
						$hashData = '|'.$presetCountryId;
						foreach (array_keys(self::$convFieldSets['BD'][$presetCountryId]) as $fieldName)
						{
							$value = isset($row[$fieldName]) ? trim(strval($row[$fieldName])) : '';
							$hashData .= '|'.$value;
						}
						$bdHashList[md5($hashData)] = $bankDetailId;
					}
				}
				unset($res, $row, $hashData, $value, $bankDetailId);
			}

			// add bank details
			foreach ($info['BD'] as $bdHash => &$bdInfo)
			{
				$bdInfo['CO_ID'] = (int)$info['CO_ID'];
				$bdInfo['RQ_ID'] = (int)$info['ID'];
				$bdInfo['COUNTRY_ID'] = $presetCountryId;
				$bankDetailId = isset($bdHashList[$bdHash]) ?
					(int)$bdHashList[$bdHash] : 0;
				if ($bankDetailId > 0)
				{
					$bdInfo['IS_NEW'] = 'N';
				}
				else
				{
					$bankDetailId = self::addBankDetail($bdInfo);
					$bdInfo['IS_NEW'] = 'Y';
				}
				$bdInfo['ID'] = $bankDetailId;
				if ($bankDetailId > 0 && is_array($info['PS']))
				{
					if (is_array($bdInfo['PS']))
					{
						foreach ($bdInfo['PS'] as $psId)
						{
							self::$psEntityTreeMap[$psId] = array(
								'CO' => $bdInfo['CO_ID'],
								'RQ' => $bdInfo['RQ_ID'],
								'BD' => $bankDetailId
							);
						}
					}
				}
			}
			unset($rqInfo);
		}
	}

	private static function saveEntities()
	{
		if (self::$psEntityTreeMap === null)
			self::$psEntityTreeMap = array();

		if (is_array(self::$entityTree))
		{
			foreach (self::$entityTree as &$coInfo)
			{
				$companyId = self::findCompanyByTitle($coInfo['CO']['TITLE']);
				if ($companyId > 0)
				{
					$coInfo['IS_NEW'] = 'N';
					self::updateCompanyMF($companyId, $coInfo);
				}
				else
				{
					$companyId = self::addCompany($coInfo);
					$coInfo['IS_NEW'] = 'Y';
				}
				$coInfo['ID'] = $companyId;
				if ($companyId > 0)
				{
					if (is_array($coInfo['PS']))
					{
						foreach ($coInfo['PS'] as $psId)
						{
							self::$psEntityTreeMap[$psId] = array(
								'CO' => $companyId,
								'RQ' => 0,
								'BD' => 0
							);
						}
					}
					self::saveCompanyRequisite($coInfo);
				}
			}
			unset($coInfo);
			
			foreach (self::$psEntityTreeMap as $psId => $relations)
			{
				EntityPSRequisiteRelation::register(
					$psId,
					$relations['CO'],
					$relations['RQ'],
					$relations['BD']
				);
			}
		}
	}

	private static function updatePSParams()
	{
		if (!is_array(self::$psEntityTreeMap))
			return;

		$paySystem = new \CSalePaySystemAction();

		foreach (self::$paySystemData as $psInfo)
		{
			if (is_array($psInfo) && is_array($psInfo['PSA_PARAMS']) && is_array($psInfo['PSA_PARAMS_ORIG']))
			{
				$psId = isset($psInfo['PS_ID']) ? (int)$psInfo['PS_ID'] : 0;
				$relatedCompanyId = isset(self::$psEntityTreeMap[$psId]['CO']) ?
					(int)self::$psEntityTreeMap[$psId]['CO'] : 0;
				$paramsAllEmpty =  isset($psInfo['PSA_PARAMS_ALL_EMPTY']) ?
					($psInfo['PSA_PARAMS_ALL_EMPTY'] === 'Y' ? 'Y' : 'N') : 'N';
				if ($relatedCompanyId > 0 || $paramsAllEmpty === 'Y')
				{
					$psaId = isset($psInfo['PSA_ID']) ? (int)$psInfo['PSA_ID'] : 0;
					$personTypeId = isset($psInfo['PSA_PERSON_TYPE_ID']) ? (int)$psInfo['PSA_PERSON_TYPE_ID'] : 0;
					$presetCountryId = isset($psInfo['PRESET_COUNTRY_ID']) ? (int)$psInfo['PRESET_COUNTRY_ID'] : 0;
					$origPsaParams = $psInfo['PSA_PARAMS_ORIG'];

					// convert params
					$origPsaParamsModified = false;
					foreach ($psInfo['PSA_PARAMS'] as $paramName => $convInfo)
					{
						if (isset($convInfo['VALUE']) && (!empty($convInfo['VALUE']) || $paramsAllEmpty === 'Y'))
						{
							$paramType = '';
							$paramValue = '';
							switch ($convInfo['ENTITY_ABBR'])
							{
								case 'CO':
									$paramType = 'CRM_MYCOMPANY';
									$paramValue = $convInfo['FIELD_NAME'];
									break;
								case 'CO.MF':
									$paramType = 'CRM_MYCOMPANY';
									$paramValue = $convInfo['FIELD_NAME'];
									if (isset($convInfo['MF_VALUE_TYPE']) && !empty($convInfo['MF_VALUE_TYPE']))
										$paramValue .= '_'.$convInfo['MF_VALUE_TYPE'];
									break;
								case 'RQ':
									if ($presetCountryId > 0)
									{
										$paramType = 'MC_REQUISITE';
										$paramValue = $convInfo['FIELD_NAME'];
										if ($paramValue === EntityRequisite::ADDRESS && isset($convInfo['ADDR_TYPE']))
											$paramValue .= '_'.(int)$convInfo['ADDR_TYPE'];
										$paramValue .= '|'.$presetCountryId;
									}
									break;
								case 'BD':
									if ($presetCountryId > 0)
									{
										$paramType = 'MC_BANK_DETAIL';
										$paramValue = $convInfo['FIELD_NAME'].'|'.$presetCountryId;
									}
									break;
							}
							if (!empty($paramType) && !empty($paramValue))
							{
								if (is_array($origPsaParams[$paramName]))
								{
									$origPsaParams[$paramName]['TYPE'] = $paramType;
									$origPsaParams[$paramName]['VALUE'] = $paramValue;
									$origPsaParamsModified = true;
								}
							}
						}
					}

					// save modified params
					if ($origPsaParamsModified)
					{
						$psaParams = \CSalePaySystemAction::SerializeParams($origPsaParams);
						$psaFields = array('PARAMS' => $psaParams, 'PERSON_TYPE_ID' => $personTypeId);
						$paySystem->Update($psaId, $psaFields);
					}
				}
			}
		}
	}

	private static function makeConvPresets()
	{
		if (self::$convPresets === null)
		{
			$presetData = array();
			$maxSort = null;
			$sortIndex = 0;
			$currentCountryId = EntityPreset::getCurrentCountryId();
			$psData = self::$paySystemData;
			if (is_array($psData) || !empty($psData))
			{
				$preset = new EntityPreset();
				foreach ($psData as $psInfo)
				{
					$presetCountryId = isset($psInfo['PRESET_COUNTRY_ID']) ? (int)$psInfo['PRESET_COUNTRY_ID'] : 0;
					if ($presetCountryId > 0
						&& in_array($presetCountryId, EntityRequisite::getAllowedRqFieldCountries(), true))
					{
						if (!isset($presetData[$presetCountryId]))
						{
							$countryCode = EntityPreset::getCountryCodeById($presetCountryId);
							if (!empty($countryCode))
							{
								$presetXmlId = '#CRM_REQUISITE_PRESET_'.$countryCode.'_SELLER#';

								// load existing preset
								$res = $preset->getList(
									array(
										'filter' => array('=XML_ID' => $presetXmlId),
										'select' => array(
											'ID',
											'ENTITY_TYPE_ID',
											'COUNTRY_ID',
											'NAME',
											'ACTIVE',
											'XML_ID',
											'SORT',
											'SETTINGS'
										),
										'limit' => 1
									)
								);
								if ($row = $res->fetch())
								{
									if (is_array($row))
										$presetData[$presetCountryId] = $row;
								}
								unset($res, $row);

								if (!isset($presetData[$presetCountryId]))
								{
									// prepare new preset
									$prefix = '#CRM_REQUISITE_PRESET_DEF_';
									$postfix = $countryCode === 'RU' ? '_COMPANY#' : '_LEGALENTITY#';
									$fixedPresetXmlId = $prefix.$countryCode.$postfix;
									$presetFields = null;
									foreach (EntityRequisite::getFixedPresetList() as $presetInfo)
									{
										if (isset($presetInfo['XML_ID']) && $presetInfo['XML_ID'] === $fixedPresetXmlId)
										{
											$presetFields = $presetInfo;
											break;
										}
									}
									if (is_array($presetFields))
									{
										unset($presetFields['ID']);
										$presetFields['NAME'] = GetMessage('CRM_PS_RQ_CONV_PRESET_NAME').
											($presetCountryId === $currentCountryId ? '' : " ($countryCode)");
										$presetFields['XML_ID'] = $presetXmlId;
										if ($maxSort === null)
										{
											$res = $preset->getList(
												array(
													'order' => array('SORT' => 'DESC', 'ID' => 'DESC'),
													'select' => array('ID', 'SORT'),
													'limit' => 1
												)
											);
											if ($row = $res->fetch())
											{
												if (is_array($row) && isset($row['SORT']))
													$maxSort = (int)$row['SORT'];
											}
											if ($maxSort === null || $maxSort < 0)
												$maxSort = 500;
											unset($res, $row);
										}
										$presetFields['SORT'] = $maxSort + ($sortIndex += 10);

										$presetData[$presetCountryId] = $presetFields;
									}
								}
							}
						}

						$presetModified = false;
						if (is_array($presetData[$presetCountryId]) && is_array($presetData[$presetCountryId]['SETTINGS']))
						{
							$presetFields = $presetData[$presetCountryId];

							// add fields
							$maxFieldSort = $sort = 0;
							$fieldsOfPreset = array();
							foreach ($preset->settingsGetFields($presetFields['SETTINGS']) as $fieldInfo)
							{
								if (isset($fieldInfo['FIELD_NAME']))
									$fieldsOfPreset[$fieldInfo['FIELD_NAME']] = true;
								$sort = isset($fieldInfo['SORT']) ? (int)$fieldInfo['SORT'] : 0;
								if ($sort > $maxFieldSort)
									$maxFieldSort = $sort;
							}
							$sort = $maxFieldSort;
							unset($maxFieldSort);

							// verify company name exists
							if (!isset($fieldsOfPreset['RQ_COMPANY_NAME']))
							{
								$preset->settingsAddField($presetFields['SETTINGS'], array(
									'FIELD_NAME' => 'RQ_COMPANY_NAME',
									'FIELD_TITLE' => '',
									'IN_SHORT_LIST' => 'N',
									'SORT' => $sort += 10
								));
								$presetModified = true;
							}

							foreach ($psInfo['PSA_PARAMS'] as $paramInfo)
							{
								if ($paramInfo['ENTITY_ABBR'] === 'RQ'
									&& !isset($fieldsOfPreset[$paramInfo['FIELD_NAME']]))
								{
									$preset->settingsAddField($presetFields['SETTINGS'], array(
										'FIELD_NAME' => $paramInfo['FIELD_NAME'],
										'FIELD_TITLE' => '',
										'IN_SHORT_LIST' => 'N',
										'SORT' => $sort += 10
									));
									$presetModified = true;
								}
							}

							// add/update preset
							if (isset($presetFields['ID']))
							{
								if ($presetModified)
									$preset->update((int)$presetFields['ID'], $presetFields);
							}
							else
							{
								$result = $preset->add($presetFields);
								if ($result->isSuccess())
									$presetData[$presetCountryId]['ID'] = $result->getId();
							}
						}
					}
				}
			}

			self::$convPresets = $presetData;
		}
	}

	private static function findCompanyByTitle($companyTitle)
	{
		$companyId = 0;

		if (!empty($companyTitle))
		{
			$res = \CCrmCompany::GetListEx(
				array('ID' => 'DESC'),
				array('=TITLE' => $companyTitle),
				false,
				array('nTopCount' => 1),
				array('ID')
			);
			$row = $res->Fetch();
			if (is_array($row) && isset($row['ID']) && $row['ID'] > 0)
				$companyId = (int)$row['ID'];
		}

		return $companyId;
	}
	
	private static function loadPSEntityTreeMap()
	{
		if (self::$psEntityTreeMap === null)
		{
			self::$psEntityTreeMap = array();
			$res = PSRequisiteRelationTable::getList(
				array(
					'select' => array('ENTITY_ID', 'COMPANY_ID', 'REQUISITE_ID', 'BANK_DETAIL_ID')
				)
			);
			while ($row = $res->fetch())
			{
				self::$psEntityTreeMap[(int)$row['ENTITY_ID']] = array(
					'CO' => (int)$row['COMPANY_ID'],
					'RQ' => (int)$row['REQUISITE_ID'],
					'BD' => (int)$row['BANK_DETAIL_ID']
				);
			}
		}
	}
	
	private static function updateInvoices()
	{
		$firstInvoiceId = 0;
		$lastInvoiceId = 0;
		$countInvoiceUpdated = 0;
		$countInvoice = 0;

		$progressData = self::getProgressData();

		if (!is_array(self::$psEntityTreeMap) || empty(self::$psEntityTreeMap))
			return true;

		$nextInvoiceId = 0;
		if (is_array($progressData))
		{
			if (isset($progressData['INVOICES']) && $progressData['INVOICES'] === 'Y')
				return true;

			if (isset($progressData['NEXT_INVOICE_ID']) && $progressData['NEXT_INVOICE_ID'] > 0)
				$nextInvoiceId = (int)$progressData['NEXT_INVOICE_ID'];

			if (isset($progressData['FIRST_INVOICE_ID']))
			{
				$firstInvoiceId = (int)$progressData['FIRST_INVOICE_ID'];
			}
			else
			{
				$res = Invoice\Internals\InvoiceTable::getList(array(
					'order' => array('ID' => 'ASC'),
					'filter' => array(
						'=PAY_SYSTEM_ID' => array_keys(self::$psEntityTreeMap)
					),
					'select' => array('ID'),
					'limit' => 1
				));
				if ($row = $res->fetch())
				{
					$firstInvoiceId = (int)$row['ID'];
				}
			}
			if ($nextInvoiceId <= 0 && $firstInvoiceId > 0)
				$nextInvoiceId = $firstInvoiceId;
			
			if (isset($progressData['LAST_INVOICE_ID']))
			{
				$lastInvoiceId = (int)$progressData['LAST_INVOICE_ID'];
			}
			else
			{
				$res = Invoice\Internals\InvoiceTable::getList(array(
					'order' => array('ID' => 'DESC'),
					'filter' => array(
						'=PAY_SYSTEM_ID' => array_keys(self::$psEntityTreeMap)
					),
					'select' => array('ID'),
					'limit' => 1
				));
				if ($row = $res->fetch())
				{
					$lastInvoiceId = (int)$row['ID'];
				}
			}

			if (isset($progressData['COUNT_INVOICE_UPDATED']))
			{
				$countInvoiceUpdated = (int)$progressData['COUNT_INVOICE_UPDATED'];
			}
			else
			{
				$countInvoiceUpdated = Invoice\Internals\InvoiceTable::getCount(
					array(
						'=PAY_SYSTEM_ID' => array_keys(self::$psEntityTreeMap),
						'<ID' => $nextInvoiceId
					)
				);
			}

			if (isset($progressData['COUNT_INVOICE']))
			{
				$countInvoice = (int)$progressData['COUNT_INVOICE'];
			}
			else
			{
				$countInvoice = Invoice\Internals\InvoiceTable::getCount(
					array(
						'=PAY_SYSTEM_ID' => array_keys(self::$psEntityTreeMap)
					)
				);
			}
		}

		if ($firstInvoiceId <= 0 || $lastInvoiceId <= 0 || $firstInvoiceId > $lastInvoiceId || $nextInvoiceId <= 0
			|| $nextInvoiceId < $firstInvoiceId || $nextInvoiceId > $lastInvoiceId)
		{
			return true;
		}

		self::updateProgressData(
			array(
				'FIRST_INVOICE_ID' => $firstInvoiceId,
				'LAST_INVOICE_ID' => $lastInvoiceId,
				'COUNT_INVOICE_UPDATED' => $countInvoiceUpdated,
				'COUNT_INVOICE' => $countInvoice
			)
		);

		$res = Invoice\Internals\InvoiceTable::getList(
			array(
				'order' => array('ID' => 'ASC'),
				'filter' => array(
					'=PAY_SYSTEM_ID' => array_keys(self::$psEntityTreeMap),
					'><ID' => array($nextInvoiceId, $lastInvoiceId),
				),
				'select' => array('ID', 'PAY_SYSTEM_ID'),
				'limit' => self::$stepInvoiceCount + 1
			)
		);
		$nRow = 0;
		while (($row = $res->fetch()) && $nextInvoiceId > 0)
		{
			$nRow++;
			$curInvoiceId = (int)$row['ID'];
			if ($nRow === self::$stepInvoiceCount)
			{
				$nextInvoiceId = $curInvoiceId;
				break;
			}

			self::updateInvoice($row);

			if ($curInvoiceId > $nextInvoiceId)
			{
				$nextInvoiceId = $curInvoiceId;
				$countInvoiceUpdated++;
			}
			if ($curInvoiceId >= $lastInvoiceId)
				$nextInvoiceId = 0;

		}

		self::updateProgressData(
			array(
				'NEXT_INVOICE_ID' => $nextInvoiceId,
				'COUNT_INVOICE_UPDATED' => $countInvoiceUpdated
			)
		);

		return ($nextInvoiceId <= 0);
	}

	private static function updateInvoice($info)
	{
		if (is_array(self::$psEntityTreeMap) && is_array($info))
		{
			$id = isset($info['ID']) ? (int)$info['ID'] : 0;
			$psId = isset($info['PAY_SYSTEM_ID']) ? (int)$info['PAY_SYSTEM_ID'] : 0;
			if ($id > 0 && $psId > 0 && is_array(self::$psEntityTreeMap[$psId]))
			{
				$myCompanyId = isset(self::$psEntityTreeMap[$psId]['CO']) ?
					(int)self::$psEntityTreeMap[$psId]['CO'] : 0;
				$mcRequisiteId = isset(self::$psEntityTreeMap[$psId]['RQ']) ?
					(int)self::$psEntityTreeMap[$psId]['RQ'] : 0;
				$mcBankDetailId = isset(self::$psEntityTreeMap[$psId]['BD']) ?
					(int)self::$psEntityTreeMap[$psId]['BD'] : 0;

				if ($myCompanyId > 0)
				{
					Invoice\Internals\InvoiceTable::update($id, array('UF_MYCOMPANY_ID' => $myCompanyId));
				}
				if ($mcRequisiteId > 0)
				{
					$bindings = EntityLink::getByEntity(\CCrmOwnerType::Invoice, $id);
					if (!is_array($bindings))
					{
						$bindings = array(
							'REQUISITE_ID' => 0,
							'BANK_DETAIL_ID' => 0
						);
					}
					EntityLink::register(
						\CCrmOwnerType::Invoice, $id,
						$bindings['REQUISITE_ID'], $bindings['BANK_DETAIL_ID'],
						$mcRequisiteId, $mcBankDetailId
					);
				}
			}
		}
	}
	
	private static function initialize()
	{
		// backup pay system actions table
		$connection = Main\Application::getConnection();
		if ($connection->isTableExists('b_sale_pay_system_action')
			&& !$connection->isTableExists('b_sale_pay_system_ac_bu'))
		{
			$sql = [];
			if ($connection instanceof Main\DB\MysqlCommonConnection)
			{
				$sql[] = /** @lang MySQL */
					"CREATE TABLE b_sale_pay_system_ac_bu AS SELECT * FROM b_sale_pay_system_action LIMIT 0";
				/** @noinspection SqlInsertValues */
				$sql[] = /** @lang MySQL */
					"INSERT INTO b_sale_pay_system_ac_bu SELECT * FROM b_sale_pay_system_action";
			}
			elseif ($connection instanceof Main\DB\MssqlConnection)
			{
				$sql[] = /** @lang TSQL */
					"SELECT * INTO B_SALE_PAY_SYSTEM_AC_BU FROM B_SALE_PAY_SYSTEM_ACTION";
			}
			elseif ($connection instanceof Main\DB\OracleConnection)
			{
				$sql[] = /** @lang Oracle */
					"CREATE TABLE B_SALE_PAY_SYSTEM_AC_BU AS SELECT * FROM B_SALE_PAY_SYSTEM_ACTION";
			}
			if (!empty($sql))
			{
				foreach ($sql as $sqlStr)
				{
					$connection->queryExecute($sqlStr);
				}
			}
		}

		EntityPSRequisiteRelation::unregisterAll();
	}

	public static function convert()
	{
		if(!Main\Loader::includeModule('crm'))
			throw new Main\SystemException('Could not include crm module.');

		if(!Main\Loader::includeModule('sale'))
			throw new Main\SystemException('Could not include sale module.');

		$progressData = self::getProgressData();

		if (!isset($progressData['INIT']) || $progressData['INIT'] !== 'Y')
		{
			self::initialize();
			self::updateProgressData(array('INIT' => 'Y', 'INIT_TIME' => time()));
		}

		if (!isset($progressData['PRESETS']) || $progressData['PRESETS'] !== 'Y')
		{
			self::parsePaySystemData();
			self::makeConvPresets();
			self::updateProgressData(array('PRESETS' => 'Y'));
			return;
		}

		if (!isset($progressData['REQUISITE']) || $progressData['REQUISITE'] !== 'Y')
		{
			self::parsePaySystemData();
			self::prepareConvFieldSets();
			self::preparePaySystemConvData();
			self::prepareEntityTree();
			self::saveEntities();
			self::updatePSParams();
			self::updateProgressData(array('REQUISITE' => 'Y'));
			return;
		}

		if (!isset($progressData['INVOICES']) || $progressData['INVOICES'] !== 'Y')
		{
			self::loadPSEntityTreeMap();
			self::$startTime = time();
			while (self::$stepTime >= (time() - self::$startTime))
			{
				if (self::updateInvoices())
				{
					self::updateProgressData(array('PS_COMPLETE' => 'Y'));
					break;
				}
			}
		}

		return;
	}

	public static function skipConvert()
	{
		self::removeTriggerOption();
	}

	public static function complete()
	{
		self::removeProgressData();
		self::removeTriggerOption();
	}

	public static function getIntroMessage(array $params)
	{
		$replace = array();
		if(isset($params['EXEC_ID']))
		{
			$replace['#EXEC_ID#'] = $params['EXEC_ID'];
		}

		if(isset($params['EXEC_URL']))
		{
			$replace['#EXEC_URL#'] = $params['EXEC_URL'];
		}

		if(isset($params['SKIP_ID']))
		{
			$replace['#SKIP_ID#'] = $params['SKIP_ID'];
		}

		if(isset($params['SKIP_URL']))
		{
			$replace['#SKIP_URL#'] = $params['SKIP_URL'];
		}

		Loc::loadMessages(__FILE__);
		return GetMessage('CRM_PS_RQ_CONV_INTRO', $replace);
	}
}
