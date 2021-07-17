<?php
Use Bitrix\Main\Loader,
	Bitrix\Catalog;

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

		if(COption::GetOptionString("crm", "vatModeSetted", 'N') == 'Y')
		{
			self::$bVatMode = true;
		}
		else
		{
			$nActiveVats = CCatalogVat::GetListEx(array(), array('ACTIVE' => 'Y'), array(), false, array('ID'));
			self::$bVatMode = (intval($nActiveVats) > 0);
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

		if($strActiveVats <> '')
		{
			$arActiveVats = explode(',', $strActiveVats);

			foreach ($arActiveVats as $vatId)
			{
				CCatalogVat::Update($vatId, array('ACTIVE' => 'Y'));
				$count++;
			}
		}
		else
		{
			$dbVats = CCatalogVat::GetListEx(array(), array('!ACTIVE' => 'Y'), false, false, array('ID'));
			while($arVat = $dbVats->Fetch())
			{
				CCatalogVat::Update($arVat['ID'], array('ACTIVE' => 'Y'));
				$count++;
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

		$dbActiveVats = CCatalogVat::GetListEx(array(), array('ACTIVE' => 'Y'), false, false, array('ID'));
		while($arVat = $dbActiveVats->Fetch())
		{
			$arActiveVats[] = $arVat['ID'];
			CCatalogVat::Update($arVat['ID'], array('ACTIVE' => 'N'));
			$count++;
		}

		$strActiveVats = !empty($arActiveVats) ? implode(',', $arActiveVats) : '';
		COption::SetOptionString("crm", "crmSaveActiveVats", $strActiveVats);
		COption::SetOptionString("crm", "vatModeSetted", 'N');
		self::$bVatMode = false;

		return $count;
	}
	public static function GetVatRateNameByValue($value)
	{
		$value = round((float)$value, 2);
		$infos = self::GetVatRateInfos();
		foreach($infos as $info)
		{
			if($info['VALUE'] === $value)
			{
				return $info['NAME'];
			}
		}
		unset($info);

		return "{$value}%";
	}
	public static function GetDefaultVatRateInfo()
	{
		if(self::$DEFAULT_VAT_RATE !== null)
		{
			return self::$DEFAULT_VAT_RATE;
		}

		if(!Loader::includeModule('catalog'))
		{
			return null;
		}

		$dbResult = CCatalogVat::GetListEx(array('SORT' => 'ASC'), array('ACTIVE' => 'Y'), false, array('nPageTop' => 1));
		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(is_array($fields))
		{
			$ID = intval($fields['ID']);
			self::$DEFAULT_VAT_RATE = array(
				'ID' => $ID,
				'NAME' => isset($fields['NAME']) ? $fields['NAME'] : "[{$ID}]",
				'VALUE' => isset($fields['RATE']) ? round(doubleval($fields['RATE']), 2) : 0.0
			);
		}

		return self::$DEFAULT_VAT_RATE;
	}
	public static function GetVatRateInfos()
	{
		if(self::$VAT_RATES !== null)
		{
			return self::$VAT_RATES;
		}

		if (!Loader::includeModule('catalog'))
		{
			return array();
		}

		self::$VAT_RATES = array();
		$dbResult = Catalog\VatTable::getList([
			'select' => ['ID', 'NAME', 'RATE', 'SORT'],
			'filter' => ['=ACTIVE' => 'Y'],
			'order' => ['SORT' => 'ASC']
		]);
		if(is_object($dbResult))
		{
			while($fields = $dbResult->fetch())
			{
				$ID = (int)$fields['ID'];
				self::$VAT_RATES[] = array(
					'ID' => $ID,
					'NAME' => $fields['NAME'] ?? "[{$ID}]",
					'VALUE' => isset($fields['RATE']) ? round((float)$fields['RATE'], 2) : 0.0
				);
			}
		}
		return self::$VAT_RATES;
	}
}
