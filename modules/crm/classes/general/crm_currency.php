<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\LanguageTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Currency;

class CCrmCurrency
{
	/** @var \CCurrencyRates */
	private static $currencyRatesClassName = \CCurrencyRates::class;
	private static ?string $BASE_CURRENCY_ID = null;
	private static $ACCOUNT_CURRENCY_ID = null;
	private static array $CURRENCY_BY_LANG = [];
	private static array $CURRENCY_FORMAT_BY_LANG = [];
	protected static $LAST_ERROR = '';
	// Default currency is stub that used only when 'currency' module is not installed
	protected static $DEFAULT_CURRENCY_ID = '';
	private static $FIELD_INFOS = null;
	private static $LOC_FIELD_INFOS = null;
	private static $LANGS_ID = null;

	// Get Fields Metadata
	public static function GetFieldsInfo()
	{
		if (!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = [
				'CURRENCY' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::UserPKey,
					],
				],
				'AMOUNT_CNT' => [
					'TYPE' => 'int',
				],
				'AMOUNT' => [
					'TYPE' => 'double',
				],
				'BASE' => [
					'TYPE' => 'char',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::ReadOnly,
					],
				],
				'SORT' => [
					'TYPE' => 'int',
				],
				'DATE_UPDATE' => [
					'TYPE' => 'datetime',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::ReadOnly,
					],
				],
				'LID' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::ReadOnly,
					],
				],
				'FORMAT_STRING' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::ReadOnly,
					],
				],
				'FULL_NAME' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::ReadOnly,
					],
				],
				'DEC_POINT' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::ReadOnly,
					],
				],
				'THOUSANDS_SEP' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::ReadOnly,
					],
				],
				'DECIMALS' => [
					'TYPE' => 'int',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::ReadOnly,
					],
				],
			];
		}

		return self::$FIELD_INFOS;
	}

	public static function GetFieldCaption($fieldName)
	{
		$messageId = match ((string)$fieldName)
		{
			'AMOUNT' => 'CRM_CURRENCY_FIELD_AMOUNT_MSGVER_1',
			'AMOUNT_CNT' => 'CRM_CURRENCY_FIELD_AMOUNT_CNT_MSGVER_1',
			default => 'CRM_CURRENCY_FIELD_' . $fieldName,
		};

		return (string)Loc::getMessage($messageId);
	}

	public static function GetCurrencyLocalizationFieldsInfo()
	{
		if (!self::$LOC_FIELD_INFOS)
		{
			self::$LOC_FIELD_INFOS = [
				'FULL_NAME' => [
					'TYPE' => 'string',
				],
				'FORMAT_STRING' => [
					'TYPE' => 'string',
				],
				'DEC_POINT' => [
					'TYPE' => 'string',
				],
				'THOUSANDS_VARIANT' => [
					'TYPE' => 'string',
				],
				'THOUSANDS_SEP' => [
					'TYPE' => 'string',
				],
				'DECIMALS' => [
					'TYPE' => 'int',
				],
				'HIDE_ZERO' => [
					'TYPE' => 'char',
				],
			];
		}

		return self::$LOC_FIELD_INFOS;
	}

	public static function GetDefaultCurrencyID()
	{
		if (self::$DEFAULT_CURRENCY_ID !== '')
		{
			return self::$DEFAULT_CURRENCY_ID;
		}

		self::$DEFAULT_CURRENCY_ID = 'USD';

		$row = LanguageTable::getRow([
			'select' => [
				'ID',
			],
			'filter' => [
				'=ID' => 'ru',
			],
			'cache' => [
				'ttl' => 86400,
			],
		]);
		if ($row !== null)
		{
			self::$DEFAULT_CURRENCY_ID = 'RUB';
		}
		else
		{
			$row = LanguageTable::getRow([
				'select' => [
					'ID',
				],
				'filter' => [
					'=ID' => 'de',
				],
				'cache' => [
					'ttl' => 86400,
				],
			]);
			if ($row !== null)
			{
				self::$DEFAULT_CURRENCY_ID = 'EUR';
			}
		}

		return self::$DEFAULT_CURRENCY_ID;
	}

	public static function NormalizeCurrencyID($currencyID)
	{
		return mb_strtoupper(trim((string)$currencyID));
	}

	public static function GetBaseCurrencyID()
	{
		if (!Loader::includeModule('currency'))
		{
			return self::GetDefaultCurrencyID();
		}

		if (!self::$BASE_CURRENCY_ID)
		{
			self::$BASE_CURRENCY_ID = (string)Currency\CurrencyManager::getBaseCurrency();
		}

		return self::$BASE_CURRENCY_ID;
	}

	public static function SetBaseCurrencyID($currencyID)
	{
		if (!Loader::includeModule('currency'))
		{
			return false;
		}

		return CCurrency::SetBaseCurrency($currencyID);
	}

	// Is used in reports only
	public static function GetAccountCurrencyID()
	{
		if (!self::$ACCOUNT_CURRENCY_ID)
		{
			self::$ACCOUNT_CURRENCY_ID = COption::GetOptionString('crm', 'account_currency_id', '');
			if (!isset(self::$ACCOUNT_CURRENCY_ID[0]))
			{
				self::$ACCOUNT_CURRENCY_ID = self::GetBaseCurrencyID();
			}
		}

		return self::$ACCOUNT_CURRENCY_ID;
	}

	public static function SetAccountCurrencyID($currencyID)
	{
		$currencyID = self::NormalizeCurrencyID($currencyID);
		if ($currencyID === self::$ACCOUNT_CURRENCY_ID)
		{
			return;
		}

		self::$ACCOUNT_CURRENCY_ID = $currencyID;
		COption::SetOptionString('crm', 'account_currency_id', self::$ACCOUNT_CURRENCY_ID);

		CCrmDeal::OnAccountCurrencyChange();
		CCrmLead::OnAccountCurrencyChange();
	}

	public static function GetAccountCurrency()
	{
		return self::GetByID(self::GetAccountCurrencyID());
	}

	public static function GetBaseCurrency()
	{
		if (!Loader::includeModule('currency'))
		{
			return false;
		}

		$baseCurrencyID = Currency\CurrencyManager::getBaseCurrency();
		if ($baseCurrencyID === null)
		{
			return false;
		}

		return self::GetByID($baseCurrencyID);
	}

	public static function EnsureReady()
	{
		if (!Loader::includeModule('currency'))
		{
			self::$LAST_ERROR = Loc::getMessage('CRM_CURRERCY_MODULE_WARNING');

			return false;
		}

		return true;
	}

	public static function IsExists($currencyID)
	{
		return is_array(self::GetByID($currencyID));
	}

	public static function GetByID($currencyID, $langID = '')
	{
		$currencyID = (string)self::NormalizeCurrencyID($currencyID);

		if ($currencyID === '')
		{
			return false;
		}

		$currencies = self::GetAll($langID);

		return $currencies[$currencyID] ?? false;
	}

	public static function GetByName($name, $langID = '')
	{
		$name = (string)$name;
		$currencies = self::GetAll($langID);
		foreach($currencies as $currency)
		{
			if (isset($currency['FULL_NAME']) && $currency['FULL_NAME'] === $name)
			{
				return $currency;
			}
		}

		return false;
	}

	public static function GetAll($langID = '')
	{
		if (!Loader::includeModule('currency'))
		{
			return [];
		}

		$langID = (string)$langID;
		if ($langID === '')
		{
			$langID = LANGUAGE_ID;
		}

		$currencies = self::$CURRENCY_BY_LANG[$langID] ?? null;
		if (!$currencies)
		{
			$currencies = [];
			$resCurrency = CCurrency::GetList('sort', 'asc', $langID);
			while ($arCurrency = $resCurrency->Fetch())
			{
				$arCurrency['FULL_NAME'] = (string)$arCurrency['FULL_NAME'];
				if ($arCurrency['FULL_NAME'] === '')
				{
					$arCurrency['FULL_NAME'] = $arCurrency['CURRENCY'];
				}
				$currencies[$arCurrency['CURRENCY']] = $arCurrency;
			}
			self::$CURRENCY_BY_LANG[$langID] = $currencies;
		}

		return $currencies;
	}

	public static function GetList($arOrder, $langID = '')
	{
		if (!Loader::includeModule('currency'))
		{
			return false;
		}

		if (!is_array($arOrder))
		{
			$arOrder = [];
		}

		$arOrderFields = array_keys($arOrder);
		if (!empty($arOrderFields))
		{
			$by = $arOrderFields[0];
			$order = $arOrder[$by];
		}
		else
		{
			$by = 'sort';
			$order = 'asc';
		}

		$langID = (string)$langID;
		if ($langID === '')
		{
			$langID = LANGUAGE_ID;
		}

		return CCurrency::GetList($by, $order, $langID);
	}

	public static function GetCurrencyLocalizations($currencyID)
	{
		if (!Loader::includeModule('currency'))
		{
			return [];
		}

		$currencyID = (string)$currencyID;
		if($currencyID === '')
		{
			return [];
		}

		$result = [];

		$dbResult = CCurrencyLang::GetList('', '', self::NormalizeCurrencyID($currencyID));
		if ($dbResult)
		{
			while ($item = $dbResult->Fetch())
			{
				$result[$item['LID']] = $item;
			}
		}

		return $result;
	}

	private static function GetLanguagesID()
	{
		if (self::$LANGS_ID)
		{
			return self::$LANGS_ID;
		}

		self::$LANGS_ID = [];

		$dbResult = CLangAdmin::GetList();
		while ($arResult = $dbResult->Fetch())
		{
			self::$LANGS_ID[] = $arResult['LID'];
		}

		return self::$LANGS_ID;
	}

	public static function SetCurrencyLocalizations($currencyID, $arItems)
	{
		if (!Loader::includeModule('currency'))
		{
			return false;
		}

		$currencyID = self::NormalizeCurrencyID($currencyID);
		$langsID = self::GetLanguagesID();

		$allowedKeys = array_keys(self::GetCurrencyLocalizationFieldsInfo());
		$processed = 0;
		foreach ($langsID as $langID)
		{
			$item = $arItems[$langID] ?? null;

			if (!is_array($item))
			{
				continue;
			}

			$fields = [];
			foreach ($allowedKeys as $key)
			{
				if (isset($item[$key]))
				{
					$fields[$key] = $item[$key];
				}
			}

			if (empty($fields))
			{
				continue;
			}

			$fields['CURRENCY'] = $currencyID;
			$fields['LID'] = $langID;

			if (is_array(CCurrencyLang::GetByID($currencyID, $langID)))
			{
				CCurrencyLang::Update($currencyID, $langID, $fields);
			}
			else
			{
				if (!isset($fields['DECIMALS']))
				{
					$fields['DECIMALS'] = 2;
				}
				CCurrencyLang::Add($fields);

			}
			$processed++;
		}

		return $processed > 0;
	}

	public static function DeleteCurrencyLocalizations($currencyID, $arLangs)
	{
		if (!Loader::includeModule('currency'))
		{
			return false;
		}

		if (!is_array($arLangs) || empty($arLangs))
		{
			return false;
		}

		$langsID = self::GetLanguagesID();

		$processed = 0;
		foreach ($langsID as $langID)
		{
			if (
				!in_array($langID, $arLangs, true)
				|| !is_array(CCurrencyLang::GetByID($currencyID, $langID))
			)
			{
				continue;
			}

			if (CCurrencyLang::Delete($currencyID, $langID))
			{
				$processed++;
			}
		}

		return $processed > 0;
	}

	public static function GetEncodedCurrencyName($currencyID, $langID = '')
	{
		return htmlspecialcharsbx(self::GetCurrencyName($currencyID, $langID));
	}

	public static function GetCurrencyListEncoded(): array
	{
		static $currencies;

		if (!is_array($currencies))
		{
			$currencies = [];
			$allCurrencies = self::GetAll();
			foreach ($allCurrencies as $currencyId => $currency)
			{
				$currencies[htmlspecialcharsbx($currencyId)] = htmlspecialcharsbx($currency['FULL_NAME']);
			}
		}

		return $currencies;
	}

	public static function GetCurrencyName($currencyID, $langID = '')
	{
		$currencyID = (string)$currencyID;
		if ($currencyID === '')
		{
			return '';
		}

		$ID = self::NormalizeCurrencyID($currencyID);
		$currencies = self::GetAll($langID);

		return $currencies[$ID]['FULL_NAME'] ?? $currencyID;
	}

	public static function GetCurrencyFormatString($currencyID, $langID = '')
	{
		$currencyID = (string)$currencyID;
		$langID = (string)$langID;
		if ($langID === '')
		{
			$langID = LANGUAGE_ID;
		}

		$formatStr = '';
		if (isset(self::$CURRENCY_FORMAT_BY_LANG[$langID][$currencyID]))
		{
			$formatStr = self::$CURRENCY_FORMAT_BY_LANG[$langID][$currencyID];
		}
		elseif (Loader::includeModule('currency'))
		{
			$formatInfo = CCurrencyLang::GetCurrencyFormat($currencyID, $langID);
			$formatStr = $formatInfo['FORMAT_STRING'] ?? '#';

			if ($formatStr !== '')
			{
				$formatStr = strip_tags($formatStr);
			}

			if ($formatStr === '')
			{
				$formatStr = '#';
			}

			self::$CURRENCY_FORMAT_BY_LANG[$langID] ??= [];
			self::$CURRENCY_FORMAT_BY_LANG[$langID][$currencyID] = $formatStr;
		}

		return $formatStr;
	}

	public static function GetCurrencyFormatParams($currencyID)
	{
		if (!Loader::includeModule('currency'))
		{
			return [];
		}

		return CCurrencyLang::GetFormatDescription($currencyID);
	}

	public static function GetCurrencyText($currencyID)
	{
		$currencyText = '?';

		if (Loader::includeModule('currency'))
		{
			$currencyFormat = (string)self::GetCurrencyFormatString($currencyID);
			if ($currencyFormat !== '')
			{
				$str = CCurrencyLang::applyTemplate('', $currencyFormat);
				if (is_string($str))
				{
					$str = trim($str);
					if ($str !== '')
					{
						$currencyText = $str;
					}
				}
			}
		}

		return $currencyText;
	}

	public static function MoneyToString($sum, $currencyID, $formatStr = '')
	{
		if (!Loader::includeModule('currency'))
		{
			return number_format($sum, 2, '.', '');
		}

		$currencyID = (string)$currencyID;

		CCurrencyLang::enableUseHideZero();
		$result = strip_tags(CCurrencyLang::CurrencyFormat($sum, $currencyID, $formatStr !== '#'));
		CCurrencyLang::disableUseHideZero();

		return $result;
	}

	public static function ConvertMoney($sum, $srcCurrencyID, $dstCurrencyID, $srcExchRate = -1)
	{
		$sum = (float)$sum;

		if (!Loader::includeModule('currency'))
		{
			return $sum;
		}

		$srcCurrencyID = self::NormalizeCurrencyID($srcCurrencyID);
		$dstCurrencyID = self::NormalizeCurrencyID($dstCurrencyID);
		$srcExchRate = (float)$srcExchRate;

		if ($sum === 0.0 || $srcCurrencyID === $dstCurrencyID)
		{
			return $sum;
		}

		if ($srcExchRate <= 0)
		{
			// Use default exchenge rate
			$result = self::$currencyRatesClassName::ConvertCurrency($sum, $srcCurrencyID, $dstCurrencyID);
		}
		else
		{
			// Convert source currency to base and convert base currency to destination
			$result = self::$currencyRatesClassName::ConvertCurrency(
				$sum * $srcExchRate,
				self::GetBaseCurrencyID(),
				$dstCurrencyID
			);
		}

		$formatInfo = CCurrencyLang::GetFormatDescription($dstCurrencyID);

		return round($result, $formatInfo['DECIMALS']);
	}

	public static function GetCurrencyDecimals($currencyID)
	{
		$formatInfo = CCurrencyLang::GetFormatDescription($currencyID);

		return $formatInfo['DECIMALS'];
	}

	public static function GetExchangeRate($currencyID)
	{
		if (!Loader::includeModule('currency'))
		{
			return 1;
		}

		$currencyID = (string)$currencyID;

		$rates = new self::$currencyRatesClassName();
		$rs = $rates->_get_last_rates(date('Y-m-d'), $currencyID);
		if (!$rs)
		{
			return 1.0;
		}

		$exchRate = (float)$rs['RATE'];
		$cnt = (int)$rs['RATE_CNT'];

		if ($exchRate <= 0)
		{
			$exchRate = (float)$rs['AMOUNT'];
			$cnt = (int)$rs['AMOUNT_CNT'];
		}

		return ($cnt !== 1 ? ($exchRate / $cnt) : $exchRate);
	}

	private static function ClearCache(): void
	{
		self::$CURRENCY_BY_LANG = [];
	}

	public static function GetLastError()
	{
		return self::$LAST_ERROR;
	}

	private static function CheckFields($action, &$arFields, $ID)
	{
		if (isset($arFields['AMOUNT_CNT']))
		{
			$arFields['AMOUNT_CNT'] = intval($arFields['AMOUNT_CNT']);
		}

		if (isset($arFields['AMOUNT']))
		{
			$arFields['AMOUNT'] = doubleval($arFields['AMOUNT']);
		}

//		if(isset($arFields['SORT']))
//		{
//			$SORT = intval($arFields['SORT']);
//			$arFields['SORT'] = ($SORT > 255 || $SORT < 0 ? 0 : $SORT);
//		}

		return true;
	}

	public static function Add($arFields)
	{
		if (!Loader::includeModule('currency'))
		{
			self::$LAST_ERROR = Loc::getMessage('CRM_CURRERCY_MODULE_IS_NOT_INSTALLED');

			return false;
		}

		global $APPLICATION;

		$ID = $arFields['CURRENCY'] ?? '';
		if (!self::CheckFields('ADD', $arFields, $ID))
		{
			return false;
		}

		$ID = CCurrency::Add($arFields);
		if (!$ID)
		{
			$ex = $APPLICATION->GetException();
			if ($ex)
			{
				self::$LAST_ERROR = $ex->GetString();
			}

			return false;
		}

		self::ClearCache();

		return $ID;
	}

	public static function Update($ID, $arFields)
	{
		if (!Loader::includeModule('currency'))
		{
			self::$LAST_ERROR = Loc::getMessage('CRM_CURRERCY_MODULE_IS_NOT_INSTALLED');

			return false;
		}

		global $APPLICATION;

		$arFields['CURRENCY'] = $ID;

		if (!self::CheckFields('UPDATE', $arFields, $ID))
		{
			return false;
		}

		if (!CCurrency::Update($ID, $arFields))
		{
			$ex = $APPLICATION->GetException();
			if ($ex)
			{
				self::$LAST_ERROR = $ex->GetString();
			}

			return false;
		}

		self::ClearCache();

		return true;
	}

	public static function Delete($ID)
	{
		if (!Loader::includeModule('currency'))
		{
			self::$LAST_ERROR = Loc::getMessage('CRM_CURRERCY_MODULE_IS_NOT_INSTALLED');

			return false;
		}

		global $APPLICATION;

		$ID = (string)$ID;
		if (mb_strlen($ID) !== 3)
		{
			return false;
		}

		if ($ID === self::GetBaseCurrencyID())
		{
			self::$LAST_ERROR = Loc::getMessage('CRM_CURRERCY_ERR_DELETION_OF_BASE_CURRENCY');

			return false;
		}

		if ($ID === self::GetAccountCurrencyID())
		{
			self::$LAST_ERROR = Loc::getMessage('CRM_CURRERCY_ERR_DELETION_OF_ACCOUNTING_CURRENCY');

			return false;
		}

		if (!CCurrency::Delete($ID))
		{
			$ex = $APPLICATION->GetException();
			if ($ex)
			{
				self::$LAST_ERROR = $ex->GetString();
			}

			return false;
		}

		self::ClearCache();

		return true;
	}

	public static function CheckCreatePermission()
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();

		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckUpdatePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();

		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckDeletePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();

		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckReadPermission($ID = 0)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();

		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');
	}

	public static function getInvoiceDefault()
	{
		return COption::GetOptionString("sale", "default_currency", "RUB");
	}

	public static function setInvoiceDefault($currencyId)
	{
		return COption::SetOptionString("sale", "default_currency", $currencyId);
	}
}
