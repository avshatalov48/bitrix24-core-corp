<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Intranet;
use Bitrix\Security\Mfa\Otp;
use Bitrix\Intranet\Settings\Requisite;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bitrix24;
use Bitrix\Main;

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

	private $requisites;

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

		$this->user = Intranet\CurrentUser::get();

		if (Loader::includeModule('bitrix24'))
		{
			$this->isBitrix24 = true;
			$this->initAffiliates();
		}
	}

	private function getUser(): Intranet\CurrentUser
	{
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

	private function getRequisites(): ?array
	{
		if (
			!Loader::includeModule('crm')
			|| !class_exists('Bitrix\Crm\Integration\Landing\RequisitesLanding')
		)
		{
			return null;
		}

		$companyId = \Bitrix\Crm\Requisite\EntityLink::getDefaultMyCompanyId();
		$company = new Requisite\CompanyList(['=ID' => $companyId], ['DATE_CREATE' => 'DESC'], ['ID'], ['ID', 'ENTITY_ID']);

		if ($companyId)
		{
			if ($this->requisites = $company->getLandingList()->toArray()[$companyId])
			{
				return [
					'isCompanyCreated' => true,
					'isConnected' => $this->requisites->isLandingConnected(),
					'isPublic' => $this->requisites->isLandingPublic(),
					'publicUrl' => $this->requisites->getLandingPublicUrl(),
					'editUrl' => $this->requisites->getLandingEditUrl(),
				];
			}
		}

		return [
			'isCompanyCreated' => false,
		];
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
		if ($this->showWidget)
		{
			return AjaxJson::createSuccess([
				'requisite' => $this->getRequisites(),
			]);
		}

		return AjaxJson::createDenied();
	}

	public function createRequisiteLandingAction(): Bitrix\Main\Response
	{
		if (
			$this->requisites
			&& $this->showWidget
		)
		{
			$this->requisites->connectLanding();

			return AjaxJson::createSuccess([
				'isConnected' => $this->requisites->isLandingConnected(),
				'isPublic' => $this->requisites->isLandingPublic(),
				'publicUrl' => $this->requisites->getLandingPublicUrl(),
				'editUrl' => $this->requisites->getLandingEditUrl(),
			]);
		}

		return AjaxJson::createDenied();
	}

	public function onPrepareComponentParams($arParams): array
	{
		// for now show widget only for admin or users with affiliate
		$this->showWidget = $this->getUser()->isAdmin() || count($this->affiliates) > 1;

		if (!$this->showWidget)
		{
			return [];
		}

		$arParams['SETTINGS_PATH'] = Intranet\Portal::getInstance()->getSettings()->getSettingsUrl();
		$arParams['REQUISITE'] = $this->getRequisites();
		$arParams['IS_BITRIX24'] = $this->isBitrix24;
		$arParams['IS_FREE_LICENSE'] = false;
		$arParams['IS_ADMIN'] = $this->getUser()->isAdmin();
		$arParams['MARKET_URL'] = Intranet\Binding\Marketplace::getMainDirectory();
		$arParams['THEME'] = Intranet\Integration\Templates\Bitrix24\ThemePicker::getInstance()->getCurrentTheme();
		$arParams['OTP'] = $this->getOtpData();
		$arParams['HOLDING'] = null;

		if ($this->isBitrix24)
		{
			$arParams['IS_RENAMEABLE'] = Bitrix24\Domain::getCurrent()->isRenameable() && $this->user->isAdmin();
			$arParams['IS_FREE_LICENSE'] = \CBitrix24::isFreeLicense();
			$arParams['HOLDING'] = [
				'isHolding' => $this->isHolding,
				'affiliate' => $this->getAffiliate(),
				'canBeHolding' => $this->canBeHolding,
				'canBeAffiliate' => $this->canBeAffiliate,
			];
		}

		return $arParams;
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
		if ($this->showWidget)
		{
			$this->arResult['NUMBER'] = self::$number;
			$this->includeComponentTemplate();
		}
	}
}