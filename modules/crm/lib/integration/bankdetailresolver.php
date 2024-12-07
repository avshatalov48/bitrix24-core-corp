<?php
namespace Bitrix\Crm\Integration;

use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\Integration\Rest\AppPlacement;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;

class BankDetailResolver extends ResolverBase
{
	const TYPE_BIC = 3;

	const PROP_BIC = 'BIC';      // Bank identification code

	protected static $allowedCountries = [1];
	protected static $allowedTypesMap = [1 => ['BIC']];

	public static function isGetByBicAvailable(): bool
	{
		return method_exists(self::getClient(), 'getByBic');
	}

	public function resolveClient(string $propertyTypeID, string $propertyValue, int $countryID = 1): array
	{
		if (
			$countryID === 1
			&& $propertyTypeID === static::PROP_BIC
			&& !RestrictionManager::isDetailsSearchByInnPermitted()
		)
		{
			return [];
		}


		if (
			!in_array($countryID, static::$allowedCountries, true)
			|| (($propertyTypeID === static::PROP_BIC) && $countryID !== 1)
		)
		{
			static::throwCountryException($countryID);
		}

		$results = [];

		Loc::loadMessages(__FILE__);

		if ($propertyTypeID === static::PROP_BIC)
		{
			$info = self::getClient()->getByBic($propertyValue);
			if(is_array($info))
			{
				$caption = $info['NAMEP'] ?? '';
				$title = $caption;
				$fieldTitles = (new EntityBankDetail())->getFieldsTitles($countryID);
				$bic = $info['BIC'] ?? '';
				$subtitle = ($bic === '' ? '' : $fieldTitles['RQ_BIK'] . ' ' . $bic);
				$np = '';
				if (isset($info['NNP']) && is_string($info['NNP']) && $info['NNP'] !== '')
				{
					if (isset($info['TNP']) && is_string($info['TNP']) && $info['TNP'] !== '')
					{
						$np .= $info['TNP'] . '. ';
					}
					$np .= $info['NNP'];
				}
				$addrComponents = [$info['IND'] ?? '', $np, $info['ADR'] ?? ''];
				$n = 0;
				$bankAddr = '';
				foreach ($addrComponents as $item)
				{
					if (is_string($item) && $item !== '')
					{
						$bankAddr .= ($n++ > 0 ? ', ' : '') . $item;
					}
				}
				unset($np, $n, $addrComponents, $item);
				$corAccNum = '';
				if (isset($info['ACCOUNTS']) && is_array($info['ACCOUNTS']))
				{
					foreach ($info['ACCOUNTS'] as $accInfo)
					{
						if (
							isset(
								$accInfo['ACCOUNTSTATUS'],
								$accInfo['REGULATIONACCOUNTTYPE'],
								$accInfo['ACCOUNT']
							)
							&& $accInfo['ACCOUNTSTATUS'] === 'ACAC'
							&& $accInfo['REGULATIONACCOUNTTYPE'] === 'CRSA'
							&& is_string($accInfo['ACCOUNT'])
						)
						{
							$corAccNum = $accInfo['ACCOUNT'];
							break;
						}
					}
					unset($accInfo);
				}
				$bicSw = '';
				if (isset($info['BICSW']) && is_array($info['BICSW']))
				{
					foreach ($info['BICSW'] as $bicSwInfo)
					{
						if (
							isset($bicSwInfo['SWBIC'])
							&& isset($bicSwInfo['DEFAULTSWBIC'])
							&& $bicSwInfo['DEFAULTSWBIC'] === '1'
							&& is_string($bicSwInfo['SWBIC'])
						)
						{
							$bicSw = $bicSwInfo['SWBIC'];
							break;
						}
					}
				}
				unset($bicSwInfo);

				$results[] = [
					'caption' => $caption,
					'title' => $title,
					'subTitle' => $subtitle,
					'fields' => [
						'NAME' => $title,
						'RQ_BANK_NAME' => $title,
						'RQ_BIK' => $info['BIC'] ?? '',
						'RQ_BANK_ADDR' => $bankAddr,
						'RQ_COR_ACC_NUM' => $corAccNum,
						'RQ_SWIFT' => $bicSw,
					]
				];
			}
		}
		else
		{
			throw new ArgumentException('propertyTypeID');
		}

		return $results;
	}

	public static function getPropertyTypeByCountry(int $countryId)
	{
		if ($countryId === 1 && // ru
			RestrictionManager::isDetailsSearchByInnPermitted())
		{
			$titles = EntityBankDetail::getSingleInstance()->getFieldsTitles($countryId);
			return [
				'VALUE' => static::PROP_BIC,
				'TITLE' => $titles['RQ_BIK'],
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

		return null;
	}

	public static function isPlacementCodeAllowed(string $placementCode)
	{
		return ($placementCode === AppPlacement::BANK_DETAIL_AUTOCOMPLETE);
	}

	public static function getDetailSearchHandlersByCountry(
		bool $noStaticCache = false,
		string $placementCode = AppPlacement::BANK_DETAIL_AUTOCOMPLETE
	): array
	{
		return parent::getDetailSearchHandlersByCountry($noStaticCache, $placementCode);
	}

	public static function getClientResolverPlacementParams(int $countryId): ?array
	{
		$placementParams = parent::getClientResolverPlacementParams($countryId);
		if (is_array($placementParams))
		{
			$placementParams['placementCode'] = AppPlacement::BANK_DETAIL_AUTOCOMPLETE;
		}

		return $placementParams;
	}

	public static function getClientResolverPropertyWithPlacements(
		int $countryId,
		string $placementCode = AppPlacement::BANK_DETAIL_AUTOCOMPLETE
	)
	{
		return parent::getClientResolverPropertyWithPlacements($countryId, $placementCode);
	}
}
