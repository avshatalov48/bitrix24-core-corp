<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

class CIntranetOtpInfoComponent extends CBitrixComponent
{
	public function executeComponent(): void
	{
		global $USER;
		
		if (
			!Loader::includeModule('security')
			|| !$USER->IsAuthorized()
			|| !\Bitrix\Security\Mfa\Otp::isOtpEnabled()
			|| !\Bitrix\Security\Mfa\Otp::isMandatoryUsing()
		)
		{
			return;
		}

		foreach (GetModuleEvents('intranet', 'OnIntranetPopupShow', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent) === false)
			{
				return;
			}
		}

		if(defined('BX_COMP_MANAGED_CACHE'))
		{
			$ttl = 2592000;
		}
		else
		{
			$ttl = 600;
		}

		$cache_id = 'user_otp_' . $USER->GetID();
		$cache_dir = '/otp/user_id/' . substr(md5($USER->GetID()), -2) . '/' . $USER->GetID() . '/';

		$obCache = new \CPHPCache;

		if($obCache->InitCache($ttl, $cache_id, $cache_dir))
		{
			$arUserOtp = $obCache->GetVars();
		}
		else
		{
			$arUserOtp = array(
				'ACTIVE' => \CSecurityUser::IsUserOtpActive($USER->GetID())
			);

			if($obCache->StartDataCache())
			{
				$obCache->EndDataCache($arUserOtp);
			}
		}

		$this->arParams['PATH_TO_PROFILE_SECURITY'] = trim($this->arParams['PATH_TO_PROFILE_SECURITY']);

		if($this->arParams['PATH_TO_PROFILE_SECURITY'] == '')
		{
			$this->arParams['PATH_TO_PROFILE_SECURITY'] = SITE_DIR . 'company/personal/user/#user_id#/security/';
		}

		$this->arResult['PATH_TO_PROFILE_SECURITY'] = \CComponentEngine::MakePathFromTemplate(
			$this->arParams['PATH_TO_PROFILE_SECURITY'],
			['user_id' => $USER->GetID()]
		);

		$localStorage = \Bitrix\Main\Application::getInstance()->getLocalSession('otpMandatoryInfo');

		if (
			!$arUserOtp['ACTIVE']
			&& !isset($localStorage['otpMandatoryInfo'])
		)
		{
			//for all mandatory
			$isUserSkipMandatoryRights = \CSecurityUser::IsUserSkipMandatoryRights($USER->GetID());
			$dateDeactivate = \CSecurityUser::GetDeactivateUntil($USER->GetID());

			if (!$isUserSkipMandatoryRights && $dateDeactivate)
			{
				$this->arResult['POPUP_NAME'] = 'otp_mandatory_info';
				$localStorage->set('otpMandatoryInfo', 'Y');
				$this->arResult['USER']['OTP_DAYS_LEFT'] = (
					$dateDeactivate
					? FormatDate('ddiff', time() - 60*60*24,  MakeTimeStamp($dateDeactivate))
					: ''
				);

				$this->includeComponentTemplate();
			}
		}
	}
}
