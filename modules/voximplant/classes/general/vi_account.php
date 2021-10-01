<?

use Bitrix\Voximplant\Limits;

class CVoxImplantAccount
{
	const ACCOUNT_PAYED = "account_payed";
	const ACCOUNT_LANG = "account_lang";
	const ACCOUNT_BETA_ACCESS = "account_beta_access";
	const ACCOUNT_CURRENCY = "account_currency";
	const ACCOUNT_BALANCE = "account_balance";
	const ACCOUNT_NAME = "account_name";
	const LAST_TOP_UP_TIMESTAMP = "last_top_up_timestamp";

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

		$sipPaid = $accountInfo->sip_paid === 'Y';
		if($sipPaid !== CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP))
			CVoxImplantConfig::SetModeStatus(CVoxImplantConfig::MODE_SIP, $sipPaid);

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
		if ($this->account_name == '')
		{
			$this->account_name = COption::GetOptionString("voximplant", self::ACCOUNT_NAME);
			if ($this->account_name == '')
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
		$isTopUp = ($balance > $this->GetAccountBalance());
		if ($this->GetAccountBalance() == $balance)
		{
			return true;
		}
		$this->account_balance = floatval($balance);

		COption::SetOptionString("voximplant", self::ACCOUNT_BALANCE, $this->account_balance);
		if ($isTopUp)
		{
			COption::SetOptionString("voximplant", self::LAST_TOP_UP_TIMESTAMP, time());
		}

		\Bitrix\Voximplant\Integration\Pull::sendBalanceUpdate($this->account_balance, $this->GetAccountCurrency(false));

		return true;
	}

	public function GetLastTopUpTimestamp()
	{
		return COption::GetOptionInt("voximplant", self::LAST_TOP_UP_TIMESTAMP, 0);
	}

	public function GetAccountBalance($liveBalance = false)
	{
		$updateResult = $liveBalance ? $this->UpdateAccountInfo() : false;

		if($liveBalance && !$updateResult)
		{
			return false;
		}

		if ($this->account_balance <= 0)
		{
			$this->account_balance = (float)COption::GetOptionString("voximplant", self::ACCOUNT_BALANCE, 0);
		}

		return (float)$this->account_balance;
	}

	public function GetBalanceFormatted()
	{
		$balance = $this->GetAccountBalance();
		$currency = $this->GetAccountCurrency();
		if($currency === 'RUR')
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

		if($accountLang === 'ru')
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

	public function GetAccountCurrency($allowUpdate = true)
	{
		if ($this->account_currency == '')
		{
			$this->account_currency = COption::GetOptionString("voximplant", self::ACCOUNT_CURRENCY);
			if ($this->account_currency == '' && $allowUpdate)
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
		if ($this->account_lang == '')
		{
			$this->account_lang = COption::GetOptionString("voximplant", self::ACCOUNT_LANG);
			if ($this->account_lang == '')
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
		return (
			\Bitrix\Main\Loader::includeModule('bitrix24')
			&& CBitrix24::IsDemoLicense()
		);
	}

	public static function GetRecordLimit($mode = false)
	{
		$recordLimit = Limits::getRecordLimit($mode);
		if ($recordLimit > 0)
		{
			$recordLimitRemaining = Limits::getRemainingRecordsCount();

			$result = Array(
				'ENABLE' => true,
				'LIMIT' => $recordLimit,
				'REMAINING' => $recordLimitRemaining,
				'USED' => $recordLimit - $recordLimitRemaining,
			);
		}
		else
		{
			$result =  Array(
				'ENABLE' => false,
				'DEMO' => CVoxImplantAccount::IsDemo()
			);
		}

		return $result;
	}

	public function GetError()
	{
		return $this->error;
	}
}