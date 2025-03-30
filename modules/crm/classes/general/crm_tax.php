<?php
Use Bitrix\Main\Loader,
	Bitrix\Catalog;
use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);

class CCrmTax
{
	private static $TAXES = null;
	private static $VAT_RATES = null;
	private static $DEFAULT_VAT_RATE = null;
	private static $bVatMode = null;

	public static function GetAll()
	{
		$taxes = self::$TAXES ?? null;

		if(!$taxes && Loader::includeModule('sale'))
		{
			$taxes = array();
			$dbResultList = CSaleTax::GetList( array('NAME' => 'ASC')	);

			while ($arTax = $dbResultList->Fetch())
				$taxes[$arTax['ID']] = $arTax;

			self::$TAXES = $taxes;
		}

		return $taxes;
	}

	public static function GetByID($taxID)
	{
		$taxID = (int)$taxID;
		if ($taxID <= 0)
		{
			return false;
		}

		$taxies = self::GetAll();

		return $taxies[$taxID] ?? false;
	}

	public static function GetRatesById($taxID)
	{
		if(!Loader::includeModule('sale'))
			return false;

		$arRates = array();

		$arFilter = array();

		if(intval($taxID) > 0)
			$arFilter['TAX_ID'] = $taxID;

		$dbResultList = CSaleTaxRate::GetList(array('ID' => 'asc'), $arFilter);

		while($arRate = $dbResultList->Fetch())
			$arRates[$arRate['ID']] = $arRate;

		return $arRates;
	}

	public static function getSitesList()
	{
		static $arSites = array();

		if(empty($arSites))
		{
			$dbSites = CSite::GetList();
			while ($arSite = $dbSites->Fetch())
				$arSites[$arSite["LID"]] = "[".$arSite["LID"]."] ".$arSite["NAME"];
		}

		return $arSites;
	}

	/**
	 * It Returns if sale module work in vat - mode.
	 * It means that counts item-depended vat taxes.
	 * @return bool
	 */
	public static function isVatMode()
	{
		if(self::$bVatMode !== null)
			return self::$bVatMode;

		if(!Loader::includeModule('catalog'))
			return false;

		if (COption::GetOptionString("crm", "vatModeSetted", 'N') == 'Y')
		{
			self::$bVatMode = true;
		}
		else
		{
			self::$bVatMode = (bool)Catalog\VatTable::getRow([
				'select' => ['ID'],
				'filter' => [
					'=ACTIVE' => 'Y',
				],
				'cache' => [
					'ttl' => 86400,
				],
			]);
		}

		return self::$bVatMode;
	}

	public static function isTaxMode()
	{
		if (self::isVatMode())
			return false;

		if(!Loader::includeModule('sale'))
			return false;

		$dbActiveTaxRates = CSaleTaxRate::GetList(array(), array('ACTIVE' => 'Y'));

		$arFields = $dbActiveTaxRates->Fetch();
		return is_array($arFields);
	}

	public static function setVatMode()
	{
		if(!Loader::includeModule('catalog'))
			return false;

		if(self::isVatMode())
			return true;

		$count = 0;
		$strActiveVats = COption::GetOptionString("crm", "crmSaveActiveVats", '');

		if( $strActiveVats <> '')
		{
			$arActiveVats = explode(',', $strActiveVats);

			foreach ($arActiveVats as $vatId)
			{
				$result = Catalog\Model\Vat::update(
					(int)$vatId,
					[
						'ACTIVE' => 'Y',
					]
				);
				if ($result->isSuccess())
				{
					$count++;
				}
			}
		}
		else
		{
			$dbVats = Catalog\Model\Vat::getList([
				'select' => [
					'ID',
				],
				'filter' => [
					'!=ACTIVE' => 'Y',
				],
			]);
			while ($arVat = $dbVats->fetch())
			{
				$result = Catalog\Model\Vat::update(
					(int)$arVat['ID'],
					[
						'ACTIVE' => 'Y',
					]
				);
				if ($result->isSuccess())
				{
					$count++;
				}
			}
		}

		COption::SetOptionString("crm", "vatModeSetted", 'Y');
		self::$bVatMode = true;

		return $count;
	}

	public static function unSetVatMode()
	{
		if(!Loader::includeModule('catalog'))
			return false;

		$count = 0;
		$arActiveVats = array();

		$dbActiveVats = Catalog\Model\Vat::getList([
			'select' => [
				'ID',
			],
			'filter' => [
				'=ACTIVE' => 'Y',
			],
		]);
		while($arVat = $dbActiveVats->Fetch())
		{
			$arActiveVats[] = $arVat['ID'];
			$result = Catalog\Model\Vat::update(
				(int)$arVat['ID'],
				[
					'ACTIVE' => 'N',
				]
			);
			if ($result->isSuccess())
			{
				$count++;
			}
		}

		$strActiveVats = !empty($arActiveVats) ? implode(',', $arActiveVats) : '';
		COption::SetOptionString("crm", "crmSaveActiveVats", $strActiveVats);
		COption::SetOptionString("crm", "vatModeSetted", 'N');
		self::$bVatMode = false;

		return $count;
	}

	public static function GetVatRateNameByValue($value)
	{
		$value = isset($value) ? round((float)$value, 2) : null;
		$infos = self::GetVatRateInfos();
		foreach ($infos as $info)
		{
			if($info['VALUE'] === $value)
			{
				return $info['NAME'];
			}
		}
		unset($info);

		if ($value === null)
		{
			return Loc::getMessage('CRM_VAT_EMPTY_VALUE_MSGVER_1');
		}

		return "{$value}%";
	}
	public static function GetDefaultVatRateInfo(): ?array
	{
		if (self::$DEFAULT_VAT_RATE !== null)
		{
			return self::$DEFAULT_VAT_RATE;
		}

		if (!Loader::includeModule('catalog'))
		{
			return null;
		}

		$defaultVatIdInProductCatalog = 0;

		$defaultProductCatalogId = \Bitrix\Crm\Product\Catalog::getDefaultId();

		if ($defaultProductCatalogId > 0)
		{
			$vatInfo = Catalog\CatalogIblockTable::getRow([
				'select' => [
					'VAT_ID',
				],
				'filter' => [
					'=IBLOCK_ID' => $defaultProductCatalogId,
				],
			]);

			if (!empty($vatInfo['VAT_ID']))
			{
				$defaultVatIdInProductCatalog = (int)$vatInfo['VAT_ID'];
			}
		}

		$filter = [
			'=ACTIVE' => 'Y',
		];

		if ($defaultVatIdInProductCatalog > 0)
		{
			$filter['=ID'] = $defaultVatIdInProductCatalog;
		}

		$fields = Catalog\VatTable::getRow([
			'select' => [
				'ID',
				'SORT',
				'NAME',
				'RATE',
				'EXCLUDE_VAT',
			],
			'filter' => $filter,
			'order' => [
				'RATE' => 'ASC',
				'ID' => 'ASC',
			]
		]);
		if (is_array($fields))
		{
			$ID = (int)$fields['ID'];
			self::$DEFAULT_VAT_RATE = [
				'ID' => $ID,
				'NAME' => $fields['NAME'] ?? '[' . $ID . ']',
				'VALUE' => $fields['EXCLUDE_VAT'] === 'Y' ? null : round((float)$fields['RATE'], 2),
			];
		}

		return self::$DEFAULT_VAT_RATE;
	}

	public static function GetVatRateInfos(): array
	{
		if (self::$VAT_RATES !== null)
		{
			return self::$VAT_RATES;
		}

		if (!Loader::includeModule('catalog'))
		{
			return [];
		}

		self::$VAT_RATES = [];
		$dbResult = Catalog\VatTable::getList([
			'select' => [
				'ID',
				'NAME',
				'RATE',
				'SORT',
				'EXCLUDE_VAT',
			],
			'filter' => [
				'=ACTIVE' => 'Y',
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC',
			]
		]);
		while ($fields = $dbResult->fetch())
		{
			$ID = (int)$fields['ID'];
			self::$VAT_RATES[] = [
				'ID' => $ID,
				'NAME' => $fields['NAME'] ?? '[' . $ID . ']',
				'VALUE' => $fields['EXCLUDE_VAT'] === 'Y' ? null : round((float)$fields['RATE'], 2),
			];
		}

		return self::$VAT_RATES;
	}
}
