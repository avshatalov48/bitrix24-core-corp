<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Intranet\MainPage\Publisher;
use Bitrix\Main\Loader;
use Bitrix\Intranet;
use Bitrix\Main\UI\Spotlight;
use Bitrix\Security\Mfa\Otp;
use Bitrix\Intranet\Settings\Widget\Requisite;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Bitrix24;
use Bitrix\Intranet\MainPage;

class IntranetSettingsWidgetComponent extends CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
	//region Holding
	private bool $isBitrix24 = false;
	private bool $isHolding = false;
	private bool $canBeHolding = false;
	private bool $canBeAffiliate = false;
	private array $affiliates = [];
	//endregion

	private bool $showWidget = false;

	private Intranet\CurrentUser $user;

	private bool $isRequisiteAvailable = false;

	private static array $cachedResult = [];
	private static int $number = 0;
	private static array $cachedAffiliates = [];

	public function configureActions(): array
	{
		return [];
	}

	public function __construct($component = null)
	{
		parent::__construct($component);
		self::$number++;
	}

	private function getUser(): Intranet\CurrentUser
	{
		$this->user ??= Intranet\CurrentUser::get();

		return $this->user;
	}

	protected function getOtpData(): array
	{
		$data = ['IS_ACTIVE' => 'N'];

		if (Loader::includeModule('security') && Otp::isOtpEnabled())
		{
			$data['IS_ACTIVE'] = Otp::isMandatoryUsing() ? 'Y' : 'N';

			if ($data['IS_ACTIVE'] === 'Y' && $this->isBitrix24)
			{
				$otpRights = Otp::getMandatoryRights();
				$adminGroup = 'G1';
				$employeeGroup = 'G' . \CBitrix24::getEmployeeGroupId();

				if (!in_array($adminGroup, $otpRights, true) || !in_array($employeeGroup, $otpRights, true))
				{
					$data['IS_ACTIVE'] = 'N';
				}
			}
		}

		return $data;
	}

	protected function getAffiliate(): ?array
	{
		$affiliates = array_filter(
			$this->affiliates ?? [],
			fn(Bitrix24\Holding\AffiliateProfiled $affiliate) => $affiliate->isCurrent()
		);
		if ($affiliate = reset($affiliates))
		{
			return [
				'name' => $affiliate->getName(),
				'url' => $affiliate->getUrl(),
				'profileId' => $affiliate->getProfileId(),
				'profileName' => $affiliate->getProfileName(),
				'profilePhoto' => $affiliate->getProfilePhoto(),
				'profileTheme' => $affiliate->getProfileTheme(),
				'isHolding' => $affiliate->isHolding(),
			];
		}

		return null;
	}

	private function initAffiliates(): void
	{
		if (!isset(self::$cachedAffiliates[$this->getUser()->getId()]))
		{
			$affiliates = [];
			$isHolding = false;

			$currentPortal = Bitrix24\Holding\CurrentPortal::getInstance();
			if ($currentPortal->isBound())
			{
				$isHolding = Bitrix24\Holding\CurrentPortal::getInstance()->isHolding();

				Bitrix24\Holding\AffiliateProfiled::refreshList($this->getUser());
				$affiliates = array_filter(
					Bitrix24\Holding\AffiliateProfiled::getList($this->getUser()) ?? [],
					fn(Bitrix24\Holding\AffiliateProfiled $affiliate) => (
						$affiliate->isAvailable() || $this->getUser()->isAdmin() && ($isHolding || $affiliate->isHolding())
					)
				);
			}
			elseif ($currentPortal->canBeBound())
			{
				$isHolding = $currentPortal->canBeHolding();
			}

			self::$cachedAffiliates[$this->getUser()->getId()] =
				[$isHolding, $affiliates, $currentPortal->canBeHolding(), $currentPortal->canBeAffiliate()]
			;
		}

		[$this->isHolding, $this->affiliates, $this->canBeHolding, $this->canBeAffiliate] = self::$cachedAffiliates[$this->getUser()->getId()];
	}

	public function getDataAction(): Bitrix\Main\Response
	{
		if (!$this->showWidget)
		{
			return AjaxJson::createDenied();
		}

		return AjaxJson::createSuccess([
			'theme' => Intranet\Integration\Templates\Bitrix24\ThemePicker::getInstance()->getCurrentTheme(),
			'otp' => $this->getOtpData(),
			'holding' => $this->arParams['HOLDING'],
		]);
	}

	public function getRequisitesAction(): Bitrix\Main\Response
	{
		if (Loader::includeModule('crm') && $this->getUser()->isAdmin())
		{
			return AjaxJson::createSuccess([
				'requisite' => Requisite::getInstance()->getRequisitesData(),
			]);
		}

		return AjaxJson::createDenied();
	}

	public function createRequisiteLandingAction(): Bitrix\Main\Response
	{
		if (Loader::includeModule('crm') && $this->getUser()->isAdmin())
		{
			if ($requisites = Requisite::getInstance()->getRequisites())
			{
				$requisites->connectLanding();

				return AjaxJson::createSuccess([
					'isConnected' => $requisites->isLandingConnected(),
					'isPublic' => $requisites->isLandingPublic(),
					'publicUrl' => $requisites->getLandingPublicUrl(),
					'editUrl' => $requisites->getLandingEditUrl(),
				]);
			}
		}

		return AjaxJson::createDenied();
	}

	public function getAdditionalArguments(): array
	{
		if (!$this->showWidget)
		{
			return [];
		}

		$result['SETTINGS_PATH'] = Intranet\Portal::getInstance()->getSettings()->getSettingsUrl();
		$result['IS_FREE_LICENSE'] = false;
		$result['MARKET_URL'] = Intranet\Binding\Marketplace::getMainDirectory();
		$result['THEME'] = Intranet\Integration\Templates\Bitrix24\ThemePicker::getInstance()->getCurrentTheme();
		$result['OTP'] = $this->getOtpData();
		$result['HOLDING'] = null;
		$result['MAIN_PAGE'] = [
			'isAvailable' => self::$cachedResult['IS_WIDGET_MENU_ITEM_SHOW'],
			'isNew' => self::$cachedResult['IS_WIDGET_MENU_ITEM_SHOW'] && time() < mktime(23, 59, 59, 9, 30, 2024),
			'settingsPath' => (new Intranet\Site\FirstPage\MainFirstPage())->getSettingsPath() . '&analyticContext=widget_settings_settings',
		];

		if ($this->isRequisiteAvailable)
		{
			$result['REQUISITE'] = Requisite::getInstance()->getRequisitesData();
		}

		if ($this->isBitrix24)
		{
			$result['IS_RENAMEABLE'] = Bitrix24\Domain::getCurrent()->isRenameable() && $this->user->isAdmin();
			$result['IS_FREE_LICENSE'] = \CBitrix24::isFreeLicense();
			$result['HOLDING'] = [
				'isHolding' => $this->isHolding,
				'affiliate' => $this->getAffiliate(),
				'canBeHolding' => $this->canBeHolding,
				'canBeAffiliate' => $this->canBeAffiliate,
			];
		}

		return $result;
	}

	public function getWidgetComponentAction()
	{
		return new \Bitrix\Main\Engine\Response\Component(
			'bitrix:intranet.settings.widget',
			'.main'
		);
	}

	public function executeComponent(): void
	{
		if (empty(self::$cachedResult))
		{
			if (Loader::includeModule('bitrix24'))
			{
				$this->isBitrix24 = true;
				$this->initAffiliates();
			}

			if (Loader::includeModule('crm') && $this->getUser()->isAdmin())
			{
				$this->isRequisiteAvailable = true;
			}

			$this->showWidget = $this->getUser()->isAdmin() || count($this->affiliates) > 1;
			self::$cachedResult['SHOW_WIDGET'] = $this->showWidget;

			if ($this->showWidget)
			{
				self::$cachedResult['IS_ADMIN'] = $this->getUser()->isAdmin();
				self::$cachedResult['IS_REQUISITE'] = $this->isRequisiteAvailable;
				self::$cachedResult['IS_BITRIX24'] = $this->isBitrix24;
				self::$cachedResult['SPOTLIGHT'] = false;
				self::$cachedResult['SPOTLIGHT_AFTER_CREATE'] = false;
				$mainPageAccess = new MainPage\Access();
				self::$cachedResult['IS_MAIN_PAGE_AVAILABLE'] = $mainPageAccess->canEdit();
				self::$cachedResult['IS_WIDGET_MENU_ITEM_SHOW'] = $mainPageAccess->canEdit(false);
				$spotlight = new Spotlight('intranet-main-page');
				$spotlightAfterFirstCreate = new Spotlight('intranet-main-page-after-create');

				if (self::$cachedResult['IS_MAIN_PAGE_AVAILABLE'])
				{
					if ($spotlight->isAvailable())
					{
						self::$cachedResult['SPOTLIGHT'] = true;
					}
					if (
						$spotlightAfterFirstCreate->isAvailable()
						&& Loader::includeModule('landing')
						&& (new Bitrix\Landing\Mainpage\Manager)->isReady()
						&& !((new Publisher)->isPublished())
					)
					{
						self::$cachedResult['SPOTLIGHT_AFTER_CREATE'] = true;
					}
				}
			}
		}

		$this->arResult = self::$cachedResult;
		$this->arResult['NUMBER'] = self::$number;

		if ($this->arResult['SHOW_WIDGET'])
		{
			$this->includeComponentTemplate();
		}
	}
}