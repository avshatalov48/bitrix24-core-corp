<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\Entity\UserOtp;
use Bitrix\Intranet\Internal\Integration\Security\Otp;

class CIntranetOtpInfoComponent extends CBitrixComponent
{
	private \Bitrix\Intranet\CurrentUser $currentUser;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->currentUser = Bitrix\Intranet\CurrentUser::get();
	}

	public function executeComponent(): void
	{
		if (!$this->currentUser->isAuthorized())
		{
			return;
		}

		$otp = new Otp();

		if (
			!$otp->isMandatory()
			|| $this->isSavedLocalStorageInfo()
			|| !$this->checkPopupShowEvents()
		)
		{
			return;
		}

		$user = new \Bitrix\Intranet\Entity\User(
			id: $this->currentUser->getId(),
		);

		if (
			$otp->isEnabledForUser($user)
			|| !$otp->isRequiredForUser($user)
		)
		{
			return;
		}

		$this->arResult['PATH_TO_PROFILE_SECURITY'] = $this->getPathToProfileSecurity();
		$this->arResult['POPUP_NAME'] = 'otp_mandatory_info';
		$this->arResult['USER']['OTP_DAYS_LEFT'] = $this->getFormattedOtpDaysLeft($otp->getUserOtp($user));
		$this->saveLocalStorageInfo();

		$this->includeComponentTemplate();
	}

	private function checkPopupShowEvents(): bool
	{
		foreach (GetModuleEvents('intranet', 'OnIntranetPopupShow', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent) === false)
			{
				return false;
			}
		}

		return true;
	}

	private function isSavedLocalStorageInfo(): bool
	{
		$localStorageInfo = \Bitrix\Main\Application::getInstance()
			->getLocalSession('otpMandatoryInfo')
			->get('otpMandatoryInfo');

		return isset($localStorageInfo);
	}

	private function saveLocalStorageInfo(): void
	{
		\Bitrix\Main\Application::getInstance()
			->getLocalSession('otpMandatoryInfo')
			->set('otpMandatoryInfo', 'Y');
	}

	private function getPathToProfileSecurity(): string
	{
		$this->arParams['PATH_TO_PROFILE_SECURITY'] = trim($this->arParams['PATH_TO_PROFILE_SECURITY'] ?? '');

		if(empty($this->arParams['PATH_TO_PROFILE_SECURITY']))
		{
			$isExtranet = (
				\Bitrix\Main\ModuleManager::isModuleInstalled('extranet')
				&& \COption::getOptionString('extranet', 'extranet_site') === SITE_ID
			);

			$path = $isExtranet ? SITE_DIR . 'contacts/personal' : SITE_DIR . 'company/personal';
			$this->arParams['PATH_TO_PROFILE_SECURITY'] = $path . '/user/#user_id#/security/';
		}

		return \CComponentEngine::MakePathFromTemplate(
			$this->arParams['PATH_TO_PROFILE_SECURITY'],
			['user_id' => $this->currentUser->getId()]
		);
	}

	private function getFormattedOtpDaysLeft(UserOtp $userOtp): string
	{
		return FormatDate('ddiff', time() - 60 * 60 * 24,  MakeTimeStamp($userOtp->dateDeactivate));
	}
}
