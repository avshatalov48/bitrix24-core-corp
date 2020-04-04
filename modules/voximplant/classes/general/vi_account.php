<?
class CVoxImplantAccount
{
	const ACCOUNT_PAYED = "account_payed";
	const ACCOUNT_LANG = "account_lang";
	const ACCOUNT_BETA_ACCESS = "account_beta_access";
	const ACCOUNT_CURRENCY = "account_currency";
	const ACCOUNT_BALANCE = "account_balance";
	const ACCOUNT_NAME = "account_name";

	private $account_name = null;
	private $account_balance = 0;
	private $account_currency = null;
	private $account_beta_access = false;
	private $account_lang = '';
	private $error = null;

	function __construct()
	{
		$this->error = new CVoxImplantError(null, '', '');
	}

	public function UpdateAccountInfo($accountInfo = null)
	{
		if(\Bitrix\Voximplant\Limits::isRestOnly())
		{
			return false;
		}

		if(is_null($accountInfo))
		{
			$ViHttp = new CVoxImplantHttp();
			$accountInfo = $ViHttp->GetAccountInfo();

			if ($ViHttp->GetError()->error)
			{
				$this->error = new CVoxImplantError(__METHOD__, $ViHttp->GetError()->code, $ViHttp->GetError()->msg);
				return false;
			}
		}

		$this->SetAccountName($accountInfo->account_name);
		$this->SetAccountBalance($accountInfo->account_balance);
		$this->SetAccountCurrency($accountInfo->account_currency);
		$this->SetAccountBetaAccess($accountInfo->account_beta_access);
		$this->SetAccountLang($accountInfo->account_lang);

		if($accountInfo->account_payed !== self::GetPayedFlag())
			$this->SetPayedFlag($accountInfo->account_payed);

		if($accountInfo->sip_paid !== CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP))
			CVoxImplantConfig::SetModeStatus(CVoxImplantConfig::MODE_SIP, ($accountInfo->sip_paid === 'Y'));

		return true;
	}

	public function ClearAccountInfo()
	{
		$this->SetAccountName(null);
		$this->SetAccountBalance(0);
		$this->SetAccountCurrency(null);
	}

	public function SetAccountName($name)
	{
		if ($this->account_name == $name)
			return true;

		$this->account_name = $name;

		COption::SetOptionString("voximplant", self::ACCOUNT_NAME, $this->account_name);

		return true;
	}

	public function GetAccountName()
	{
		if (strlen($this->account_name)<=0)
		{
			$this->account_name = COption::GetOptionString("voximplant", self::ACCOUNT_NAME);
			if (strlen($this->account_name)<=0)
			{
				if (!$this->UpdateAccountInfo())
				{
					return false;
				}
			}
		}
		return str_replace('voximplant.com', 'bitrixphone.com', $this->account_name);
	}

	public function GetCallServer()
	{
		$accountName = $this->GetAccountName();

		return $accountName ? 'ip.'.$accountName : false;
	}

	public function SetAccountBalance($balance)
	{
		$this->account_balance = floatval($balance);

		COption::SetOptionString("voximplant", self::ACCOUNT_BALANCE, $this->account_balance);

		return true;
	}

	public function GetAccountBalance($liveBalance = false)
	{
		if ($liveBalance)
			$this->UpdateAccountInfo();

		if (floatval($this->account_balance)<=0)
		{
			$this->account_balance = COption::GetOptionString("voximplant", self::ACCOUNT_BALANCE, 0);
			if (floatval($this->account_balance)<=0)
			{
				if (!$liveBalance && !$this->UpdateAccountInfo())
				{
					return false;
				}
			}
		}
		return floatval($this->account_balance);
	}

	public function GetBalanceFormatted()
	{
		$balance = $this->GetAccountBalance();
		$currency = $this->GetAccountCurrency();
		if($currency == 'RUR')
		{
			$currency = 'RUB';
		}

		if(!$currency)
			return '';

		if(\Bitrix\Main\Loader::includeModule('currency'))
		{
			return CCurrencyLang::CurrencyFormat($balance, $currency);
		}
		else
		{
			return $balance . ' ' . $currency;
		}
	}

	public function GetBalanceThreshold()
	{
		$accountLang = $this->GetAccountLang(false);
		if($accountLang == '')
			return false;

		if($accountLang == 'ru')
		{
			return 300;
		}
		else
		{
			return 6;
		}
	}

	public function SetAccountCurrency($currency)
	{
		if ($this->account_currency == $currency)
			return true;

		$this->account_currency = $currency;

		COption::SetOptionString("voximplant", self::ACCOUNT_CURRENCY, $this->account_currency);

		return true;
	}

	public function GetAccountCurrency()
	{
		if (strlen($this->account_currency)<=0)
		{
			$this->account_currency = COption::GetOptionString("voximplant", self::ACCOUNT_CURRENCY);
			if (strlen($this->account_currency)<=0)
			{
				if (!$this->UpdateAccountInfo())
				{
					return false;
				}
			}
		}
		return $this->account_currency;
	}

	public function SetAccountBetaAccess($active = false)
	{
		$active = $active? true: false;

		$this->account_beta_access = $active;

		COption::SetOptionString("voximplant", self::ACCOUNT_BETA_ACCESS, $this->account_beta_access);

		return true;
	}

	public function GetAccountBetaAccess()
	{
		$value = COption::GetOptionString("voximplant", self::ACCOUNT_BETA_ACCESS, $this->account_beta_access);
		return $value? true: false;
	}

	public function SetAccountLang($lang)
	{
		if ($this->account_lang == $lang)
			return true;

		$this->account_lang = $lang;
		COption::SetOptionString("voximplant", self::ACCOUNT_LANG, $this->account_lang);

		return true;
	}

	public function GetAccountLang($allowUpdate = true)
	{
		if (strlen($this->account_lang)<=0)
		{
			$this->account_lang = COption::GetOptionString("voximplant", self::ACCOUNT_LANG);
			if (strlen($this->account_lang)<=0)
			{
				if(!$allowUpdate)
					return false;

				if (!$this->UpdateAccountInfo())
					return false;
			}
		}
		return $this->account_lang;
	}

	public static function SetPayedFlag($flag)
	{
		COption::SetOptionString("voximplant", self::ACCOUNT_PAYED, $flag == 'Y'? 'Y':'N');

		return true;
	}

	public static function GetPayedFlag()
	{
		return COption::GetOptionString("voximplant", self::ACCOUNT_PAYED);
	}

	public static function SynchronizeInfo()
	{
		return false;
	}

	public static function IsPro()
	{
		if (!CModule::IncludeModule('bitrix24'))
			return true;

		if (CBitrix24::IsLicensePaid())
			return true;

		if (CBitrix24::IsNfrLicense())
			return true;

		if (CBitrix24::IsDemoLicense())
			return true;

		return false;
	}

	public static function IsDemo()
	{
		if (!CModule::IncludeModule('bitrix24'))
			return false;

		if (CBitrix24::IsDemoLicense())
			return true;

		return false;
	}

	public static function GetRecordLimit($mode = false)
	{
		$sipConnectorActive = CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP);

		$recordLimit = COption::GetOptionInt("voximplant", "record_limit");
		if ($recordLimit > 0 && !CVoxImplantAccount::IsPro())
		{
			if ($mode == CVoxImplantConfig::MODE_SIP && $sipConnectorActive)
			{
				$recordLimitEnable = false;
			}
			else
			{
				$recordLimitEnable = true;
				$recordLimitRemaining = $recordLimit-CGlobalCounter::GetValue('vi_records', CGlobalCounter::ALL_SITES);

				$result = Array(
					'ENABLE' => $recordLimitEnable,
					'LIMIT' => $recordLimit,
					'REMAINING' => $recordLimitRemaining
				);
			}
		}
		else
		{
			$recordLimitEnable = false;
		}

		if (!$recordLimitEnable)
		{
			$result =  Array(
				'ENABLE' => $recordLimitEnable,
				'DEMO' => CVoxImplantAccount::IsDemo() && !$sipConnectorActive
			);
		}

		return $result;
	}

	public function GetError()
	{
		return $this->error;
	}
}