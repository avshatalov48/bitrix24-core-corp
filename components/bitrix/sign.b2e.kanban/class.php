<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Document\Entity\SmartB2e;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Toolbar\Facade\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignB2eKanbanComponent extends SignBaseComponent
{
	private const TOUR_ID = 'sign-tour-guide-sign-start-kanban-b2e-by-employee';
	private const TOUR_ID_OLD = 'sign-tour-guide-sign-start-kanban-b2e';

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
					->addClass('ui-btn-icon-lock')
					->addClass('sign-b2e-js-tarriff-slider-trigger')
					->setTag('button')
				;

				break;
			}
		}
	}

	private function prepareResult(): void
	{
		$this->arResult['ENTITY_TYPE_ID'] = SmartB2e::getEntityTypeId();
		$this->arResult['SHOW_TARIFF_SLIDER'] = $this->accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_READ)
			&& B2eTariff::instance()->isB2eRestrictedInCurrentTariff();

		$this->arResult['SHOW_WELCOME_TOUR'] = false;
		$this->arResult['SHOW_BY_EMPLOYEE_TOUR'] = false;
		$this->arResult['SHOW_TOUR_BTN_CREATE'] = false;

		if (\Bitrix\Sign\Config\Feature::instance()->isSendDocumentByEmployeeEnabled())
		{
			$lastOnboardingVisitDate = \Bitrix\Sign\Service\Container::instance()
				->getTourService()
				->getLastVisitDate(
					self::TOUR_ID,
					\Bitrix\Main\Engine\CurrentUser::get()->getId(),
				)
			;

			$this->arResult['SHOW_WELCOME_TOUR'] =
				$lastOnboardingVisitDate === null
				&& !Storage::instance()->isToursDisabled()
				&& !$this->isCurrentUserCreatedB2eDocuments()
			;

			$this->arResult['SHOW_BY_EMPLOYEE_TOUR'] =
				$this->arResult['SHOW_WELCOME_TOUR'] === false
				&& $lastOnboardingVisitDate === null
				&& !Storage::instance()->isToursDisabled()
			;

			$this->arResult['TOUR_ID'] = self::TOUR_ID;
		}
		else
		{
			$this->arResult['SHOW_TOUR_BTN_CREATE'] = !$this->isCurrentUserCreatedB2eDocuments();
			$this->arResult['TOUR_ID'] = self::TOUR_ID_OLD;
		}
	}

	private function isCurrentUserCreatedB2eDocuments(): bool
	{
		return (bool)\Bitrix\Sign\Service\Container::instance()
			->getDocumentRepository()
			->listLastB2eFromCompanyByUserCreateId(
				\Bitrix\Main\Engine\CurrentUser::get()->getId(),
				1,
			)
			->count()
		;
	}
}
