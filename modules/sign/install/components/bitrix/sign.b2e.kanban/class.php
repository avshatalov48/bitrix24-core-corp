<?php

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Document\Entity\SmartB2e;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Service\Container;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignB2eKanbanComponent extends SignBaseComponent
{
	private const SIGN_B2E_CLASS_FOR_ONBOARDING_CREATE = 'sign-b2e-onboarding-create';

	protected function exec(): void
	{
		parent::exec();
		$this->prepareResult();
	}

	public function executeComponent(): void
	{
		if (!Storage::instance()->isB2eAvailable())
		{
			showError('access denied');

			return;
		}

		parent::executeComponent();

		$this->addOnboardingClasses();

		if (B2eTariff::instance()->isB2eRestrictedInCurrentTariff())
		{
			$this->lockAddButton();
		}
	}

	private function addOnboardingClasses()
	{
		foreach (Toolbar::getButtons() as $button)
		{
			if ($button instanceof Button && str_contains($button->getLink(), 'sign/b2e/doc/'))
			{
				$button->addClass(self::SIGN_B2E_CLASS_FOR_ONBOARDING_CREATE);
				break;
			}
		}
	}

	private function lockAddButton(): void
	{
		foreach (Toolbar::getButtons() as $button)
		{
			if ($button instanceof Button && str_contains($button->getLink(), 'sign/b2e/doc/'))
			{
				$button
					->setIcon(\Bitrix\UI\Buttons\Icon::LOCK)
					->addClass('sign-b2e-js-tarriff-slider-trigger')
					->setTag('button')
				;

				break;
			}
		}
	}

	private function prepareResult(): void
	{
		$this->arResult['PORTAL_REGION'] = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion();
		$this->arResult['CAN_ADD_DOCUMENT'] = $this->getAccessController()->check(ActionDictionary::ACTION_B2E_DOCUMENT_ADD);
		$this->arResult['CAN_EDIT_DOCUMENT'] = $this->getAccessController()->check(ActionDictionary::ACTION_B2E_DOCUMENT_EDIT);
		$this->arResult['ENTITY_TYPE_ID'] = SmartB2e::getEntityTypeId();
		$this->arResult['SHOW_TARIFF_SLIDER'] =
			$this->accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_READ)
			&& B2eTariff::instance()->isB2eRestrictedInCurrentTariff()
		;
		$this->arResult['SHOW_WELCOME_TOUR'] = false;
		$this->arResult['SHOW_WELCOME_TOUR_TEST_SIGNING'] = false;
		$this->arResult['BY_EMPLOYEE_ENABLED'] = \Bitrix\Sign\Config\Feature::instance()->isSendDocumentByEmployeeEnabled();
		$this->arResult['SHOW_ONBOARDING_SIGNING_BANNER'] = $this->isTestSigningBannerVisible(
			(int)CurrentUser::get()->getId(),
			$this->arResult['PORTAL_REGION'],
		);
		// TODO: move to test signing wizard
		$this->arResult['COMPANY'] = \Bitrix\Sign\Service\Container::instance()
			->getCrmMyCompanyService()
			->getFirstCompanyWithTaxId(checkRequisitePermissions: false)
		;

		if (!Storage::instance()->isToursDisabled())
		{
			if ($this->arResult['PORTAL_REGION'] === 'ru')
			{
				$this->arResult['SHOW_WELCOME_TOUR_TEST_SIGNING'] = true;
			}
			else
			{
				$this->arResult['SHOW_WELCOME_TOUR'] = true;
			}
		}
	}

	private function isTestSigningBannerVisible(int $userId, string $portalRegion): bool
	{
		if ($userId < 1)
		{
			return false;
		}

		if ($portalRegion !== 'ru')
		{
			return false;
		}

		if (!($this->arResult['CAN_ADD_DOCUMENT'] ?? false))
		{
			return false;
		}

		if (!($this->arResult['CAN_EDIT_DOCUMENT'] ?? false))
		{
			return false;
		}

		$onboardingService = Container::instance()->getOnboardingService();

		if (!$onboardingService->isBannerSeenByUser($userId))
		{
			$this->initializeBannerVisibility($userId);
		}

		return $onboardingService->isBannerVisible($userId);
	}

	private function initializeBannerVisibility(int $userId): void
	{
		$onboardingService = Container::instance()->getOnboardingService();
		$memberService = \Bitrix\Sign\Service\Container::instance()->getMemberService();
		
		$isUserMemberOrInitiatorWithDoneStatus = $memberService->isUserMemberOrInitiatorWithDoneStatus($userId);

		$isUserMemberOrInitiatorWithDoneStatus 
			? $onboardingService->setBannerHidden($userId)
			: $onboardingService->setBannerVisible($userId)
		;
	}
}
