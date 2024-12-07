<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\SystemException;
use Bitrix\Rest\PlacementTable;
use Bitrix\Socialservices;
use CRestUtil;

class ResolverBase
{
	const TYPE_UNKNOWN = 0;

	protected static $allowedCountries = [];
	protected static $allowedTypesMap = [];

	/** @var Socialservices\Properties\Client */
	private static $client = null;
	/** @var boolean|null */
	private static $isOnline = null;

	public static function getAllowedTypesMap(): array
	{
		return static::$allowedTypesMap;
	}

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

		$result = preg_replace('/^[ \t\-]+/u', '', $result);
		$result = preg_replace('/ {2,}/u', ' ', $result);
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
			catch (SystemException $ex)
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

	protected static function throwCountryException(int $countryID): void
	{
		throw new NotSupportedException("Country ID: '$countryID' is not supported in current context.");
	}

	public function resolveClient(string $propertyTypeID, string $propertyValue, int $countryID = 1): array
	{
		return [];
	}

	public static function getPropertyTypeByCountry(int $countryId)
	{
		return null;
	}

	public static function getRestriction(int $countryId)
	{
		return null;
	}

	/**
	 * @deprecated Use instanced method $this->resolveClient() instead
	 * @param $propertyTypeID
	 * @param $propertyValue
	 * @param $countryID
	 * @return array
	 */
	public static function resolve($propertyTypeID, $propertyValue, $countryID = 1)
	{
		return (new static())->resolveClient((string)$propertyTypeID, (string)$propertyValue, (int)$countryID);
	}

	public static function isPlacementCodeAllowed(string $placementCode)
	{
		return false;
	}

	public static function getDetailSearchHandlersByCountry(
		bool $noStaticCache = false,
		string $placementCode = ''
	): array
	{
		static $result = [];

		if (!static::isPlacementCodeAllowed($placementCode))
		{
			return [];
		}

		if (!isset($result[$placementCode]) || $noStaticCache)
		{
			$result[$placementCode] = [];
			if (static::isRestModuleIncluded())
			{
				$allowedCountriesMap = array_fill_keys(EntityRequisite::getAllowedRqFieldCountries(), true);
				$handlers = PlacementTable::getHandlersList($placementCode);
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
						if (!is_array($result[$placementCode][$countryId]))
						{
							$result[$placementCode][$countryId] = [];
						}
						$result[$placementCode][$countryId][] = $filteredHandlerInfo;
					}
				}
			}
		}

		return $result[$placementCode];
	}

	/**
	 * @param int $countryId
	 * @param string $placementCode
	 * @return array|null
	 */
	public static function getClientResolverPropertyWithPlacements(
		int $countryId,
		string $placementCode = ''
	)
	{
		$result = static::getPropertyTypeByCountry($countryId);

		$detailSearchHandlersByCountry = static::getDetailSearchHandlersByCountry(false, $placementCode);
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

	protected static function getAppTitle(string $appCode): string
	{
		return '';
	}

	protected static function getDefaultClientResolverApplicationCodeByCountryMap()
	{
		return [];
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

		if ($appCode !== '' && static::isRestModuleIncluded() && CRestUtil::canInstallApplication())
		{
			$appTitle = static::getAppTitle($appCode);

			if ($appTitle !== '')
			{
				$result['code'] = $appCode;
				$result['isAvailable'] = 'Y';
				$result['title'] = $appTitle;
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
			$title = static::getClientResolverPlaceholderTextByTitle($title);
		}

		return $title;
	}

	public static function getClientResolverPlaceholderTextByTitle(string $title): string
	{
		if ($title !== '')
		{
			$modifiedTitle = Loc::getMessage(
				'CRM_CLIENT_REQUISITE_AUTOCOMPLETE_FILL_IN_02',
				['#FIELD_NAME#' => $title]
			);

			if (is_string($modifiedTitle) && $modifiedTitle !== '')
			{
				$title = $modifiedTitle;
			}
		}

		return $title;
	}
}
