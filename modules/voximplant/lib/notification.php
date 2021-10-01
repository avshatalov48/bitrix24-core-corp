<?php

namespace Bitrix\Voximplant;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Data\LocalStorage\SessionLocalStorage;
use Bitrix\Main\Data\LocalStorage\SessionLocalStorageManager;
use Bitrix\Main\Loader;

class Notification
{
	public static function isBalanceTooLow()
	{
		$account = new \CVoxImplantAccount();

		if (\CVoxImplantAccount::getPayedFlag() !== 'Y')
		{
			return false;
		}

		$accountLang = $account->GetAccountLang(false);
		if(in_array($accountLang, ['ua', 'kz', 'by']))
		{
			return false;
		}

		$balance = $account->getAccountBalance(false);
		$balanceThreshold = $account->getBalanceThreshold();

		$hasCallsInLastFiveDays = false;
		$lastPaidCallTimestamp = \CVoxImplantHistory::getLastPaidCallTimestamp();
		if($lastPaidCallTimestamp > 0)
		{
			$interval = time() - $lastPaidCallTimestamp;

			if($interval < 432000) // 5 days
			{
				$hasCallsInLastFiveDays = true;
			}
		}

		return (\CVoxImplantPhone::getRentedNumbersCount() > 0 && $hasCallsInLastFiveDays && $balanceThreshold > 0 && $balance < $balanceThreshold);
	}

	/**
	 * @return string|false
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function shouldShowWarningForFreePortals($ignoreClosed = false)
	{
		if (Limits::canManageTelephony())
		{
			return false;
		}

		if (!$ignoreClosed)
		{
			$ls = \Bitrix\Main\Application::getInstance()->getLocalSession('telephony_notification_free_plan');
			if ($ls->get('closed') === 'Y')
			{
				return false;
			}
		}

		if (\CVoxImplantPhone::getRentedNumbersCount() > 0)
		{
			return 'rent';
		}
		if (\CVoxImplantSip::hasConnection())
		{
			return 'sip';
		}
		$account = new \CVoxImplantAccount();
		$balance = $account->getAccountBalance(false);
		return $balance > 0 ? 'rent' : false;
	}
}