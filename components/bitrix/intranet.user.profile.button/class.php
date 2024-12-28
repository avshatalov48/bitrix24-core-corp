<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Bitrix24\Feature;
use Bitrix\Intranet\Integration\Templates\Bitrix24\ThemePicker;
use Bitrix\Intranet\Util;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\Component;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet;
use Bitrix\Main;
use Bitrix\Security\Mfa\Otp;
use Bitrix\Intranet\Binding;

Loc::loadMessages(__FILE__);

class IntranetUserProfileButton extends \CBitrixComponent implements Controllerable
{
	private Intranet\CurrentUser $currentUser;
	private bool $isCloud;
	private bool $isExtranet;
	private ?int $userId;
	private bool $isCollaber;

	public function __construct($component = null)
	{
		$this->currentUser = Intranet\CurrentUser::get();
		$this->userId = $this->currentUser->getId();
		$this->isCloud = Loader::includeModule('bitrix24');
		$this->isExtranet = Loader::includeModule('extranet') && \CExtranet::isExtranetSite();
		$this->isCollaber = $this->isExtranet
			&& \Bitrix\Extranet\Service\ServiceContainer::getInstance()
				->getCollaberService()
				->isCollaberById($this->userId)
		;
		parent::__construct($component);
	}

	protected function listKeysSignedParameters(): array
	{
		return [
			'PATH_TO_USER_STRESSLEVEL',
			'USER_ID',
		];
	}

	public function executeComponent()
	{
		if ($this->currentUser->isAuthorized())
		{
			$this->prepareResult();
		}
		else
		{
			$this->setTemplateName('auth');
		}

		$this->includeComponentTemplate();
	}

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['THUMBNAIL_SIZE'] = isset($arParams['THUMBNAIL_SIZE']) ? (int)$arParams['THUMBNAIL_SIZE'] : 100;
		$arParams['USER_ID'] = $this->userId;
		$arParams['PATH_TO_USER_PROFILE'] = $arParams['PATH_TO_USER_PROFILE']
			?? SITE_DIR . 'company/personal/user/#user_id#/';
		$arParams["PATH_TO_USER_STRESSLEVEL"] = $arParams["PATH_TO_USER_STRESSLEVEL"]
			?? SITE_DIR . "company/personal/user/#user_id#/stresslevel/";
		$arParams['PATH_TO_USER_COMMON_SECURITY'] = $arParams['PATH_TO_USER_COMMON_SECURITY']
			?? SITE_DIR . 'company/personal/user/#user_id#/common_security/';

		return parent::onPrepareComponentParams($arParams);
	}

	protected function prepareResult(): void
	{
		$this->arResult['USER_ID'] = $this->userId;
		$this->arResult['IS_EXTRANET'] = $this->isExtranet;
		$this->arResult['IS_COLLABER'] = $this->isCollaber;
		$this->arResult['IS_CLOUD'] = $this->isCloud;
		$this->arResult['IS_ADMIN'] = $this->currentUser->isAdmin();
		$this->arResult['USER_NAME'] = htmlspecialcharsbx($this->currentUser->getFormattedName());
		$this->arResult['USER_URL'] = CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER_PROFILE'],
			['user_id' => $this->userId]);
		$this->arResult['USER_WORK_POSITION'] = $this->currentUser->getWorkPosition();
		$this->arResult['USER_STATUS'] = Util::getUserStatus($this->userId);
		$this->arResult += Util::getAppsInstallationConfig($this->userId);
		$this->arResult['LOGIN_HISTORY'] = $this->getLoginHistoryData();
		$this->arResult['IS_STRESSLEVEL_AVAILABLE'] = $this->isStressLevelAvailable($this->arResult['USER_STATUS']);
		$this->arResult['IS_SOCIALNETWORK_ADMIN'] = $this->isUserSocialnetworkAdmin();
		$this->arResult['B24NET_PANEL_AVAILABLE'] = $this->isB24NetPanelAvailable();
		$this->arResult['IS_SIGN_DOCUMENT_AVAILABLE'] = $this->isSignDocumentAvailable();
		$this->arResult['IS_SIGN_DOCUMENT_LOCKED'] = $this->arResult['IS_SIGN_DOCUMENT_AVAILABLE'] && $this->isSignDocumentLocked();
		$this->arResult['USER_PHOTO_ID'] = $this->currentUser->getPersonalPhotoId();
		$this->arResult['USER_PERSONAL_PHOTO_SRC'] = $this->getUserPhotoSrc($this->arResult['USER_PHOTO_ID']);
		$this->arResult['MASK'] = $this->getUserMaskData($this->arResult['USER_PHOTO_ID']);
		$this->arResult['OTP'] = $this->getOtpData();
		$this->arResult['BINDINGS'] = $this->getBindingData($this->arResult['USER_STATUS']);
		$this->arResult['DESKTOP_DOWNLOAD_LINKS'] = array_map(
			fn($link) => htmlspecialcharsbx($link),
			\Bitrix\Intranet\Portal::getInstance()->getSettings()->getDesktopDownloadLinks()
		);
		$this->arResult['NETWORK_PROFILE_URL'] = $this->getNetworkProfileUrl();
	}

	protected function getUserPhotoSrc(?int $userPhotoId): string
	{
		$userPersonalPhotoSrc = '';

		if ($userPhotoId > 0
			&& $this->currentUser->isAuthorized()
			&& ($imageConfig = CFile::ResizeImageGet(
				$userPhotoId,
				[
					'width' => $this->arParams['THUMBNAIL_SIZE'],
					'height' => $this->arParams['THUMBNAIL_SIZE'],
				],
				BX_RESIZE_IMAGE_EXACT,
			))
			&& is_array($imageConfig)
			&& !empty($imageConfig['src'])
		)
		{
			$userPersonalPhotoSrc = $imageConfig['src'];
		}

		return (string)$userPersonalPhotoSrc;
	}

	protected function getUserMaskData(?int $photoId): ?array
	{
		if (
			class_exists(Bitrix\UI\Avatar\Mask\Helper::class)
			&& (Option::get('ui', 'avatar-editor-availability', 'N') === 'Y')
		)
		{
			return Bitrix\UI\Avatar\Mask\Helper::getData($photoId);
		}

		return null;
	}

	protected function getBindingData(string $userStatus): array
	{
		return !$this->isExtranet && !in_array($userStatus, ['email', 'extranet'])
			? Binding\Menu::getMenuItems('top_panel', 'user_menu',
				['inline' => true, 'context' => ['USER_ID' => $this->userId]]) : [];
	}

	protected function getOtpData(): array
	{
		$isEnabled = Loader::includeModule('security') && Otp::isOtpEnabled();
		$data['IS_ENABLED'] = $isEnabled ? 'Y' : 'N';

		if ($isEnabled)
		{
			$data['IS_ACTIVE'] = \CSecurityUser::IsUserOtpActive($this->userId) ? 'Y' : 'N';
			$data['URL'] = CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER_COMMON_SECURITY'],
				['user_id' => $this->userId]);
		}

		return $data;
	}

	protected function getLoginHistoryData(): array
	{
		$data = [
			'url' => Intranet\Site\Sections\TimemanSection::getUserLoginHistoryUrl(),
			'isCloud' => $this->isCloud,
			'isHide' => ($this->isCloud && (\CBitrix24::getPortalZone() === 'ua'))
				|| !\Bitrix\Intranet\Util::isIntranetUser($this->userId),
		];

		if ($this->isCloud)
		{
			$data['isAvailableUserLoginHistory'] = Feature::isFeatureEnabled('user_login_history');
			$data['isConfiguredUserLoginHistory'] = true;
		}
		else
		{
			$data['isAvailableUserLoginHistory'] = true;
			$data['isConfiguredUserLoginHistory'] = Option::get('main', 'user_device_history', 'N') === 'Y';
		}

		return $data;
	}

	protected function isUserSocialnetworkAdmin(): bool
	{
		return Loader::includeModule("socialnetwork")
			&& \CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false);
	}

	protected function isB24NetPanelAvailable(): bool
	{
		return $this->isCloud && Option::get('bitrix24', 'network', 'N') === 'Y';
	}

	protected function isStressLevelAvailable(string $userStatus): bool
	{
		return Option::get('intranet', 'stresslevel_available', 'Y') === 'Y'
			&& !in_array($userStatus, ['email', 'extranet'])
			&& !$this->isExtranet;
	}

	public function configureActions(): array
	{
		return [];
	}

	public function getUserStatComponentAction(): Component
	{
		return new Component('bitrix:intranet.ustat.status', 'lite', []);
	}

	public function getThemePickerDataAction(): array
	{
		$themePicker = new ThemePicker(defined('SITE_TEMPLATE_ID') ? SITE_TEMPLATE_ID : 'bitrix24');

		return array_intersect_key($themePicker->getCurrentTheme() ?? [],
			['title' => '', 'previewColor' => '', 'previewImage' => '', 'id' => '']);
	}

	private function isSignDocumentAvailable(): bool
	{
		if (!Loader::includeModule('sign'))
		{
			return false;
		}
		if (
			!class_exists(\Bitrix\Sign\FeatureResolver::class)
			|| !class_exists(\Bitrix\Sign\Config\Feature::class)
		)
		{
			return false;
		}

		return !$this->isExtranet
			&& \Bitrix\Sign\FeatureResolver::instance()->released('sendByEmployee')
			&& \Bitrix\Sign\Config\Feature::instance()->isSendDocumentByEmployeeEnabled()
		;
	}

	private function getNetworkProfileUrl(): ?string
	{
		if (
			Loader::includeModule('bitrix24')
			&& Loader::includeModule('socialservices')
		)
		{
			return rtrim(CSocServBitrix24Net::NETWORK_URL, '/') . '/passport/view/';
		}

		return null;
	}

	private function isSignDocumentLocked(): bool
	{
		if (!Loader::includeModule('sign'))
		{
			return false;
		}

		if (
			!class_exists(\Bitrix\Sign\Integration\Bitrix24\B2eTariff::class)
			|| !method_exists(\Bitrix\Sign\Integration\Bitrix24\B2eTariff::class, 'isB2eRestrictedInCurrentTariff')
		)
		{
			return false;
		}

		return \Bitrix\Sign\Integration\Bitrix24\B2eTariff::instance()->isB2eRestrictedInCurrentTariff();
	}
}
