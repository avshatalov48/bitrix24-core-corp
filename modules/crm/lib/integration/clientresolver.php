<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Integration\Rest\AppPlacement;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Crm;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Requisite;

class ClientResolver extends ResolverBase
{
	const TYPE_COMPANY = 1;
	const TYPE_PERSON = 2;
	const PROP_ITIN = 'ITIN';    // Individual Taxpayer Identification Number
	const PROP_SRO = 'SRO';      // State Register of organizations

	private $compatibilityMode = false;

	protected static $allowedCountries = [1, 14];
	protected static $allowedTypesMap = [1 => ['ITIN'], 14 => ['SRO']];

	public function setCompatibilityMode(bool $compatibilityMode): void
	{
		$this->compatibilityMode = $compatibilityMode;
	}

	public function resolveClient(string $propertyTypeID, string $propertyValue, int $countryID = 1): array
	{
		if (
			(
				$countryID === 1
				&& $propertyTypeID === static::PROP_ITIN
				&& !RestrictionManager::isDetailsSearchByInnPermitted()
			)
			|| (
				$countryID === 14
				&& $propertyTypeID === static::PROP_SRO
				&& !RestrictionManager::isDetailsSearchByEdrpouPermitted()
			)
		)
		{
			return [];
		}

		if (
			!in_array($countryID, static::$allowedCountries, true)
			|| ($propertyTypeID === self::PROP_ITIN && $countryID !== 1)
			|| ($propertyTypeID === self::PROP_SRO && $countryID !== 14)
		)
		{
			static::throwCountryException($countryID);
		}

		$fieldTitles = (new EntityRequisite)->getFieldsTitles($countryID);

		$dateFormat = Date::convertFormatToPhp(FORMAT_DATE);
		$nameFormat = Crm\Format\PersonNameFormatter::LastFirstSecondFormat;
		$alphaRegex = "/[[:alpha:]]/u";
		$results = array();

		Loc::loadMessages(__FILE__);

		if($propertyTypeID === self::PROP_ITIN)
		{
			$info = self::getClient()->getByInn($propertyValue);
			if(is_array($info))
			{
				$caption = '';
				$fields = null;

				if (isset($info['INN']))
				{
					$len = mb_strlen($info['INN']);
				}
				else
				{
					$len = 0;
				}

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

					$fullName = $info['NAME'] ?? '';
					$shortName = $info['NAME_SHORT'] ?? '';

					$fields = [
						EntityRequisite::INN => $info['INN'] ?? '',
						EntityRequisite::KPP => $info['KPP'] ?? '',
						EntityRequisite::OGRN => $info['OGRN'] ?? '',
						EntityRequisite::OKVED => $info['OKVED_CODE'] ?? '',
						EntityRequisite::COMPANY_NAME => $shortName,
						EntityRequisite::COMPANY_FULL_NAME => $fullName,
						EntityRequisite::IFNS => $info['TAX_REGISTRAR_NAME'] ?? '',
					];
					$presetId = Crm\EntityPreset::getByXmlId('#CRM_REQUISITE_PRESET_DEF_RU_COMPANY#');
					if ($presetId > 0)
					{
						$fields['PRESET_ID'] = $presetId;
						$fields['PRESET_COUNTRY_ID'] =
							EntityRequisite::getSingleInstance()
								->getCountryIdByPresetId($presetId)
						;
					}

					$caption = $shortName !== '' ? $shortName : $fullName;

					$registrationDate = $info['CREATION_REGISTRATION_DATE'] ?? '';

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
		elseif ($propertyTypeID === self::PROP_SRO)
		{
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
			throw new Main\ArgumentException('propertyTypeID');
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

	public static function getDetailSearchHandlersByCountry(
		bool $noStaticCache = false,
		string $placementCode = AppPlacement::REQUISITE_AUTOCOMPLETE
	): array
	{
		return parent::getDetailSearchHandlersByCountry($noStaticCache, $placementCode);
	}

	public static function getClientResolverPlacementParams(int $countryId): ?array
	{
		$placementParams = parent::getClientResolverPlacementParams($countryId);
		if (is_array($placementParams))
		{
			$placementParams['placementCode'] = AppPlacement::REQUISITE_AUTOCOMPLETE;
		}

		return $placementParams;
	}

	protected static function getAppTitle(string $appCode): string
	{
		// APP_TITLE_INTEGRATIONS24_PORTAL_NALOG_GOV_BY
		// APP_TITLE_INTEGRATIONS24_MNS_KAZAKHSTAN_POISK_PO_BIN
		return (string)Loc::getMessage('APP_TITLE_'.mb_strtoupper(preg_replace('/[^0-9a-zA-Z_]/', '_',$appCode)));
	}

	public static function getClientResolverPropertyWithPlacements(
		int $countryId,
		string $placementCode = AppPlacement::REQUISITE_AUTOCOMPLETE
	)
	{
		return parent::getClientResolverPropertyWithPlacements($countryId, $placementCode);
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

	public static function isPlacementCodeAllowed(string $placementCode)
	{
		return ($placementCode === AppPlacement::REQUISITE_AUTOCOMPLETE);
	}
}
