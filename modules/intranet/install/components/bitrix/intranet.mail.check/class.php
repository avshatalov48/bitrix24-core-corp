<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CIntranetMailCheckComponent extends CBitrixComponent
{

	public function executeComponent()
	{
		global $APPLICATION;

		$this->arResult['IS_TIME_TO_MAIL_CHECK'] = null;

		$settedUp = null;

		if (defined('SKIP_MAIL_CHECK') && SKIP_MAIL_CHECK == true)
		{
			$settedUp = false;
		}

		if (defined('ADMIN_SECTION') && ADMIN_SECTION == true)
		{
			$settedUp = false;
		}

		if (!\Bitrix\Main\Loader::includeModule('mail'))
		{
			$settedUp = false;
		}

		if ($settedUp !== false)
		{
			$isMobileInstalled = COption::GetOptionString('main', 'wizard_mobile_installed', 'N', SITE_ID) == 'Y';
			$isMobileVersion = strpos($APPLICATION->GetCurPage(), SITE_DIR . 'm/') === 0;
			if ($isMobileInstalled && $isMobileVersion)
			{
				$settedUp = false;
			}
		}

		if ($settedUp !== false)
		{
			if (!is_callable(['CIntranetUtils', 'IsExternalMailAvailable']) || !CIntranetUtils::IsExternalMailAvailable())
			{
				$settedUp = false;
			}
		}

		if ($settedUp !== false)
		{
			$mailboxesSyncManager = new \Bitrix\Mail\Helper\Mailbox\MailboxSyncManager(\Bitrix\Main\Engine\CurrentUser::get()->getId());
			$mailboxesSuccessSynced = $mailboxesSyncManager->getSuccessSyncedMailboxes();
			$checkInterval = $mailboxesSyncManager->getMailCheckInterval();
			if (!empty($mailboxesSuccessSynced))
			{
				$settedUp = true;
				$hasSuccessSync = true;
				$failedToSyncMailboxId = $mailboxesSyncManager->getFirstFailedToSyncMailboxId();
				$isTimeToMailCheck = count($mailboxesSyncManager->getNeedToBeSyncedMailboxes()) > 0;
				$nextTimeToCheckMailboxes = [];
				foreach ($mailboxesSuccessSynced as $mailboxId => $lastMailCheckData)
				{
					$nextTimeToCheckMailboxes[] = $mailboxesSyncManager->getNextTimeToSync($lastMailCheckData);
				}
				$nextTimeToCheck = !empty($nextTimeToCheckMailboxes) && min($nextTimeToCheckMailboxes) > 0 ? min($nextTimeToCheckMailboxes) : 0;
			}
		}

		if ($settedUp !== false)
		{
			$this->arResult['NEXT_TIME_TO_CHECK'] = $nextTimeToCheck;
			$this->arResult['CHECK_INTERVAL'] = $checkInterval;

			$this->arResult['HAS_SUCCESS_SYNC'] = $hasSuccessSync;
			$this->arResult['FAILED_SYNC_MAILBOX_ID'] = $failedToSyncMailboxId;
			$this->arResult['IS_TIME_TO_MAIL_CHECK'] = $isTimeToMailCheck;
		}

		$this->arResult['SETTED_UP'] = $settedUp;

		$this->includeComponentTemplate();
	}

}
