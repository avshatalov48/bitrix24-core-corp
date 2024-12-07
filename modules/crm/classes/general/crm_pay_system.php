<?php

use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\RequisiteAddress;

IncludeModuleLangFile(__FILE__);

class CCrmPaySystem
{
	const DEFAULT_HANDLER_GROUP = 'PS_OTHER';

	const PERSON_TYPE_NAME_COMPANY = 'CRM_COMPANY';
	const PERSON_TYPE_NAME_CONTACT = 'CRM_CONTACT';

	private static $arActFiles = array();
	private static $arCrmCompatibleActs = array(
		'bill', 'billua', 'billen', 'billde', 'billla', 'billkz', 'billby', 'billbr', 'billfr',
		'paypal', 'yandexinvoice', 'yandex', 'yandexcheckout', 'invoicedocument', 'bepaid', 'roboxchange',
		'sberbankonline', 'uapay', 'alfabank', 'wooppay'
	);
	private static $paySystems = null;
	private static $defBuyerParamsMap = null;
	private static $defMyCompanyParamsMap = null;
	private static $rqCountryIds = null;

	public static function LocalGetPSActionParams($fileName)
	{
		$arPSCorrespondence = array();

		if (file_exists($fileName) && is_file($fileName))
			include($fileName);

		if (isset($data))
			$arPSCorrespondence = self::convertNewToOld($data);
		return $arPSCorrespondence;
	}

	private static function convertNewToOld($data)
	{
		foreach ($data['CODES'] as &$code)
		{
			if (!array_key_exists('GROUP', $code))
				$code['GROUP'] = self::DEFAULT_HANDLER_GROUP;

			if (isset($code['DESCRIPTION']))
			{
				$code['DESCR'] = $code['DESCRIPTION'];
				unset($code['DESCRIPTION']);
			}
			else
			{
				$code['DESCR'] = '';
			}

			if (isset($code['INPUT']))
			{
				if ($code['INPUT']['TYPE'] == 'ENUM')
				{
					$code['TYPE'] = 'SELECT';

					foreach ($code['INPUT']['OPTIONS'] as $key => $value)
						$code['VALUE'][$key] = $value;
				}
				elseif ($code['INPUT']['TYPE'] == 'Y/N')
				{
					$code['TYPE'] = 'CHECKBOX';
				}
				else
				{
					$code['TYPE'] = $code['INPUT']['TYPE'];
				}
				unset($code['INPUT']);
			}

			if (isset($code['DEFAULT']) && $code['TYPE'] !== 'SELECT')
			{
				$type = null;
				$code['VALUE'] = $code['DEFAULT']['PROVIDER_VALUE'];
				if ($code['DEFAULT']['PROVIDER_KEY'] == 'VALUE')
				{
					$type = '';
				}
				else
				{
					if ($code['DEFAULT']['PROVIDER_KEY'] !== 'INPUT')
						$type = $code['DEFAULT']['PROVIDER_KEY'];
				}

				if ($type !== null)
					$code['TYPE'] = $type;
				unset($code['DEFAULT']);
			}
			elseif (!isset($code['TYPE']))
			{
				$code['TYPE'] = '';
				$code['VALUE'] = '';
			}
		}
		unset($code);

		return $data['CODES'];
	}

	private static function initDefaultBuyerParamsMap()
	{
		if (self::$rqCountryIds === null)
		{
			self::$rqCountryIds = array(
				'RU' => GetCountryIdByCode('RU'),
				'BY' => GetCountryIdByCode('BY'),
				'KZ' => GetCountryIdByCode('KZ'),
				'UA' => GetCountryIdByCode('UA'),
				'DE' => GetCountryIdByCode('DE'),
				'US' => GetCountryIdByCode('US')
			);
		}

		$idRU = self::$rqCountryIds['RU'];
		$idBY = self::$rqCountryIds['BY'];
		$idKZ = self::$rqCountryIds['KZ'];
		$idUA = self::$rqCountryIds['UA'];
		$idDE = self::$rqCountryIds['DE'];
		$idUS = self::$rqCountryIds['US'];

		if (self::$defBuyerParamsMap === null)
		{
			self::$defBuyerParamsMap = array(
				self::PERSON_TYPE_NAME_COMPANY => array(
					'bill' => array(
						$idRU => array(
							"BUYER_PERSON_COMPANY_NAME" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_COMPANY_NAME|$idRU"
							),
							"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_INN|$idRU"),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Registered."|$idRU"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idRU"),
							"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_FAX|$idRU"),
							"BUYER_PERSON_COMPANY_NAME_CONTACT" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_CONTACT|$idRU"
							)
						),
						$idBY => array(
							"BUYER_PERSON_COMPANY_NAME" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_COMPANY_NAME|$idBY"
							),
							"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_INN|$idBY"),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Registered."|$idBY"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idBY"),
							"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_FAX|$idBY"),
							"BUYER_PERSON_COMPANY_NAME_CONTACT" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_CONTACT|$idBY"
							),
							'BUYER_PERSON_COMPANY_BANK_NAME' => array(
								'TYPE' => 'BANK_DETAIL', 'VALUE' => 'RQ_BANK_NAME|'.$idBY
							),
							'BUYER_PERSON_COMPANY_BANK_CITY' => array(
								'TYPE' => 'BANK_DETAIL', 'VALUE' => 'RQ_BANK_ADDR|'.$idBY
							),
							'BUYER_PERSON_COMPANY_BANK_ACCOUNT' => array(
								'TYPE' => 'BANK_DETAIL', 'VALUE' => 'RQ_ACC_NUM|'.$idBY
							),
							'BUYER_PERSON_COMPANY_BANK_BIC' => array(
								'TYPE' => 'BANK_DETAIL', 'VALUE' => 'RQ_BIK|'.$idBY
							)
						),
						$idKZ => array(
							'BUYER_PERSON_COMPANY_NAME' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_COMPANY_FULL_NAME|'.$idKZ
							),
							'BUYER_PERSON_COMPANY_IIN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_IIN|'.$idKZ),
							'BUYER_PERSON_COMPANY_BIN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_BIN|'.$idKZ),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Registered."|$idKZ"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idKZ"),
							"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_FAX|$idKZ"),
							"BUYER_PERSON_COMPANY_NAME_CONTACT" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_CONTACT|$idKZ"
							),
						),
						$idUA => array(
							"BUYER_PERSON_COMPANY_NAME" => array(
								"TYPE" => "REQUISITE",
								"VALUE" => "RQ_COMPANY_NAME|$idUA"
							),
							"BUYER_PERSON_COMPANY_INN" => array(
								"TYPE" => "REQUISITE",
								"VALUE" => "RQ_INN|$idUA"
							),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE",
								"VALUE" => "RQ_ADDR_".RequisiteAddress::Registered."|$idUA"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array(
								"TYPE" => "REQUISITE",
								"VALUE" => "RQ_PHONE|$idUA"
							),
							"BUYER_PERSON_COMPANY_FAX" => array(
								"TYPE" => "REQUISITE",
								"VALUE" => "RQ_FAX|$idUA"
							)
						),
						$idDE => array(
							"BUYER_PERSON_COMPANY_NAME" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_COMPANY_NAME|$idDE"
							),
							"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_INN|$idDE"),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Registered."|$idDE"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idDE"),
							"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_FAX|$idDE"),
							"BUYER_PERSON_COMPANY_NAME_CONTACT" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_CONTACT|$idDE"
							)
						),
						$idUS => array(
							"BUYER_PERSON_COMPANY_NAME" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_COMPANY_NAME|$idUS"
							),
							"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_INN|$idUS"),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Registered."|$idUS"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idUS"),
							"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_FAX|$idUS"),
							"BUYER_PERSON_COMPANY_NAME_CONTACT" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_CONTACT|$idUS"
							)
						)
					),
					'quote' => array(
						$idRU => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_COMPANY_NAME|'.$idRU),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idRU),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idRU
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idRU),
							'BUYER_FAX' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_FAX|'.$idRU),
							'BUYER_PAYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_CONTACT|'.$idRU),
						),
						$idBY => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_COMPANY_NAME|'.$idBY),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idBY),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idBY
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idBY),
							'BUYER_FAX' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_FAX|'.$idBY),
							'BUYER_PAYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_CONTACT|'.$idBY),
						),
						$idKZ => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_COMPANY_NAME|'.$idKZ),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idKZ),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idKZ
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idKZ),
							'BUYER_FAX' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_FAX|'.$idKZ),
							'BUYER_PAYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_CONTACT|'.$idKZ),
						),
						$idUA => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_COMPANY_NAME|'.$idUA),
							'BUYER_EDRPOU' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_EDRPOU|'.$idUA),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idUA
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idUA),
							'BUYER_FAX' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_FAX|'.$idUA),
							'BUYER_PAYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_CONTACT|'.$idUA),
						),
						$idDE => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_COMPANY_NAME|'.$idDE),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idDE),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idDE
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idDE),
							'BUYER_FAX' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_FAX|'.$idDE),
							'BUYER_PAYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_CONTACT|'.$idDE),
						),
						$idUS => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_COMPANY_NAME|'.$idUS),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idUS),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idUS
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idUS),
							'BUYER_FAX' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_FAX|'.$idUS),
							'BUYER_PAYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_CONTACT|'.$idUS),
						)
					)
				),
				self::PERSON_TYPE_NAME_CONTACT => array(
					'bill' => array(
						$idRU => array(
							"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_NAME|$idRU"),
							"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_INN|$idRU"),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Primary."|$idRU"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idRU"),
							"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "", "VALUE" => ""),
							"BUYER_PERSON_COMPANY_NAME_CONTACT" => array("TYPE" => "", "VALUE" => "")
						),
						$idBY => array(
							"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_NAME|$idBY"),
							"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_INN|$idBY"),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Primary."|$idBY"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idBY"),
							'BUYER_PERSON_COMPANY_BANK_NAME' => array(
								'TYPE' => 'BANK_DETAIL', 'VALUE' => 'RQ_BANK_NAME|'.$idBY
							),
							'BUYER_PERSON_COMPANY_BANK_CITY' => array(
								'TYPE' => 'BANK_DETAIL', 'VALUE' => 'RQ_BANK_ADDR|'.$idBY
							),
							'BUYER_PERSON_COMPANY_BANK_ACCOUNT' => array(
								'TYPE' => 'BANK_DETAIL', 'VALUE' => 'RQ_ACC_NUM|'.$idBY
							),
							'BUYER_PERSON_COMPANY_BANK_BIC' => array(
								'TYPE' => 'BANK_DETAIL', 'VALUE' => 'RQ_BIK|'.$idBY
							)
						),
						$idKZ => array(
							"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_NAME|$idKZ"),
							'BUYER_PERSON_COMPANY_IIN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_IIN|'.$idKZ),
							'BUYER_PERSON_COMPANY_BIN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_BIN|'.$idKZ),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Primary."|$idKZ"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idKZ")
						),
						$idUA => array(
							"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_NAME|$idUA"),
							"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_INN|$idUA"),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE",
								"VALUE" => "RQ_ADDR_".RequisiteAddress::Primary."|$idUA"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idUA"),
							"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_FAX|$idUA")
						),
						$idDE => array(
							"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_NAME|$idDE"),
							"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_INN|$idDE"),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Primary."|$idDE"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idDE"),
							"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "", "VALUE" => ""),
							"BUYER_PERSON_COMPANY_NAME_CONTACT" => array("TYPE" => "", "VALUE" => "")
						),
						$idUS => array(
							"BUYER_PERSON_COMPANY_NAME" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_NAME|$idUS"),
							"BUYER_PERSON_COMPANY_INN" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_INN|$idUS"),
							"BUYER_PERSON_COMPANY_ADDRESS" => array(
								"TYPE" => "REQUISITE", "VALUE" => "RQ_ADDR_".RequisiteAddress::Primary."|$idUS"
							),
							"BUYER_PERSON_COMPANY_PHONE" => array("TYPE" => "REQUISITE", "VALUE" => "RQ_PHONE|$idUS"),
							"BUYER_PERSON_COMPANY_FAX" => array("TYPE" => "", "VALUE" => ""),
							"BUYER_PERSON_COMPANY_NAME_CONTACT" => array("TYPE" => "", "VALUE" => "")
						)
					),
					'quote' => array(
						$idRU => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_NAME|'.$idRU),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idRU),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Primary.'|'.$idRU
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idRU),
							'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
							'BUYER_PAYER_NAME' => array('TYPE' => '', 'VALUE' => '')
						),
						$idBY => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_NAME|'.$idBY),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idBY),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Primary.'|'.$idBY
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idBY),
							'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
							'BUYER_PAYER_NAME' => array('TYPE' => '', 'VALUE' => '')
						),
						$idKZ => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_NAME|'.$idKZ),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idKZ),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Primary.'|'.$idKZ
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idKZ),
							'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
							'BUYER_PAYER_NAME' => array('TYPE' => '', 'VALUE' => '')
						),
						$idUA => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_NAME|'.$idUA),
							'BUYER_EDRPOU' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_EDRPOU|'.$idUA),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Primary.'|'.$idUA
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idUA),
							'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
							'BUYER_PAYER_NAME' => array('TYPE' => '', 'VALUE' => '')
						),
						$idDE => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_NAME|'.$idDE),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idDE),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Primary.'|'.$idDE
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idDE),
							'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
							'BUYER_PAYER_NAME' => array('TYPE' => '', 'VALUE' => '')
						),
						$idUS => array(
							'BUYER_NAME' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_NAME|'.$idUS),
							'BUYER_INN' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_INN|'.$idUS),
							'BUYER_ADDRESS' => array(
								'TYPE' => 'REQUISITE', 'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Primary.'|'.$idUS
							),
							'BUYER_PHONE' => array('TYPE' => 'REQUISITE', 'VALUE' => 'RQ_PHONE|'.$idUS),
							'BUYER_FAX' => array('TYPE' => '', 'VALUE' => ''),
							'BUYER_PAYER_NAME' => array('TYPE' => '', 'VALUE' => '')
						)
					)
				)
			);
		}
	}

	private static function initDefaultMyCompanyParamsMap()
	{
		if (self::$rqCountryIds === null)
		{
			self::$rqCountryIds = array(
				'RU' => GetCountryIdByCode('RU'),
				'BY' => GetCountryIdByCode('BY'),
				'KZ' => GetCountryIdByCode('KZ'),
				'UA' => GetCountryIdByCode('UA'),
				'DE' => GetCountryIdByCode('DE'),
				'US' => GetCountryIdByCode('US')
			);
		}

		$idRU = self::$rqCountryIds['RU'];
		$idBY = self::$rqCountryIds['BY'];
		$idKZ = self::$rqCountryIds['KZ'];
		$idUA = self::$rqCountryIds['UA'];
		$idDE = self::$rqCountryIds['DE'];
		$idUS = self::$rqCountryIds['US'];

		if (self::$defMyCompanyParamsMap === null)
		{
			self::$defMyCompanyParamsMap = array(
				'bill' => array(
					$idRU => array(
						'SELLER_COMPANY_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_COMPANY_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idRU
						),
						'SELLER_COMPANY_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_COMPANY_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_INN|'.$idRU
						),
						'SELLER_COMPANY_KPP' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_KPP|'.$idRU
						),
						'SELLER_COMPANY_BANK_ACCOUNT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idRU
						),
						'SELLER_COMPANY_BANK_NAME' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idRU
						),
						'SELLER_COMPANY_BANK_CITY' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ADDR|'.$idRU
						),
						'SELLER_COMPANY_BANK_ACCOUNT_CORR' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_COR_ACC_NUM|'.$idRU
						),
						'SELLER_COMPANY_BANK_BIC' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BIK|'.$idRU
						),
						'SELLER_COMPANY_DIRECTOR_NAME' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_DIRECTOR|'.$idRU
						),
						'SELLER_COMPANY_ACCOUNTANT_NAME' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ACCOUNTANT|'.$idRU
						)
					),
					$idBY => array(
						'SELLER_COMPANY_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_COMPANY_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idBY
						),
						'SELLER_COMPANY_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_COMPANY_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_INN|'.$idBY
						),
						'SELLER_COMPANY_BANK_ACCOUNT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idBY
						),
						'SELLER_COMPANY_BANK_NAME' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idBY
						),
						'SELLER_COMPANY_BANK_CITY' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ADDR|'.$idBY
						),
						'SELLER_COMPANY_BANK_BIC' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BIK|'.$idBY
						),
						'SELLER_COMPANY_DIRECTOR_NAME' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_DIRECTOR|'.$idBY
						),
						'SELLER_COMPANY_ACCOUNTANT_NAME' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ACCOUNTANT|'.$idBY
						)
					),
					$idKZ => array(
						'SELLER_COMPANY_NAME' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_COMPANY_FULL_NAME|'.$idKZ
						),
						'SELLER_COMPANY_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idKZ
						),
						'SELLER_COMPANY_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_COMPANY_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_INN|'.$idKZ
						),
						'SELLER_COMPANY_IIN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_IIN|'.$idKZ
						),
						'SELLER_COMPANY_BIN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_BIN|'.$idKZ
						),
						'SELLER_COMPANY_KBE' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_KBE|'.$idKZ
						),
						'SELLER_COMPANY_BANK_ACCOUNT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_IIK|'.$idKZ
						),
						'SELLER_COMPANY_BANK_NAME' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idKZ
						),
						'SELLER_COMPANY_BANK_CITY' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ADDR|'.$idKZ
						),
						'SELLER_COMPANY_BANK_IIK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_IIK|'.$idKZ
						),
						'SELLER_COMPANY_BANK_ACCOUNT_CORR' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_COR_ACC_NUM|'.$idKZ
						),
						'SELLER_COMPANY_BANK_BIC' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BIK|'.$idKZ
						),
						'SELLER_COMPANY_DIRECTOR_POSITION' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_CEO_WORK_POS|'.$idKZ
						)
					),
					$idDE => array(
						'SELLER_COMPANY_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_COMPANY_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idDE
						),
						'SELLER_COMPANY_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_COMPANY_EMAIL' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'EMAIL_WORK'
						),
						'SELLER_COMPANY_BANK_ACCOUNT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idDE
						),
						'SELLER_COMPANY_BANK_NAME' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idDE
						),
						'SELLER_COMPANY_BANK_BIC' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ROUTE_NUM|'.$idDE
						),
						'SELLER_COMPANY_BANK_IBAN' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_IBAN|'.$idDE
						),
						'SELLER_COMPANY_BANK_SWIFT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_SWIFT|'.$idDE
						),
						'SELLER_COMPANY_EU_INN' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_VAT_ID|'.$idDE
						),
						'SELLER_COMPANY_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_INN|'.$idDE
						),
						'SELLER_COMPANY_REG' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_USRLE|'.$idDE
						)
					),
					$idUS => array(
						'SELLER_COMPANY_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_COMPANY_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idUS
						),
						'SELLER_COMPANY_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_COMPANY_BANK_NAME' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idUS
						),
						'SELLER_COMPANY_BANK_ACCOUNT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idUS
						),
						'SELLER_COMPANY_BANK_ADDR' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ADDR|'.$idUS
						),
						'SELLER_COMPANY_BANK_ACCOUNT_CORR' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ROUTE_NUM|'.$idUS
						),
						'SELLER_COMPANY_BANK_SWIFT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_SWIFT|'.$idUS
						)
					),
					$idUA => array(
						'SELLER_COMPANY_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_COMPANY_BANK_ACCOUNT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idUA
						),
						'SELLER_COMPANY_BANK_NAME' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idUA
						),
						'SELLER_COMPANY_MFO' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_MFO|'.$idUA
						),
						'SELLER_COMPANY_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idUA
						),
						'SELLER_COMPANY_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_COMPANY_EDRPOY' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_EDRPOU|'.$idUA
						),
						'SELLER_COMPANY_IPN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_INN|'.$idUA
						),
						'SELLER_COMPANY_PDV' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_VAT_CERT_NUM|'.$idUA
						),
						'SELLER_COMPANY_ACCOUNTANT_NAME' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ACCOUNTANT|'.$idUA
						)
					)
				),
				'quote' => array(
					$idRU => array(
						'SELLER_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idRU
						),
						'SELLER_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_INN|'.$idRU
						),
						'SELLER_KPP' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_KPP|'.$idRU
						),
						'SELLER_RS' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idRU
						),
						'SELLER_BANK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idRU
						),
						'SELLER_BCITY' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ADDR|'.$idRU
						),
						'SELLER_KS' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_COR_ACC_NUM|'.$idRU
						),
						'SELLER_BIK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BIK|'.$idRU
						),
						'SELLER_DIR' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_DIRECTOR|'.$idRU
						),
						'SELLER_ACC' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ACCOUNTANT|'.$idRU
						)
					),
					$idBY => array(
						'SELLER_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idBY
						),
						'SELLER_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_INN|'.$idBY
						),
						'SELLER_RS' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idBY
						),
						'SELLER_BANK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idBY
						),
						'SELLER_BCITY' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ADDR|'.$idBY
						),
						'SELLER_BIK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BIK|'.$idBY
						),
						'SELLER_DIR' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_DIRECTOR|'.$idBY
						),
						'SELLER_ACC' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ACCOUNTANT|'.$idBY
						)
					),
					$idKZ => array(
						'SELLER_NAME' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_COMPANY_FULL_NAME|'.$idKZ
						),
						'SELLER_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idKZ
						),
						'SELLER_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_INN|'.$idKZ
						),
						'SELLER_RS' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_IIK|'.$idKZ
						),
						'SELLER_BANK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idKZ
						),
						'SELLER_BCITY' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ADDR|'.$idKZ
						),
						'SELLER_KS' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_COR_ACC_NUM|'.$idKZ
						),
						'SELLER_BIK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BIK|'.$idKZ
						),
						'BUYER_NAME' => array(
							'TYPE' => 'REQUISITE',
							'VALUE' => 'RQ_COMPANY_FULL_NAME|'.$idKZ
						)
					),
					$idDE => array(
						'SELLER_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idDE
						),
						'SELLER_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_EMAIL' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'EMAIL_WORK'
						),
						'SELLER_BANK_ACCNO' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idDE
						),
						'SELLER_BANK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idDE
						),
						'SELLER_BANK_BLZ' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ROUTE_NUM|'.$idDE
						),
						'SELLER_BANK_IBAN' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_IBAN|'.$idDE
						),
						'SELLER_BANK_SWIFT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_SWIFT|'.$idDE
						),
						'SELLER_EU_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_VAT_ID|'.$idDE
						),
						'SELLER_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_INN|'.$idDE
						),
						'SELLER_REG' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_USRLE|'.$idDE
						)
					),
					$idUS => array(
						'SELLER_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idUS
						),
						'SELLER_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_EMAIL' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'EMAIL_WORK'
						),
						'SELLER_BANK_ACCNO' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idUS
						),
						'SELLER_BANK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idUS
						),
						'SELLER_BANK_BLZ' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_ROUTE_NUM|'.$idUS
						),
						'SELLER_BANK_IBAN' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_IBAN|'.$idUS
						),
						'SELLER_BANK_SWIFT' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_SWIFT|'.$idUS
						),
						'SELLER_EU_INN' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_VAT_ID|'.$idUS
						)
					),
					$idUA => array(
						'SELLER_NAME' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'TITLE'
						),
						'SELLER_ADDRESS' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ADDR_'.RequisiteAddress::Registered.'|'.$idUA
						),
						'SELLER_PHONE' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'PHONE_WORK'
						),
						'SELLER_EMAIL' => array(
							'TYPE' => 'CRM_MYCOMPANY',
							'VALUE' => 'EMAIL_WORK'
						),
						'SELLER_EDRPOU' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_EDRPOU|'.$idUA
						),
						'SELLER_RS' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_ACC_NUM|'.$idUA
						),
						'SELLER_BANK' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_BANK_NAME|'.$idUA
						),
						'SELLER_MFO' => array(
							'TYPE' => 'MC_BANK_DETAIL',
							'VALUE' => 'RQ_MFO|'.$idUA
						),
						'SELLER_DIR' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_DIRECTOR|'.$idUA
						),
						'SELLER_ACC' => array(
							'TYPE' => 'MC_REQUISITE',
							'VALUE' => 'RQ_ACCOUNTANT|'.$idUA
						)
					)
				)
			);
		}
	}

	private static function LocalGetPSActionDescr($fileName)
	{
		$data = array();
		$psTitle = "";
		$psDescription = "";

		if (file_exists($fileName) && is_file($fileName))
			include($fileName);

		if ($data)
			return array($data['NAME'], $psDescription);

		return array($psTitle, $psDescription);
	}

	/**
	 * @return array
	 */
	public static function getActions()
	{
		if (!CModule::IncludeModule('sale'))
			return array();

		if (!empty(self::$arActFiles))
			return self::$arActFiles;

		$io = CBXVirtualIo::GetInstance();

		$handlerList = \Bitrix\Sale\PaySystem\Manager::getHandlerList();
		$handlerMap = CSalePaySystemAction::getOldToNewHandlersMap();

		foreach ($handlerList['USER'] as $handler => $title)
		{
			self::$arActFiles[$io->ExtractNameFromPath($handler)] = array(
				"ID" => $io->ExtractNameFromPath($handler),
				"PATH" => \Bitrix\Sale\PaySystem\Manager::getPathToHandlerFolder($handler),
				"HANDLER" => $handler,
				"TITLE" => $title,
				"NEW_FORMAT" => 'Y'
			);
		}

		foreach ($handlerList['SYSTEM'] as $handler => $title)
		{
			if (!in_array($handler, self::$arCrmCompatibleActs))
				continue;

			$path = array_search($handler, $handlerMap);
			if ($path === false)
				$path = \Bitrix\Sale\PaySystem\Manager::getPathToHandlerFolder($handler);

			self::$arActFiles[$handler] = array(
				"ID" => $handler,
				"PATH" => $path,
				"HANDLER" => $handler,
				"TITLE" => $title,
				"NEW_FORMAT" => 'Y'
			);
		}

		sortByColumn(self::$arActFiles, array("ID" => SORT_ASC));

		return self::$arActFiles;
	}

	public static function getActionsList()
	{
		$arReturn = array();
		$arAFF = self::getActions();

		foreach ($arAFF as $id => $arAction)
			$arReturn[$id] = $arAction['TITLE'];

		return $arReturn;
	}

	public static function getActionPath($actionId)
	{
		$arActions = self::getActions();

		if (isset($arActions[$actionId]['PATH']))
			return $arActions[$actionId]['PATH'];

		return false;
	}

	public static function getActionHandler($actionId)
	{
		$arActions = self::getActions();

		if (isset($arActions[$actionId]['HANDLER']))
			return $arActions[$actionId]['HANDLER'];

		return false;
	}

	public static function getActionSelector($idCorr, $arCorr)
	{
		if ($arCorr['TYPE'] == 'SELECT' || $arCorr['TYPE'] == 'FILE' || $arCorr['TYPE'] == 'CHECKBOX')
		{
			$res  = '<select name="TYPE_'.$idCorr.'" id="TYPE_'.$idCorr.'" style="display: none;">';
			$res .= '<option selected value="'.$arCorr["TYPE"].'"></option>';
			$res .= '</select>';
		}
		else if($arCorr['TYPE'] !== 'USER_COLUMN_LIST')
		{
			$bSimple = self::isFormSimple();

			$res = '<select name="TYPE_'.$idCorr.'" id="TYPE_'.$idCorr.'" style="'.($bSimple ? ' display: none;' : '').'">\n';
			$res .= '<option value=""'.($arCorr['TYPE'] == '' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_OTHER").'</option>\n';
			//$res .= '<option value="USER"'.($arCorr['TYPE'] == 'USER' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_USER").'</option>\n';
			$res .= '<option value="ORDER"'.($arCorr['TYPE'] == 'ORDER' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_ORDER").'</option>\n';
			$res .= '<option value="PAYMENT"'.($arCorr['TYPE'] == 'PAYMENT' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_PAYMENT").'</option>\n';
			$res .= '<option value="PROPERTY"'.($arCorr['TYPE'] == 'PROPERTY' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_PROPERTY").'</option>\n';
			$res .= '<option value="REQUISITE"'.($arCorr['TYPE'] == 'REQUISITE' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_REQUISITE").'</option>\n';
			$res .= '<option value="BANK_DETAIL"'.($arCorr['TYPE'] == 'BANK_DETAIL' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_BANK_DETAIL").'</option>\n';
			$res .= '<option value="CRM_COMPANY"'.($arCorr['TYPE'] == 'CRM_COMPANY' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_CRM_COMPANY").'</option>\n';
			$res .= '<option value="CRM_CONTACT"'.($arCorr['TYPE'] == 'CRM_CONTACT' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_CRM_CONTACT").'</option>\n';
			$res .= '<option value="MC_REQUISITE"'.($arCorr['TYPE'] == 'MC_REQUISITE' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_MC_REQUISITE").'</option>\n';
			$res .= '<option value="MC_BANK_DETAIL"'.($arCorr['TYPE'] == 'MC_BANK_DETAIL' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_MC_BANK_DETAIL").'</option>\n';
			$res .= '<option value="CRM_MYCOMPANY"'.($arCorr['TYPE'] == 'CRM_MYCOMPANY' ? ' selected' : '').'>'.GetMessage("CRM_PS_TYPES_CRM_MYCOMPANY").'</option>\n';
			$res .= '</select>';
		}

		return $res;
	}

	public static function getOrderPropsList($persTypeId = false)
	{
		static $arProps = array();

		if(empty($arProps) && CModule::IncludeModule('sale'))
		{
			$arPersTypeIds = self::getPersonTypeIDs();

			$dbRes = \Bitrix\Crm\Invoice\Property::getList([
				'select' => [
					'ID', 'CODE', 'NAME', 'TYPE', 'SORT', 'PERSON_TYPE_ID'
				],
				'filter' => [
					'PERSON_TYPE_ID' => $arPersTypeIds
				],
				'order' => [
					'SORT' => 'ASC',
					'NAME' => 'ASC'
				]
			]);

			while ($arOrderProps = $dbRes->fetch())
			{
				$title = htmlspecialcharsbx($arOrderProps["NAME"]);
				$code = htmlspecialcharsbx($arOrderProps["CODE"]);

				$idx = $arOrderProps["CODE"] <> '' ? $code : $arOrderProps["ID"];
				$arProps[$arOrderProps["PERSON_TYPE_ID"]][$idx] = $title;

				if ($arOrderProps["TYPE"] == "LOCATION")
				{
					$idx = $code <> '' ? $code."_COUNTRY" : $arOrderProps["ID"]."_COUNTRY";
					$arProps[$arOrderProps["PERSON_TYPE_ID"]][$idx] = $title." (".GetMessage("CRM_PS_JCOUNTRY").")";

					$idx = $code <> '' ? $code."_CITY" : $arOrderProps["ID"]."_CITY";
					$arProps[$arOrderProps["PERSON_TYPE_ID"]][$idx] = $title." (".GetMessage("CRM_PS_JCITY").")";
				}
			}
		}

		if($persTypeId && isset($arProps[$persTypeId]))
			$arReturn = $arProps[$persTypeId];
		elseif($persTypeId && !isset($arProps[$persTypeId]))
			$arReturn = false;
		else
			$arReturn = $arProps;

		return $arReturn;
	}

	public static function getOrderFieldsList()
	{
		return $arProps = array(
					"ID" => GetMessage("CRM_PS_ORDER_ID"),
					"ACCOUNT_NUMBER" => GetMessage("CRM_PS_ORDER_ACCOUNT_NUMBER"),
					"ORDER_TOPIC" => GetMessage("CRM_FIELD_ORDER_TOPIC"),
					"DATE_INSERT" => GetMessage("CRM_PS_ORDER_DATETIME"),
					"DATE_INSERT_DATE" => GetMessage("CRM_PS_ORDER_DATE"),
					"DATE_BILL" => GetMessage("CRM_PS_ORDER_DATE_BILL"),
					"DATE_BILL_DATE" => GetMessage("CRM_PS_ORDER_DATE_BILL_DATE"),
					"DATE_PAY_BEFORE" => GetMessage("CRM_PS_ORDER_DATE_PAY_BEFORE"),
					"SHOULD_PAY" => GetMessage("CRM_PS_ORDER_PRICE"),
					"CURRENCY" => GetMessage("CRM_PS_ORDER_CURRENCY"),
					"PRICE" => GetMessage("CRM_PS_ORDER_SUM"),
					//"LID" => GetMessage("CRM_PS_ORDER_SITE"),
					"PRICE_DELIVERY" => GetMessage("CRM_PS_ORDER_PRICE_DELIV"),
					"DISCOUNT_VALUE" => GetMessage("CRM_PS_ORDER_DESCOUNT"),
					"USER_ID" => GetMessage("CRM_PS_ORDER_USER_ID"),
					"PAY_SYSTEM_ID" => GetMessage("CRM_PS_ORDER_PS"),
					"DELIVERY_ID" => GetMessage("CRM_PS_ORDER_DELIV"),
					"TAX_VALUE" => GetMessage("CRM_PS_ORDER_TAX"),
					"USER_DESCRIPTION" => GetMessage("CRM_PS_ORDER_USER_DESCRIPTION")
				);
	}

	public static function getPaymentFieldsList()
	{
		return array(
			"ID" => GetMessage("CRM_PS_PAYMENT_ID"),
			"ACCOUNT_NUMBER" => GetMessage("CRM_PS_PAYMENT_ACCOUNT_NUMBER"),
			"DATE_BILL" => GetMessage("CRM_PS_PAYMENT_DATE_BILL"),
			"DATE_BILL_DATE" => GetMessage("CRM_PS_PAYMENT_DATE"),
			"SUM" => GetMessage("CRM_PS_PAYMENT_PRICE"),
			"CURRENCY" => GetMessage("CRM_PS_PAYMENT_CURRENCY"),
		);
	}

	public static function getUserPropsList()
	{
		return $arProps = array(
					"ID" => GetMessage("CRM_PS_USER_ID"),
					"LOGIN" => GetMessage("CRM_PS_USER_LOGIN"),
					"NAME" => GetMessage("CRM_PS_USER_NAME"),
					"SECOND_NAME" => GetMessage("CRM_PS_USER_SECOND_NAME"),
					"LAST_NAME" => GetMessage("CRM_PS_USER_LAST_NAME"),
					"EMAIL" => "EMail",
					//"LID" => GetMessage("CRM_PS_USER_SITE"),
					"PERSONAL_PROFESSION" => GetMessage("CRM_PS_USER_PROF"),
					"PERSONAL_WWW" => GetMessage("CRM_PS_USER_WEB"),
					"PERSONAL_ICQ" => GetMessage("CRM_PS_USER_ICQ"),
					"PERSONAL_GENDER" => GetMessage("CRM_PS_USER_SEX"),
					"PERSONAL_FAX" => GetMessage("CRM_PS_USER_FAX"),
					"PERSONAL_MOBILE" => GetMessage("CRM_PS_USER_PHONE"),
					"PERSONAL_STREET" => GetMessage("CRM_PS_USER_ADDRESS"),
					"PERSONAL_MAILBOX" => GetMessage("CRM_PS_USER_POST"),
					"PERSONAL_CITY" => GetMessage("CRM_PS_USER_CITY"),
					"PERSONAL_STATE" => GetMessage("CRM_PS_USER_STATE"),
					"PERSONAL_ZIP" => GetMessage("CRM_PS_USER_ZIP"),
					"PERSONAL_COUNTRY" => GetMessage("CRM_PS_USER_COUNTRY"),
					"WORK_COMPANY" => GetMessage("CRM_PS_USER_COMPANY"),
					"WORK_DEPARTMENT" => GetMessage("CRM_PS_USER_DEPT"),
					"WORK_POSITION" => GetMessage("CRM_PS_USER_DOL"),
					"WORK_WWW" => GetMessage("CRM_PS_USER_COM_WEB"),
					"WORK_PHONE" => GetMessage("CRM_PS_USER_COM_PHONE"),
					"WORK_FAX" => GetMessage("CRM_PS_USER_COM_FAX"),
					"WORK_STREET" => GetMessage("CRM_PS_USER_COM_ADDRESS"),
					"WORK_MAILBOX" => GetMessage("CRM_PS_USER_COM_POST"),
					"WORK_CITY" => GetMessage("CRM_PS_USER_COM_CITY"),
					"WORK_STATE" => GetMessage("CRM_PS_USER_COM_STATE"),
					"WORK_ZIP" => GetMessage("CRM_PS_USER_COM_ZIP"),
					"WORK_COUNTRY" => GetMessage("CRM_PS_USER_COM_COUNTRY")
		);
	}

	public static function getSelectPropsList($values)
	{
		$arProps = array();

		foreach ($values as $k => $value)
		{
			$arProps[$k] = $value;
		}

		return $arProps;
	}

	public static function getActionValueSelector(
		$idCorr, $arCorr, $persTypeId, $actionFileName = '', $userFields = null,
		$requisiteFields = null, $bankDetailFields = null, $companyFields = null, $contactFields = null,
		$mcRequisiteFields = null, $mcBankDetailFields = null, $myCompanyFields = null
	)
	{
		if ($arCorr['TYPE'] == 'FILE')
		{
			$res = '<input type="file" name="VALUE1_'.$idCorr.'" id="VALUE1_'.$idCorr.'" size="40">';

			if ($arCorr['VALUE'])
			{
				$res .= '<span><br>' . $arCorr['VALUE'];
				$res .= '<br><input type="checkbox" name="' . $idCorr . '_del" value="Y" id="' . $idCorr . '_del" >';
				$res .= '<label for="' . $idCorr . '_del">' . GetMessage("CRM_PS_DEL_FILE") . '</label></span>';
			}
		}
		elseif ($arCorr['TYPE'] == 'CHECKBOX')
		{
			$res = '<input type="checkbox" name="VALUE1_'.$idCorr.'" id="VALUE1_'.$idCorr.'" ';
			if ($arCorr['VALUE'] === 'Y')
				$res .= 'checked';
			$res .= ' size="40" value="Y">';
		}
		else
		{
			$res = '<select name="VALUE1_'.$idCorr.'" id="VALUE1_'.$idCorr.'"'.($arCorr['TYPE'] == '' ? ' style="display: none;"' : '').'>';

			$arProps = array();

			if($arCorr['TYPE'] == 'USER')
			{
				$arProps = self::getUserPropsList();
			}
			if($arCorr['TYPE'] == 'ORDER')
			{
				$arProps = self::getOrderFieldsList();
			}
			if($arCorr['TYPE'] == 'PAYMENT')
			{
				$arProps = self::getPaymentFieldsList();
			}
			elseif($arCorr['TYPE'] == 'PROPERTY')
			{
				$arProps = self::getOrderPropsList($persTypeId);

				$entity = mb_strpos($actionFileName, 'quote_') !== false ? 'quote' : 'bill';
				if( is_array($userFields) && isset($userFields[$entity]))
					$arProps = array_merge($arProps, $userFields[$entity]);
			}
			elseif($arCorr['TYPE'] === 'REQUISITE' || $arCorr['TYPE'] === 'BANK_DETAIL'
				|| $arCorr['TYPE'] === 'MC_REQUISITE' || $arCorr['TYPE'] === 'MC_BANK_DETAIL')
			{
				$items = array();
				if ($arCorr['TYPE'] == 'REQUISITE' || $arCorr['TYPE'] == 'MC_REQUISITE')
					$items = $requisiteFields;
				else if ($arCorr['TYPE'] == 'BANK_DETAIL' || $arCorr['TYPE'] == 'MC_BANK_DETAIL')
					$items = $bankDetailFields;

				if(!empty($items))
				{
					$groupStart = false;
					foreach ($items as $itemInfo)
					{
						if (isset($itemInfo['type']) && $itemInfo['type'] === 'group')
						{
							if ($groupStart)
								$res .= '</optgroup>'.PHP_EOL;
							$res .= '<optgroup label="'.htmlspecialcharsbx($itemInfo['title']).'">'.PHP_EOL;
							$groupStart = true;
						}
						else
						{
							$id = htmlspecialcharsbx($itemInfo['id']);
							$title = htmlspecialcharsbx($itemInfo['title']);
							$res .= '<option value="'.$id.'"'.($arCorr['VALUE'] == $itemInfo['id'] ? ' selected' : '').'>'.$title.'</option>'.PHP_EOL;
						}
					}
					if ($groupStart)
						$res .= '</optgroup>'.PHP_EOL;
					unset($groupStart, $id, $title);
				}
			}
			elseif($arCorr['TYPE'] == 'CRM_COMPANY' || $arCorr['TYPE'] == 'CRM_MYCOMPANY')
			{
				if(is_array($companyFields))
					$arProps = $companyFields;
			}
			elseif($arCorr['TYPE'] == 'CRM_CONTACT')
			{
				if(is_array($contactFields))
					$arProps = $contactFields;
			}
			elseif ($arCorr['TYPE'] == 'SELECT')
			{
				$arProps = self::getSelectPropsList($arCorr['OPTIONS']);
			}

			if(!empty($arProps))
				foreach ($arProps as $id => $propName)
					$res .= '<option value="'.$id.'"'.($arCorr['VALUE'] == $id ? ' selected' : '').'>'.$propName.'</option>\n';

			if ($arCorr['TYPE'] != 'SELECT')
			{
				if ($arCorr['TYPE'] != '')
					$arCorr['VALUE'] = '';

				$res .= '<input type="text" value="'.htmlspecialcharsbx($arCorr['VALUE']);
				$res .= '" name="VALUE2_'.$idCorr;
				$res .= '" id="VALUE2_'.$idCorr;
				$res .= '" size="40"'.($arCorr['TYPE'] == '' ? '' : ' style="display: none;"').'>';
			}

			$res .= '</select>';
		}

		return $res;
	}

	public static function getDefaultSiteId(): string
	{
		return \Bitrix\Crm\Integration\Main\Site::getPortalSiteId();
	}

	public static function getPersonTypeIDs(?string $siteId = null)
	{
		if (!CModule::IncludeModule('sale'))
		{
			return array();
		}

		static $arPTIDs = array();

		if ($siteId === null)
		{
			$siteId = static::getDefaultSiteId();
		}

		if (!empty($arPTIDs[$siteId]))
		{
			return $arPTIDs[$siteId];
		}

		$dbRes = \Bitrix\Crm\Invoice\PersonType::getList([
			'select' => ['ID', 'CODE'],
			'filter' => [
				"=PERSON_TYPE_SITE.SITE_ID" => $siteId,
				'@CODE' => ['CRM_COMPANY', 'CRM_CONTACT']
			],
			'order' => [
				'SORT' => "ASC",
				'NAME' => 'ASC'
			],
			'cache' => [
				'ttl' => 864000,
				'cache_joins' => true
			]
		]);

		while ($arPT = $dbRes->fetch())
		{
			if ($arPT['CODE'] === 'CRM_COMPANY')
			{
				$arPTIDs[$siteId]['COMPANY'] = $arPT['ID'];
			}

			if ($arPT['CODE'] === 'CRM_CONTACT')
			{
				$arPTIDs[$siteId]['CONTACT'] = $arPT['ID'];
			}
		}

		return $arPTIDs[$siteId] ?? [];
	}

	public static function getPersonTypesList($getEmpty = false)
	{
		$arPtIDs = self::getPersonTypeIDs();

		if(empty($arPtIDs) || !CModule::IncludeModule('sale'))
			return array();

		$arReturn = array();

		if($getEmpty)
			$arReturn[""] = GetMessage('CRM_ANY');

		$dbRes = \Bitrix\Crm\Invoice\PersonType::getList([
			'select' => ['ID', 'CODE'],
			'filter' => [
				'@ID' => [$arPtIDs['COMPANY'], $arPtIDs['CONTACT']]
			],
			'order' => [
				'SORT' => "ASC",
				'NAME' => 'ASC'
			]
		]);

		while($arPT = $dbRes->fetch())
			$arReturn[$arPT['ID']] = GetMessage($arPT['CODE']."_PT");

		return $arReturn;
	}

	public static function resolveOwnerTypeID($personTypeID)
	{
		$personTypeID = intval($personTypeID);
		$personTypeIDs = self::getPersonTypeIDs();
		if(isset($personTypeIDs['COMPANY']) && intval($personTypeIDs['COMPANY']) === $personTypeID)
		{
			return CCrmOwnerType::Company;
		}
		if(isset($personTypeIDs['CONTACT']) && intval($personTypeIDs['CONTACT']) === $personTypeID)
		{
			return CCrmOwnerType::Contact;
		}
		return CCrmOwnerType::Undefined;
	}

	/**
	 * @param string $actFile
	 * @return array
	 */
	public static function getPSCorrespondence($actFile)
	{
		if (!$actFile || !CModule::IncludeModule('sale'))
			return array();

		$map = CSalePaySystemAction::getOldToNewHandlersMap();
		if (!in_array($actFile, $map))
		{
			$path = self::getActionPath($actFile);
			if ($path !== false)
				$actFile = $path;
		}

		$psMode = func_num_args() > 1 ? func_get_arg(1) : null;
		$data = \Bitrix\Sale\PaySystem\Manager::getHandlerDescription($actFile, $psMode);

		return self::convertNewToOld($data);
	}

	public static function rewritePSCorrByRqSource($personTypeId, &$params, $options = array())
	{
		$personTypeId = (int)$personTypeId;
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();

		$countryId = \Bitrix\Crm\EntityPreset::getCurrentCountryId();
		$psaCode = '';
		$psType = '';
		if (is_array($options) && isset($options['PSA_CODE']))
			$psaCode = $options['PSA_CODE'];
		if (!empty($psaCode))
		{
			$matches = array();
			$curPsLocalization = '';
			if (preg_match('/^(bill)(\w+)*$/iu', $psaCode, $matches))
			{
				$psType = $matches[1];
				if (count($matches) === 2)
				{
					$curPsLocalization = 'ru';
				}
				else if (count($matches) === 3 && mb_strlen($matches[2]) > 1)
				{
					$curPsLocalization = mb_substr($matches[2], 0, 2);
				}
			}
			else if (preg_match('/^(quote)(_\w+)*$/iu', $psaCode, $matches))
			{
				$psType = $matches[1];
				if (count($matches) === 3 && mb_strlen($matches[2]) > 2)
				{
					$curPsLocalization = mb_substr($matches[2], 1, 2);
				}
			}
			if (!empty($curPsLocalization)
				&& in_array($curPsLocalization, array('ru', 'de', 'en', 'la', 'ua', 'kz', 'by', 'br', 'fr'), true))
			{
				if (Bitrix\Crm\EntityPreset::isUTFMode())
					$countryId = self::getPresetCountryIdByPS($psType, $curPsLocalization);
			}
		}

		$personTypeCode = '';
		if ($arPersonTypes['COMPANY'] != "" && $arPersonTypes['CONTACT'] != ""
			&& ($personTypeId == $arPersonTypes['CONTACT'] || $personTypeId == $arPersonTypes['COMPANY'])
		)
		{
			if ($personTypeId == $arPersonTypes['CONTACT'])
				$personTypeCode = 'CONTACT';
			else if ($personTypeId == $arPersonTypes['COMPANY'])
				$personTypeCode = 'COMPANY';

			$requisiteConverted = false;
			if (!empty($personTypeCode))
			{
				$requisiteConverted =
					(COption::GetOptionString('crm', '~CRM_TRANSFER_REQUISITES_TO_'.$personTypeCode, 'N') !== 'Y');
			}

			if ($requisiteConverted)
			{
				if ($countryId)
				{
					$convMap = array(
						'PROPERTY' => array(
							'COMPANY' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_COMPANY_NAME|'.$countryId
							),
							'COMPANY_NAME' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_COMPANY_NAME|'.$countryId
							),
							'INN' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_INN|'.$countryId
							),
							'COMPANY_ADR' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_ADDR_'.EntityAddressType::Registered.'|'.$countryId
							),
							'PHONE' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_PHONE|'.$countryId
							),
							'FAX' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_FAX|'.$countryId
							),
							'CONTACT_PERSON' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_CONTACT|'.$countryId
							),
							'FIO' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_NAME|'.$countryId
							),
							'ADDRESS' => array(
								'TYPE' => 'REQUISITE',
								'VALUE' => 'RQ_ADDR_'.EntityAddressType::Primary.'|'.$countryId
							)
						)
					);
					if (is_array($params) && !empty($params))
					{
						foreach ($params as &$param)
						{
							if (isset($param['TYPE']) && $param['TYPE'] === 'PROPERTY'
								&& isset($param['VALUE']))
							{
								foreach ($convMap as $type => $typeMap)
								{
									if ($param['TYPE'] === $type)
									{
										foreach ($typeMap as $value => $newParam)
										{
											if ($param['VALUE'] === $value)
											{
												$param['TYPE'] = $newParam['TYPE'];
												$param['VALUE'] = $newParam['VALUE'];
											}
										}
									}
								}
							}
						}
						unset($param);
					}
				}
			}
		}

		if (is_array($params))
		{
			$psTypes = array('bill', 'quote');

			if (!empty($psType))
				$psTypes = array($psType);
			foreach ($psTypes as $type)
			{
				if (is_string($personTypeCode) && $personTypeCode <> '')
				{
					foreach (CCrmPaySystem::getDefaultBuyerParams('CRM_'.$personTypeCode, $type, $countryId)
								as $paramName => $paramValue)
					{
						if (is_array($params[$paramName]))
						{
							$params[$paramName]['TYPE'] = $paramValue['TYPE'];
							$params[$paramName]['VALUE'] = $paramValue['VALUE'];
						}
					}
				}
				foreach (CCrmPaySystem::getDefaultMyCompanyParams($type, $countryId) as $paramName => $paramValue)
				{
					if (is_array($params[$paramName]))
					{
						$params[$paramName]['TYPE'] = $paramValue['TYPE'];
						$params[$paramName]['VALUE'] = $paramValue['VALUE'];
					}
				}
			}
		}
	}

	public static function isFormSimple()
	{
		return CUserOptions::GetOption("crm", "simplePSForm", "Y") == "Y";
	}

	public static function setFormSimple($bSimple = true)
	{
		return CUserOptions::SetOption("crm", "simplePSForm", ($bSimple ? "Y" : "N"));
	}

	public static function unSetFormSimple()
	{
		self::setFormSimple(false);
	}

	public static function GetPaySystems($personTypeId)
	{
		if(!CModule::IncludeModule('sale'))
		{
			return false;
		}

		if (self::$paySystems === null)
		{
			$arPersonTypes = self::getPersonTypeIDs();
			if (!isset($arPersonTypes['COMPANY']) || !isset($arPersonTypes['CONTACT']) ||
				$arPersonTypes['COMPANY'] <= 0 || $arPersonTypes['CONTACT'] <= 0)
				return false;

			$paySystems = array(
				$arPersonTypes['COMPANY'] => array(),
				$arPersonTypes['CONTACT'] => array(),
			);

			$crmPsIds = static::getCrmPaySystemIds();

			$paySystemList = CSalePaySystem::DoLoadPaySystems(array($arPersonTypes['COMPANY'], $arPersonTypes['CONTACT']));
			foreach ($paySystemList as $psId => $ps)
			{
				if (!isset($crmPsIds[$psId]))
				{
					continue;
				}

				if (isset($ps['~PSA_PERSON_TYPE_ID']))
				{
					if ($ps['~PSA_PERSON_TYPE_ID'] == $arPersonTypes['COMPANY'])
						$paySystems[$arPersonTypes['COMPANY']][$psId] = $ps;
					else if ($ps['~PSA_PERSON_TYPE_ID'] == $arPersonTypes['CONTACT'])
						$paySystems[$arPersonTypes['CONTACT']][$psId] = $ps;
				}
			}

			self::$paySystems = $paySystems;
		}

		return isset(self::$paySystems[$personTypeId]) ? self::$paySystems[$personTypeId] : false;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private static function getCrmPaySystemIds()
	{
		$result = [];

		$dbRes = \Bitrix\Sale\PaySystem\Manager::getList([
			'select' => ['ID'],
			'filter' => [
				'@ENTITY_REGISTRY_TYPE' => [REGISTRY_TYPE_CRM_QUOTE, REGISTRY_TYPE_CRM_INVOICE]
			]
		]);
		while ($item = $dbRes->fetch())
		{
			$result[$item['ID']] = $item['ID'];
		}

		return $result;
	}

	public static function GetPaySystemsListItems($personTypeId, $fullList = false)
	{
		$arItems = array();

		$arPaySystems = self::GetPaySystems($personTypeId);
		if (is_array($arPaySystems))
			foreach ($arPaySystems as $paySystem)
			{
				if (preg_match('/quote(_\w+)*$/iu', $paySystem['~PSA_ACTION_FILE']))
					continue;

				if ($fullList
					|| preg_match('/bill(\w+)*$/iu', $paySystem['~PSA_ACTION_FILE'])
					|| preg_match('/document(\w+)*$/iu', $paySystem['~PSA_ACTION_FILE'])
				)
				{
					$arItems[$paySystem['~ID']] = $paySystem['~NAME'];
				}
			}

		return $arItems;
	}

	/**
	* Checks if is filled company-name at least in one pay system
	*/
	public static function isNameFilled()
	{
		if (!CModule::IncludeModule('sale'))
			return false;

		$result = false;
		$arCrmPtIDs = CCrmPaySystem::getPersonTypeIDs();
		$dbPaySystems = CSalePaySystem::GetList(array(), array( "PERSON_TYPE_ID" => $arCrmPtIDs ));

		while($arPaySys = $dbPaySystems->Fetch())
		{
			$params = $arPaySys['PSA_PARAMS'];
			$params = unserialize($arPaySys['PSA_PARAMS'], ['allowed_classes' => false]);

			if(trim($params['SELLER_NAME']['VALUE']) <> '')
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	public static function isUserMustFillPSProps()
	{
		if(CUserOptions::GetOption('crm', 'crmInvoicePSPropsFillDialogViewedByUser', 'N') === 'Y')
			return false;

		$CrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());

		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			return false;

		if(self::isNameFilled())
			return false;

		return true;
	}

	public static function markPSFillPropsDialogAsViewed()
	{
		return CUserOptions::SetOption('crm', 'crmInvoicePSPropsFillDialogViewedByUser', 'Y');
	}

	public static function getRequisiteFieldSelectItems($fieldsUsedInSettings)
	{
		$result = array();

		$preset = new \Bitrix\Crm\EntityPreset();
		$requisite = new EntityRequisite();
		$allowedCountries = $requisite->getAllowedRqFieldCountries();

		$addressTypesByCountry = [];
		$addressTitleList = EntityAddressType::getDescriptions(EntityAddressType::getAllIDs());

		// rq fields
		$rqFields = array();
		$tmpFields = $requisite->getRqFields();
		foreach ($tmpFields as $fieldName)
		{
			if ($fieldName === EntityRequisite::ADDRESS)
			{
				foreach (EntityAddressType::getAllIDs() as $addressType)
				{
					$rqFields[$fieldName.'_'.$addressType] = true;
				}
			}
			else
			{
				$rqFields[$fieldName] = true;
			}
		}

		// rq fields by country
		$rqFieldsByCountry = array();
		foreach ($requisite->getRqFieldsCountryMap() as $fieldName => $fieldCountryIds)
		{
			if (is_array($fieldCountryIds))
			{
				foreach ($fieldCountryIds as $countryId)
				{
					if ($fieldName === EntityRequisite::ADDRESS)
					{
						if (!isset($addressTypesByCountry[$countryId]))
						{
							$addressTypesByCountry[$countryId] = EntityAddressType::getIdsByZonesOrValues(
								[
									EntityAddress::getZoneId(),
									EntityRequisite::getAddressZoneByCountry($countryId)
								]
							);
						}
						foreach ($addressTypesByCountry[$countryId] as $addressType)
						{
							$rqFieldsByCountry[$countryId][$fieldName.'_'.$addressType] = true;
						}
					}
					else
					{
						$rqFieldsByCountry[$countryId][$fieldName] = true;
					}
				}
			}
		}

		// allowed fields
		$fieldsAllowed = array_merge($rqFields, array_fill_keys($requisite->getUserFields(), true));

		// used fields
		$usedCountries = array();
		$usedFieldsByCountry = array();
		if (is_array($fieldsUsedInSettings))
		{
			foreach ($fieldsUsedInSettings as $index)
			{
				$parts = explode('|', $index, 2);
				if (is_array($parts) && count($parts) === 2)
				{
					$fieldName = $parts[0];
					$fieldCountryId = (int)$parts[1];
					if (!empty($fieldName) && in_array($fieldCountryId, $allowedCountries, true))
					{
						if (!is_array($usedFieldsByCountry[$fieldCountryId]))
							$usedFieldsByCountry[$fieldCountryId] = array();
						$usedFieldsByCountry[$fieldCountryId][$fieldName] = true;
						$usedCountries[$fieldCountryId] = true;
					}
				}
			}
		}

		$currentCountryId = \Bitrix\Crm\EntityPreset::getCurrentCountryId();

		// active fields
		$activeFieldsByCountry = array();
		$tmpFields = $preset->getSettingsFieldsOfPresets(
			\Bitrix\Crm\EntityPreset::Requisite,
			'active',
			array(
				'ARRANGE_BY_COUNTRY' => true,
				'FILTER_BY_COUNTRY_IDS' => $allowedCountries
			)
		);
		foreach ($tmpFields as $countryId => $fieldList)
		{
			foreach ($fieldList as $fieldName)
			{
				if ($fieldName === EntityRequisite::ADDRESS)
				{
					if (!isset($addressTypesByCountry[$countryId]))
					{
						$addressTypesByCountry[$countryId] = EntityAddressType::getIdsByZonesOrValues(
							[
								EntityAddress::getZoneId(),
								EntityRequisite::getAddressZoneByCountry($countryId)
							]
						);
					}
					foreach ($addressTypesByCountry[$countryId] as $addressType)
					{
						$activeFieldsByCountry[$countryId][$fieldName.'_'.$addressType] = true;
					}
				}
				else
				{
					$activeFieldsByCountry[$countryId][$fieldName] = true;
				}
				$usedCountries[$countryId] = true;
			}
		}

		// rq fields for backward compatibility
		$rqbcFields = array(
			'RQ_COMPANY_NAME' => true,
			'RQ_INN' => true,
			'RQ_KPP' => true,
			'RQ_ADDR_'.EntityAddressType::Primary => true,
			'RQ_ADDR_'.EntityAddressType::Registered => true,
			'RQ_EMAIL' => true,
			'RQ_PHONE' => true,
			'RQ_FAX' => true,
			'RQ_CONTACT' => true,
			'RQ_NAME' => true
		);

		$fieldsTitleMap = $requisite->getRqFieldTitleMap();
		$userFieldTitles = $requisite->getUserFieldsTitles();

		$countrySort = array();
		if (isset($usedCountries[$currentCountryId]))
			$countrySort[] = $currentCountryId;
		foreach ($allowedCountries as $countryId)
		{
			if ($countryId !== $currentCountryId && isset($usedCountries[$countryId]))
				$countrySort[] = $countryId;
		}

		$countryTitleList = array();
		foreach (\Bitrix\Crm\EntityPreset::getCountryList() as $k => $v)
			$countryTitleList[$k] = $v;

		$result[] = array('id' => '', 'title' => GetMessage('CRM_PS_SELECT_NONE'));
		$addressPrefix = EntityRequisite::ADDRESS;
		$isUTFMode = \Bitrix\Crm\EntityPreset::isUTFMode();
		foreach ($countrySort as $countryId)
		{
			$groupExists = false;
			$groupItem = array('type' => 'group', 'title' => $countryTitleList[$countryId]);
			$isCountryToShow = ($isUTFMode || $countryId === $currentCountryId);
			foreach (array_keys($fieldsAllowed) as $fieldName)
			{
				if ((isset($activeFieldsByCountry[$countryId][$fieldName])
						&& $isCountryToShow)
					|| isset($usedFieldsByCountry[$countryId][$fieldName])
					|| (isset($rqbcFields[$fieldName])
						&& isset($rqFieldsByCountry[$countryId][$fieldName])
						&& $isCountryToShow))
				{
					$matches = array();
					if (preg_match('/'.$addressPrefix.'_(\d+)/u', $fieldName, $matches))
					{
						$addressType = (int)$matches[1];
						if (isset($addressTitleList[$addressType]))
						{
							if (!$groupExists)
							{
								$result[] = $groupItem;
								$groupExists = true;
							}
							$result[] = array(
								'id' => $fieldName.'|'.$countryId,
								'title' => $addressTitleList[$addressType]
							);
						}
					}
					else
					{
						$title = isset($fieldsTitleMap[$fieldName][$countryId])
							? $fieldsTitleMap[$fieldName][$countryId]
							: (isset($userFieldTitles[$fieldName]) ? $userFieldTitles[$fieldName] : '');
						if (empty($title))
							$title = $fieldName;
						if (!$groupExists)
						{
							$result[] = $groupItem;
							$groupExists = true;
						}
						$result[] = array('id' => $fieldName.'|'.$countryId, 'title' => $title);
					}
				}
			}
		}

		return $result;
	}

	public static function getBankDetailFieldSelectItems()
	{
		$result = array();

		$preset = new \Bitrix\Crm\EntityPreset();
		$bankDetail = new \Bitrix\Crm\EntityBankDetail();

		$currentCountryId = \Bitrix\Crm\EntityPreset::getCurrentCountryId();

		$allowedCountries = $bankDetail->getAllowedRqFieldCountries();
		$activeCountries = array();
		$res = $preset->getList(array(
			'order' => array('SORT' => 'ASC'),
			'filter' => array(
				'=ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite,
				'=COUNTRY_ID' => $allowedCountries,
				'=ACTIVE' => 'Y'
			),
			'select' => array('ID', 'COUNTRY_ID')
		));
		while ($presetData = $res->fetch())
		{
			$countryId = (int)$presetData['COUNTRY_ID'];
			if ($countryId > 0)
				$activeCountries[$countryId] = true;
		}

		$fieldsTitleMap = $bankDetail->getRqFieldTitleMap();

		$countrySort = array();
		if (isset($activeCountries[$currentCountryId]))
			$countrySort[] = $currentCountryId;
		foreach (array_keys($activeCountries) as $countryId)
		{
			if ($countryId !== $currentCountryId)
				$countrySort[] = $countryId;
		}

		$countryList = array();
		foreach (\Bitrix\Crm\EntityPreset::getCountryList() as $k => $v)
			$countryList[$k] = $v;

		$fieldsByCountry = $bankDetail->getRqFieldByCountry();

		$isUTFMode = \Bitrix\Crm\EntityPreset::isUTFMode();
		$result[] = array('id' => '', 'title' => GetMessage('CRM_PS_SELECT_NONE'));
		foreach ($countrySort as $countryId)
		{
			if (!($isUTFMode || $countryId === $currentCountryId))
				continue;

			$result[] = array('type' => 'group', 'title' => $countryList[$countryId]);
			foreach ($fieldsByCountry[$countryId] as $fieldName)
			{
				$title = isset($fieldsTitleMap[$fieldName][$countryId]) ? $fieldsTitleMap[$fieldName][$countryId] : '';
				if (empty($title))
					$title = $fieldName;
				$result[] = array('id' => $fieldName.'|'.$countryId, 'title' => $title);
			}
		}

		return $result;
	}

	public static function getDefaultBuyerParamValue($personTypeName, $psType, $countryId, $paramName, $defaultValue = null)
	{
		self::initDefaultBuyerParamsMap();

		if (!(is_array($defaultValue)
			&& array_key_exists('TYPE', $defaultValue)
			&& array_key_exists('VALUE', $defaultValue)))
		{
			$defaultValue = array('TYPE' => '', 'VALUE' => '');
		}

		return is_array(self::$defBuyerParamsMap[$personTypeName][$psType][$countryId][$paramName]) ?
			self::$defBuyerParamsMap[$personTypeName][$psType][$countryId][$paramName] : $defaultValue;
	}

	public static function getDefaultBuyerParams($personTypeName, $psType, $countryId)
	{
		self::initDefaultBuyerParamsMap();

		return is_array(self::$defBuyerParamsMap[$personTypeName][$psType][$countryId]) ?
			self::$defBuyerParamsMap[$personTypeName][$psType][$countryId] : array();
	}

	public static function getDefaultMyCompanyParamValue($psType, $countryId, $paramName, $defaultValue = null)
	{
		self::initDefaultMyCompanyParamsMap();

		if (!(is_array($defaultValue)
			&& array_key_exists('TYPE', $defaultValue)
			&& array_key_exists('VALUE', $defaultValue)))
		{
			$defaultValue = array('TYPE' => '', 'VALUE' => '');
		}

		return is_array(self::$defMyCompanyParamsMap[$psType][$countryId][$paramName]) ?
			self::$defMyCompanyParamsMap[$psType][$countryId][$paramName] : $defaultValue;
	}

	public static function getDefaultMyCompanyParams($psType, $countryId)
	{
		self::initDefaultMyCompanyParamsMap();

		return is_array(self::$defMyCompanyParamsMap[$psType][$countryId]) ?
			self::$defMyCompanyParamsMap[$psType][$countryId] : array();
	}

	public static function getPresetCountryIdByPS($psType, $psLocalization)
	{
		$curPresetCountryId = Bitrix\Crm\EntityPreset::getCurrentCountryId();
		$idRU = GetCountryIdByCode('RU');
		$idBY = GetCountryIdByCode('BY');
		$idKZ = GetCountryIdByCode('KZ');
		$idUA = GetCountryIdByCode('UA');
		$idDE = GetCountryIdByCode('DE');
		$idUS = GetCountryIdByCode('US');
		$presetCountryId = $idUS;
		switch ($psLocalization)
		{
			case 'ru':
				switch ($curPresetCountryId)
				{
					case $idBY:
						$presetCountryId = $idBY;
						break;
					case $idKZ:
						$presetCountryId = $idKZ;
						break;
					case $idUA:
						if ($psType === 'quote')
						{
							$presetCountryId = $idUA;
						}
						else
						{
							$presetCountryId = $idRU;
						}
						break;
					default:
						$presetCountryId = $idRU;
				}
				break;
			case 'by':
				$presetCountryId = $idBY;
				break;
			case 'kz':
				$presetCountryId = $idKZ;
				break;
			case 'ua':
				$presetCountryId = $idUA;
				break;
			case 'de':
				$presetCountryId = $idDE;
				break;
			case 'en':
			case 'la':
			case 'br':
			case 'fr':
				$presetCountryId = $idUS;
				break;
		}

		return $presetCountryId;
	}

	/**
	 * @param \Bitrix\Main\Event $event
	 * @return array
	 */
	public static function getHandlerDescriptionEx(\Bitrix\Main\Event $event)
	{
		$parameters = $event->getParameters();

		if (mb_strpos($parameters['handler'], 'bill') !== false || mb_strpos($parameters['handler'], 'quote_') !== false)
		{
			return array(
				'USER_COLUMNS' => array(
					"NAME" => GetMessage('CRM_PS_USER_FIELDS'),
					"SORT" => 6000,
					'GROUP' => 'COLUMN_SETTINGS',
					"INPUT" => array(
						"TYPE" => 'USER_COLUMN_LIST'
					)
				)
			);
		}

		return array();
	}
}
