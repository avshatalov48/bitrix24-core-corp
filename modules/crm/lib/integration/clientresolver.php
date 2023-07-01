<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Integration\Rest\AppPlacement;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Crm;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Requisite;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\Marketplace;
use Bitrix\Rest\PlacementTable;
use Bitrix\Socialservices;
use CRestUtil;

class ClientResolver
{
	const TYPE_UNKNOWN = 0;
	const TYPE_COMPANY = 1;
	const TYPE_PERSON = 2;
	const PROP_ITIN = 'ITIN';    // Individual Taxpayer Identification Number
	const PROP_SRO = 'SRO';      // State Register of organizations

	private $compatibilityMode = false;

	/** @var Socialservices\Properties\Client */
	private static $client = null;
	/** @var boolean|null */
	private static $isOnline = null;

	protected static $allowedCountries = array(1, 14);

	protected static function isRestModuleIncluded(): bool
	{
		static $result = null;

		if ($result === null)
		{
			$result = Loader::includeModule('rest');
		}

		return $result;
	}

	protected static function getClient()
	{
		Loader::includeModule('socialservices');
		if(self::$client === null)
		{
			self::$client = new Socialservices\Properties\Client();
		}

		return self::$client;
	}
	protected static function cleanStringValue($value)
	{
		$result = $value;

		$result = preg_replace('/^[ \t\-]+/'.BX_UTF_PCRE_MODIFIER, '', $result);
		$result = preg_replace('/ {2,}/'.BX_UTF_PCRE_MODIFIER, ' ', $result);
		$result = trim($result);

		return $result;
	}
	public static function isOnline()
	{
		if(self::$isOnline === null)
		{
			try
			{
				self::$isOnline = self::getClient()->isServiceOnline();
			}
			catch(Main\SystemException $ex)
			{
				self::$isOnline = false;
			}
		}
		return self::$isOnline;
	}
	public static function isEnabled($countryID)
	{
		if(!ModuleManager::isModuleInstalled('socialservices'))
		{
			return false;
		}

		if(!is_int($countryID))
		{
			$countryID = (int)$countryID;
		}

		return in_array($countryID, static::$allowedCountries, true) && self::isOnline();
	}

	public function setCompatibilityMode(bool $compatibilityMode): void
	{
		$this->compatibilityMode = $compatibilityMode;
	}

	public function resolveClient(string $propertyTypeID, string $propertyValue, int $countryID = 1): array
	{
		if (($countryID === 1 && $propertyTypeID === static::PROP_ITIN
				&& !RestrictionManager::isDetailsSearchByInnPermitted())
			|| ($countryID === 14 && $propertyTypeID === static::PROP_SRO
				&& !RestrictionManager::isDetailsSearchByEdrpouPermitted()))
		{
			return [];
		}


		if(!in_array($countryID, static::$allowedCountries, true))
		{
			throw new Main\NotSupportedException("Country ID: '{$countryID}' is not supported in current context.");
		}

		$fieldTitles = (new EntityRequisite)->getFieldsTitles($countryID);

		$dateFormat = Date::convertFormatToPhp(FORMAT_DATE);
		$nameFormat = Crm\Format\PersonNameFormatter::LastFirstSecondFormat;
		$alphaRegex = "/[[:alpha:]]/".BX_UTF_PCRE_MODIFIER;
		$results = array();

		Loc::loadMessages(__FILE__);

		if($propertyTypeID === self::PROP_ITIN)
		{
			if($countryID !== 1)
			{
				throw new Main\NotSupportedException("Country ID: '{$countryID}' is not supported in current context.");
			}

			$info = self::getClient()->getByInn($propertyValue);
			if(is_array($info))
			{
				$caption = '';
				$fields = null;
				$len = mb_strlen(isset($info['INN'])? $info['INN'] : '');
				$clientType = self::TYPE_UNKNOWN;
				if($len === 10)
				{
					$clientType = self::TYPE_COMPANY;
				}
				elseif($len === 12)
				{
					$clientType = self::TYPE_PERSON;
				}

				if($clientType === self::TYPE_COMPANY)
				{

					$fullName = isset($info['NAME']) ? $info['NAME'] : '';
					$shortName = isset($info['NAME_SHORT']) ? $info['NAME_SHORT'] : '';

					$fields = array(
						EntityRequisite::INN => isset($info['INN']) ? $info['INN'] : '',
						EntityRequisite::KPP => isset($info['KPP']) ? $info['KPP'] : '',
						EntityRequisite::OGRN => isset($info['OGRN']) ? $info['OGRN'] : '',
						EntityRequisite::OKVED => isset($info['OKVED_CODE']) ? $info['OKVED_CODE'] : '',
						EntityRequisite::COMPANY_NAME => $shortName,
						EntityRequisite::COMPANY_FULL_NAME => $fullName,
						EntityRequisite::IFNS => isset($info['TAX_REGISTRAR_NAME']) ? $info['TAX_REGISTRAR_NAME'] : ''
					);
					$presetId = Crm\EntityPreset::getByXmlId('#CRM_REQUISITE_PRESET_DEF_RU_COMPANY#');
					if ($presetId > 0)
					{
						$fields['PRESET_ID'] = $presetId;
						$fields['PRESET_COUNTRY_ID'] = EntityRequisite::getSingleInstance()
							->getCountryIdByPresetId($presetId);
					}

					$caption = $shortName !== '' ? $shortName : $fullName;

					$registrationDate = isset($info['CREATION_REGISTRATION_DATE'])
						? $info['CREATION_REGISTRATION_DATE'] : '';

					if($registrationDate === '' && isset($info['CREATION_OGRN_DATE']))
					{
						$registrationDate = $info['CREATION_OGRN_DATE'];
					}

					if($registrationDate !== '')
					{
						try
						{
							$d = new Date($registrationDate, 'Y-m-d');
							$fields[EntityRequisite::COMPANY_REG_DATE] = $d->format($dateFormat);
						}
						catch(Main\ObjectException $e)
						{
						}
					}

					$address1Parts = array();

					$street = isset($info['ADDRESS_STREET_NAME']) ? $info['ADDRESS_STREET_NAME'] : '';
					if($street !== '')
					{
						if(isset($info['ADDRESS_STREET_TYPE']) && $info['ADDRESS_STREET_TYPE'] === GetMessage('CRM_CLIENT_ADDRESS_STREET_TYPE'))
						{
							$street = GetMessage(
								'CRM_CLIENT_ADDRESS_TEMPLATE_STREET',
								array('#STREET#' => $street)
							);
						}
						elseif (isset($info['ADDRESS_STREET_TYPE']) && $info['ADDRESS_STREET_TYPE'] !== '')
						{
							$street .= ' ' . $info['ADDRESS_STREET_TYPE'];
						}
						$address1Parts[] = $street;
					}

					$house = isset($info['ADDRESS_HOUSE']) ? $info['ADDRESS_HOUSE'] : '';
					if($house !== '')
					{
						if(preg_match($alphaRegex, $house) === 0)
						{
							$address1Parts[] = GetMessage(
								'CRM_CLIENT_ADDRESS_TEMPLATE_HOUSE',
								array('#HOUSE#' => $house)
							);
						}
						else
						{
							$address1Parts[] = $house;
						}
					}

					$building = isset($info['ADDRESS_BUILDING']) ? $info['ADDRESS_BUILDING'] : '';
					if($building !== '')
					{
						if(preg_match($alphaRegex, $building) === 0)
						{
							$address1Parts[] = GetMessage(
								'CRM_CLIENT_ADDRESS_TEMPLATE_BUILDING',
								array('#BUILDING#' => $building)
							);
						}
						else
						{
							$address1Parts[] = $building;
						}
					}

					$address1 = implode(', ', $address1Parts);
					$address2 = isset($info['ADDRESS_FLAT']) ? $info['ADDRESS_FLAT'] : '';

					$city = isset($info['ADDRESS_CITY_NAME']) ? $info['ADDRESS_CITY_NAME'] : '';
					$cityType = isset($info['ADDRESS_CITY_TYPE']) ? $info['ADDRESS_CITY_TYPE'] : '';
					$region = isset($info['ADDRESS_AREA_NAME']) ? $info['ADDRESS_AREA_NAME'] : '';
					$province = isset($info['ADDRESS_REGION_NAME']) ? $info['ADDRESS_REGION_NAME'] : '';
					$provinceType = isset($info['ADDRESS_REGION_TYPE']) ? $info['ADDRESS_REGION_TYPE'] : '';
					$postalCode = isset($info['ADDRESS_INDEX']) ? $info['ADDRESS_INDEX'] : '';
					if($provinceType === GetMessage('CRM_CLIENT_ADDRESS_CITY_TYPE'))
					{
						$city = $province;
						$province = '';
					}
					elseif($provinceType !== '')
					{
						$province = "{$province} {$provinceType}";
					}

					$settlementName = isset($info['ADDRESS_SETTLEMENT_NAME']) ? $info['ADDRESS_SETTLEMENT_NAME'] : '';
					if($settlementName !== '')
					{
						if($cityType !== '')
						{
							if($cityType === GetMessage('CRM_CLIENT_ADDRESS_CITY_TYPE'))
							{
								$cityType = GetMessage('CRM_CLIENT_ADDRESS_BOROUGH');
							}
							$city = "{$cityType} {$city}";
						}

						$settlementType = isset($info['ADDRESS_SETTLEMENT_TYPE']) ? $info['ADDRESS_SETTLEMENT_TYPE'] : '';
						$settlement = $settlementType !== '' ? "{$settlementType} {$settlementName}" : $settlementName;
						$city = "{$settlement}, {$city}";
					}

					if ($this->compatibilityMode)
					{
						$fields['RQ_ADDR'] = array(
							EntityAddressType::Registered => array(
								'ADDRESS_1' => $address1,
								'ADDRESS_2' => $address2,
								'CITY' => $city,
								'REGION' => $region,
								'PROVINCE' => $province,
								'POSTAL_CODE' => $postalCode,
								'COUNTRY' => GetMessage('CRM_CLIENT_ADDRESS_COUNTRY_RUSSIA'),
							)
						);
					}
					else
					{
						$locationAddress = EntityAddress::makeLocationAddressByFields(
							[
								'ADDRESS_1' => $address1,
								'ADDRESS_2' => $address2,
								'CITY' => $city,
								'REGION' => $region,
								'PROVINCE' => $province,
								'POSTAL_CODE' => $postalCode,
								'COUNTRY' => GetMessage('CRM_CLIENT_ADDRESS_COUNTRY_RUSSIA')
							]
						);
						if ($locationAddress)
						{
							$fields['RQ_ADDR'] = array(
								EntityAddressType::Registered => $locationAddress->toJson()
							);
						}
						unset($locationAddress);
					}

					$directorName = '';
					$accountantName = '';
					if(isset($info['OFFICIALS']) && is_array($info['OFFICIALS']))
					{
						foreach($info['OFFICIALS'] as $person)
						{
							$positionType = isset($person['POSITION_TYPE']) ? (int)$person['POSITION_TYPE'] : 0;
							if($positionType === 2)
							{
								$directorName = \CCrmContact::PrepareFormattedName($person, $nameFormat);
							}
							elseif($positionType === 3)
							{
								$accountantName = \CCrmContact::PrepareFormattedName($person, $nameFormat);
							}

							//Crutch for Issue #81093 (looking for last director and accoutant)
							/*
							if($directorName !== '' && $accountantName !== '')
							{
								break;
							}
							*/
						}
					}

					if($directorName !== '')
					{
						$fields[EntityRequisite::COMPANY_DIRECTOR] = $directorName;
					}

					if($accountantName !== '')
					{
						$fields[EntityRequisite::COMPANY_ACCOUNTANT] = $accountantName;
					}
				}
				elseif($clientType === self::TYPE_PERSON)
				{
					$firstName = isset($info['NAME']) ? $info['NAME'] : '';
					$secondName = isset($info['SECOND_NAME']) ? $info['SECOND_NAME'] : '';
					$lastName = isset($info['LAST_NAME']) ? $info['LAST_NAME'] : '';

					$fullName = \CCrmContact::PrepareFormattedName(
						array('NAME' => $firstName, 'SECOND_NAME' => $secondName, 'LAST_NAME' => $lastName),
						$nameFormat
					);

					$caption =  $fullName;

					$fields = array(
						EntityRequisite::INN => isset($info['INN']) ? $info['INN'] : '',
						EntityRequisite::OGRNIP => isset($info['OGRNIP']) ? $info['OGRNIP'] : '',
						EntityRequisite::OKVED => isset($info['OKVED_CODE']) ? $info['OKVED_CODE'] : '',
						EntityRequisite::PERSON_FIRST_NAME => $firstName,
						EntityRequisite::PERSON_SECOND_NAME => $secondName,
						EntityRequisite::PERSON_LAST_NAME => $lastName,
						EntityRequisite::PERSON_FULL_NAME => $fullName,
						EntityRequisite::IFNS => isset($info['TAX_AUTHORITY_NAME']) ? $info['TAX_AUTHORITY_NAME'] : ''
					);
					$presetId = Crm\EntityPreset::getByXmlId('#CRM_REQUISITE_PRESET_DEF_RU_INDIVIDUAL#');
					if ($presetId > 0)
					{
						$fields['PRESET_ID'] = $presetId;
						$fields['PRESET_COUNTRY_ID'] = EntityRequisite::getSingleInstance()
							->getCountryIdByPresetId($presetId);
					}
				}

				if(is_array($fields))
				{
					$title = $caption;
					$subtitle = ($fields[EntityRequisite::INN] != '') ?
						$fieldTitles[EntityRequisite::INN] . ' ' . $fields[EntityRequisite::INN] : '';
					$results[] = [
						'caption' => $caption,
						'title' => $title,
						'subTitle' => $subtitle,
						'fields' => $fields
					];
				}
			}
		}
		else if ($propertyTypeID === self::PROP_SRO)
		{
			if($countryID !== 14)
			{
				throw new Main\NotSupportedException("Country ID: '{$countryID}' is not supported in current context.");
			}

			$info = self::getClient()->uaGetByEdrpou($propertyValue);
			if(is_array($info))
			{
				$fullName = isset($info['COMPANY_FULL_NAME']) ?
					static::cleanStringValue($info['COMPANY_FULL_NAME']) : '';
				$shortName = isset($info['COMPANY_NAME']) ? static::cleanStringValue($info['COMPANY_NAME']) : '';
				if (empty($shortName) && !empty($fullName))
					$shortName = $fullName;
				$director = isset($info['CEO_NAME']) ? static::cleanStringValue($info['CEO_NAME']) : '';

				$fields = array(
					EntityRequisite::EDRPOU => isset($info['EDRPOU']) ? $info['EDRPOU'] : '',
					EntityRequisite::COMPANY_NAME => $shortName,
					EntityRequisite::COMPANY_FULL_NAME => $fullName,
					EntityRequisite::COMPANY_DIRECTOR => $director,
				);
				$presetId = Crm\EntityPreset::getByXmlId('#CRM_REQUISITE_PRESET_DEF_UA_LEGALENTITY#');
				if ($presetId > 0)
				{
					$fields['PRESET_ID'] = $presetId;
					$fields['PRESET_COUNTRY_ID'] = EntityRequisite::getSingleInstance()
						->getCountryIdByPresetId($presetId);
				}
				$caption = $shortName !== '' ? $shortName : $fullName;

				$address2 = isset($info['ADDRESS']) ? static::cleanStringValue($info['ADDRESS']) : '';
				$address1 = $city = $region = $province = $postalCode = '';
				$countryList = Crm\EntityPreset::getCountryList();
				$countryName = isset($countryList[$countryID]) ? $countryList[$countryID] : '';

				if ($this->compatibilityMode)
				{
					$fields['RQ_ADDR'] = array(
						EntityAddressType::Registered => array(
							'ADDRESS_1' => $address1,
							'ADDRESS_2' => $address2,
							'CITY' => $city,
							'REGION' => $region,
							'PROVINCE' => $province,
							'POSTAL_CODE' => $postalCode,
							'COUNTRY' => $countryName,
						)
					);
				}
				else
				{
					$locationAddress = EntityAddress::makeLocationAddressByFields(
						[
							'ADDRESS_1' => $address1,
							'ADDRESS_2' => $address2,
							'CITY' => $city,
							'REGION' => $region,
							'PROVINCE' => $province,
							'POSTAL_CODE' => $postalCode,
							'COUNTRY' => $countryName,
						]
					);
					if ($locationAddress)
					{
						$fields['RQ_ADDR'] = array(
							EntityAddressType::Registered => $locationAddress->toJson()
						);
					}
					unset($locationAddress);
				}

				if(is_array($fields))
				{
					$title = $caption;
					$subtitle = ($fields[EntityRequisite::EDRPOU] != '') ?
						$fieldTitles[EntityRequisite::EDRPOU] . ' ' . $fields[EntityRequisite::EDRPOU] : '';
					$results[] = array(
						'caption' => $caption,
						'title' => $title,
						'subTitle' => $subtitle,
						'fields' => $fields
					);
				}
			}
		}
		else
		{
			throw new Main\ArgumentOutOfRangeException('propertyTypeID', self::PROP_ITIN, self::PROP_ITIN);
		}

		return $results;
	}

	public static function getPropertyTypeByCountry(int $countryId)
	{
		if ($countryId === 1 && // ru
			RestrictionManager::isDetailsSearchByInnPermitted())
		{
			$requisiteEntity = new EntityRequisite();
			$titles = $requisiteEntity->getFieldsTitles($countryId);
			return [
				'VALUE' => self::PROP_ITIN,
				'TITLE' => $titles[EntityRequisite::INN],
				'IS_PLACEMENT' => 'N',
				'COUNTRY_ID' => $countryId,
			];
		}
		if ($countryId === 14 && // ua
			RestrictionManager::isDetailsSearchByEdrpouPermitted())
		{
			$requisiteEntity = new EntityRequisite();
			$titles = $requisiteEntity->getFieldsTitles($countryId);
			return [
				'VALUE' => self::PROP_SRO,
				'TITLE' => $titles[EntityRequisite::EDRPOU],
				'IS_PLACEMENT' => 'N',
				'COUNTRY_ID' => $countryId,
			];
		}

		return null;
	}

	public static function getRestriction(int $countryId)
	{
		if ($countryId === 1 && // ru
			!RestrictionManager::isDetailsSearchByInnPermitted())
		{
			return RestrictionManager::getDetailsSearchByInnRestriction();
		}
		if ($countryId === 14 && // ua
			!RestrictionManager::isDetailsSearchByEdrpouPermitted())
		{
			return RestrictionManager::getDetailsSearchByEdrpouRestriction();
		}

		return null;
	}


	/**
	 * @deprecated Use instanced method $this->resolveClient() instead
	 * @param $propertyTypeID
	 * @param $propertyValue
	 * @param int $countryID
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 * @return array
	 */
	public static function resolve($propertyTypeID, $propertyValue, $countryID = 1)
	{
		$instance = new self();
		return $instance->resolveClient((string)$propertyTypeID, (string)$propertyValue, (int)$countryID);
	}

	public static function getDetailSearchHandlersByCountry(bool $noStaticCache = false): array
	{
		static $result = null;


		if ($result === null || $noStaticCache)
		{
			$result = [];
			if (static::isRestModuleIncluded())
			{
				$allowedCountriesMap = array_fill_keys(EntityRequisite::getAllowedRqFieldCountries(), true);
				$handlers = PlacementTable::getHandlersList(AppPlacement::REQUISITE_AUTOCOMPLETE);
				foreach ($handlers as $hadnlerInfo)
				{
					$filteredHandlerInfo = [
						'ID' => $hadnlerInfo['ID'],
						'TITLE' => $hadnlerInfo['TITLE'],
						'OPTIONS' => $hadnlerInfo['OPTIONS'],
					];
					$countries = [];
					if (
						isset($filteredHandlerInfo['OPTIONS']['countries'])
						&& is_string($filteredHandlerInfo['OPTIONS']['countries'])
						&& $filteredHandlerInfo['OPTIONS']['countries'] !== ''
					)
					{
						$optionValue = $filteredHandlerInfo['OPTIONS']['countries'];
						if (preg_match('/^[1-9][0-9]*(,[1-9][0-9]*)*$/', $optionValue))
						{
							$countryList = explode(',', $filteredHandlerInfo['OPTIONS']['countries']);
							if (is_array($countryList))
							{
								foreach ($countryList as $countryId)
								{
									$countryId = (int)$countryId;
									if (isset($allowedCountriesMap[$countryId]))
									{
										$countries[$countryId] = true;
									}
								}
								$countries = array_keys($countries);
							}
						}
					}
					if (empty($countries))
					{
						$countries = EntityRequisite::getAllowedRqFieldCountries();
					}
					foreach ($countries as $countryId)
					{
						if (!is_array($result[$countryId]))
						{
							$result[$countryId] = [];
						}
						$result[$countryId][] = $filteredHandlerInfo;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param int $countryId
	 * @return array|null
	 */
	public static function getClientResolverPropertyWithPlacements(int $countryId)
	{
		$result = ClientResolver::getPropertyTypeByCountry($countryId);

		$detailSearchHandlersByCountry = static::getDetailSearchHandlersByCountry();
		if (isset($detailSearchHandlersByCountry[$countryId]))
		{
			if (!is_array($result))
			{
				$result = [
					'COUNTRY_ID' => $countryId,
					'VALUE' => 'PLACEMENT_' . $detailSearchHandlersByCountry[$countryId][0]['ID'],
					'TITLE' => $detailSearchHandlersByCountry[$countryId][0]['TITLE'],
					'IS_PLACEMENT' => 'Y',
				];
			}
			$result['PLACEMENTS'] = $detailSearchHandlersByCountry[$countryId];
		}

		if (!is_array($result))
		{
			$defaultAppInfo = static::getDefaultClientResolverApplicationParams($countryId);
			if ($defaultAppInfo['code'] !== '')
			{
				if (!is_array($result))
				{
					$result = ['COUNTRY_ID' => $countryId];
				}
				$result['DEFAULT_APP_INFO'] = $defaultAppInfo;
			}
		}

		return $result;
	}

	protected static function getAppInfo(string $appCode, bool $noCache = false): array
	{
		static $appInfo = [];

		if (!isset($appInfo[$appCode]))
		{
			if (static::isRestModuleIncluded())
			{
				$info = Marketplace\Client::getInstall($appCode);
				$info = (is_array($info) && isset($info['ITEMS']) && is_array($info['ITEMS'])) ? $info['ITEMS'] : [];
			}

			$appInfo[$appCode] = (!empty($info) && CRestUtil::canInstallApplication($info)) ? $info : [];
		}

		return $appInfo[$appCode];
	}

	protected static function getDefaultClientResolverApplicationCodeByCountryMap()
	{
		static $map = null;

		if ($map === null)
		{
			$map  = [
				Requisite\Country::ID_BELARUS => 'integrations24.portal_nalog_gov_by',
				Requisite\Country::ID_KAZAKHSTAN => 'integrations24.mns_kazakhstan_poisk_po_bin',
			];
		}

		return $map;
	}

	public static function getDefaultClientResolverApplicationParams(int $countryId)
	{
		$result = [
			'code' => '',
			'title' => '',
			'isAvailable' => 'N',
			'isInstalled' => 'N',
		];

		$map = static::getDefaultClientResolverApplicationCodeByCountryMap();

		$appCode = $map[$countryId] ?? '';

		if ($appCode !== '' && static::isRestModuleIncluded())
		{
			$appInfo = static::getAppInfo($appCode);

			if (
				isset($appInfo['NAME'])
				&& is_string($appInfo['NAME'])
				&& $appInfo['NAME'] !== ''
			)
			{
				$result['code'] = $appCode;
				$result['isAvailable'] = 'Y';
				$result['title'] = $appInfo['NAME'];
			}
		}

		return $result;
	}

	public static function getClientResolverPlacementParams(int $countryId): ?array
	{
		$clientResolverPropertyType = static::getClientResolverPropertyWithPlacements($countryId);

		return (
			$clientResolverPropertyType
				? [
					'isPlacement' => (
						isset($clientResolverPropertyType['IS_PLACEMENT'])
						&& $clientResolverPropertyType['IS_PLACEMENT'] === 'Y'
					),
					'numberOfPlacements' =>
						isset($clientResolverPropertyType['PLACEMENTS']) && is_array($clientResolverPropertyType['PLACEMENTS'])
							? count($clientResolverPropertyType['PLACEMENTS'])
							: 0
					,
					'countryId' => $countryId,
					'defaultAppInfo' =>
						$clientResolverPropertyType['DEFAULT_APP_INFO']
						?? [
							'code' => '',
							'title' => '',
							'isAvailable' => 'N',
							'isInstalled' => 'N',
						],
				]
				: null
		);
	}

	public static function getClientResolverPlaceholderText(int $countryId): string
	{
		$clientResolverPropertyType = static::getClientResolverPropertyWithPlacements($countryId);
		$title =
			(is_array($clientResolverPropertyType) && isset($clientResolverPropertyType['TITLE']))
				? $clientResolverPropertyType['TITLE']
				: ''
		;
		if (is_string($title) && $title !== '' && $clientResolverPropertyType['IS_PLACEMENT'] !== 'Y')
		{
			$template = Loc::getMessage('CRM_CLIENT_REQUISITE_AUTOCOMPLETE_FILL_IN');
			if (is_string($template) && $template !== '')
			{
				$template = mb_strtolower($template);
				$title = strtr($template, ['#field_name#' => $title]);
			}
		}

		return $title;
	}
}
