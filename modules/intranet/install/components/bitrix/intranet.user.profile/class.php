<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet\Component\UserProfile;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;

Loc::loadMessages(__FILE__);

Loader::includeModule("intranet");

class CIntranetUserProfileComponent extends UserProfile
{
	/** @var \Bitrix\Main\UserField\Dispatcher|null */
	private $userFieldDispatcher = null;

	private function checkRequiredParams()
	{
		if (intval($this->arParams["ID"]) <= 0)
		{
			$this->errorCollection->setError(new Error(Loc::getMessage("INTRANET_USER_PROFILE_NO_USER_ERROR")));
			return false;
		}

		return true;
	}

	public function executeComponent()
	{
		global $APPLICATION, $USER;

		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
			return;
		}

		$isAdminRights = (
			Loader::includeModule("bitrix24") && \CBitrix24::IsPortalAdmin(\Bitrix\Main\Engine\CurrentUser::get()->getId())
			|| \Bitrix\Main\Engine\CurrentUser::get()->isAdmin()
		)
			? true : false;

		$this->arResult["IS_CURRENT_USER_ADMIN"] = $isAdminRights;

		$this->userFieldDispatcher = \Bitrix\Main\UserField\Dispatcher::instance();

		$this->arResult["EnablePersonalConfigurationUpdate"] = true;
		$this->arResult["EnableCommonConfigurationUpdate"] = $isAdminRights;
		$this->arResult["EnableSettingsForAll"] = \Bitrix\Main\Engine\CurrentUser::get()->canDoOperation('edit_other_settings');

		$this->arResult["Permissions"] = $this->getPermissions();

		$this->arResult["UserFieldEntityId"] = "USER";
		$this->arResult["UserFieldPrefix"] = "USR";

		$this->arResult["AllowAllUserProfileFields"] = (
			\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			|| (
				isset($this->arParams["ALLOWALL_USER_PROFILE_FIELDS"])
				&& $this->arParams["ALLOWALL_USER_PROFILE_FIELDS"] == 'Y'
			)
		);

		$this->arResult["EnableUserFieldCreation"] = $this->arResult["EnableCommonConfigurationUpdate"];
		$this->arResult["UserFieldsAvailable"] = $this->getAvailableFields();

		$this->arResult["UserFieldCreateSignature"] = $this->arResult["EnableCommonConfigurationUpdate"]
			? $this->userFieldDispatcher->getCreateSignature(array("ENTITY_ID" => $this->arResult["UserFieldEntityId"]))
			: '';
		$this->arResult["EnableUserFieldMandatoryControl"] = false;

		$this->init();

		$this->arResult["isCloud"] = Loader::includeModule("bitrix24");
		if ($this->arResult["isCloud"])
		{
			$licensePrefix = \CBitrix24::getLicensePrefix();
			$this->arResult["isRusCloud"] = in_array($licensePrefix, array("ru", "by", "kz", "ua"));
		}

		$this->arResult["Urls"] = $this->getUrls();
		$this->arResult["User"] = $this->getUserData();
		$this->arResult["CurrentUser"] = [
			'STATUS' => $this->getCurrentUserStatus()
		];

		if ($this->arResult["User"]["STATUS"] === "email")
		{
			$this->arResult["FormFields"] = $this->getFormInstance()->getFieldInfoForEmailUser();
		}
		else
		{
			$this->arResult["FormFields"] = $this->getFormInstance()->getFieldInfo($this->arResult["User"], [], $this->arParams);

			if (!$this->arResult["isCloud"])
			{
				$this->getFormInstance()->prepareSettingsFields($this->arResult, $this->arParams);
			}
		}

		$this->arResult["FormConfig"] = $this->getFormInstance()->getConfig($this->arResult["SettingsFieldsForConfig"]);
		$this->arResult["FormData"] = $this->getFormInstance()->getData($this->arResult);

		$this->arResult["Gratitudes"] = $this->getGratsInstance()->getStub();
		$this->arResult["ProfileBlogPost"] = $this->getProfilePostInstance()->getStub();
		$this->arResult["Tags"] = $this->getTagsInstance()->getStub();
		$this->arResult["FormId"] = "intranet-user-profile";
		$this->arResult["IsOwnProfile"] = $USER->GetID() === $this->arParams["ID"];
		$this->arResult["StressLevel"] = $this->getStressLevelInstance()->getStub();

		$this->checkNumAdminRestrictions();

		if (Loader::includeModule("security") && \Bitrix\Security\Mfa\Otp::isOtpEnabled())
		{
			$this->arResult["OTP_IS_ENABLED"] = "Y";
		}
		else
		{
			$this->arResult["OTP_IS_ENABLED"] = "N";
		}

		$this->arResult["isExtranetSite"] = (Loader::includeModule("extranet") && \CExtranet::isExtranetSite());

		$this->arResult["IS_CURRENT_USER_INTEGRATOR"] = false;
		if ($this->arResult["isCloud"])
		{
			$this->arResult["IS_CURRENT_USER_INTEGRATOR"] = \Bitrix\Bitrix24\Integrator::isIntegrator($USER->GetID());
		}

		$this->processShowYear();

		$this->arResult["DISK_INFO"] = $this->getDiskInfo();

		$title = \CUser::FormatName(\CSite::GetNameFormat(), $this->arResult["User"], true);
		$APPLICATION->SetTitle($title);

		$this->includeComponentTemplate();
	}
}
?>