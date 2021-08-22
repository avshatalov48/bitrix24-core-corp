<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Security\Mfa\Otp;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

class CSecurityUserOtpConnected extends CBitrixComponent
{
/*	public function onPrepareComponentParams($arParams)
	{

	}*/
	protected function listKeysSignedParameters()
	{
		return array(
			'USER_ID'
		);
	}

	public function executeComponent()
	{
		global $USER;

		//otp
		if (\Bitrix\Main\Loader::includeModule("security") && Bitrix\Security\Mfa\Otp::isOtpEnabled())
		{
			$this->arResult["OTP"]["IS_ENABLED"] = "Y";
			$this->arResult["OTP"]["IS_MANDATORY"] = !\CSecurityUser::IsUserSkipMandatoryRights($this->arParams["USER_ID"]);
			$this->arResult["OTP"]["USER_HAS_EDIT_RIGHTS"] = $USER->CanDoOperation('security_edit_user_otp');

			if (
				Loader::includeModule('bitrix24')
				&& $this->arParams["USER_ID"] === $USER->GetID()
				&& \Bitrix\Bitrix24\Integrator::isIntegrator($this->arParams["USER_ID"])
			)
			{
				$this->arResult["OTP"]["IS_MANDATORY"] = true;
				$this->arResult["OTP"]["USER_HAS_EDIT_RIGHTS"] = false;
			}

			$this->arResult["OTP"]["IS_ACTIVE"] = \CSecurityUser::IsUserOtpActive($this->arParams["USER_ID"]);
			$this->arResult["OTP"]["IS_EXIST"] = \CSecurityUser::IsUserOtpExist($this->arParams["USER_ID"]);
			$this->arResult["OTP"]["ARE_RECOVERY_CODES_ENABLED"] = Bitrix\Security\Mfa\Otp::isRecoveryCodesEnabled();

			$dateDeactivate = \CSecurityUser::GetDeactivateUntil($this->arParams["USER_ID"]);
			$this->arResult["OTP"]["NUM_LEFT_DAYS"] = ($dateDeactivate) ? FormatDate("ddiff", time()-60*60*24,  MakeTimeStamp($dateDeactivate) - 1) : "";
		}
		else
		{
			$this->arResult["OTP"]["IS_ENABLED"] = "N";
		}

		$this->IncludeComponentTemplate();

	}
}
