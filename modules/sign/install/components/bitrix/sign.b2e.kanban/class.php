<?php

use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\UI;
use Bitrix\Sign\Access\ActionDictionary;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}


CBitrixComponent::includeComponentClass('bitrix:sign.base');

final class SignB2eKanbanComponent extends SignBaseComponent
{
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

		if (B2eTariff::instance()->isB2eRestrictedInCurrentTariff())
		{
			$this->lockAddButton();
		}
	}

	private function lockAddButton(): void
	{
		foreach (\Bitrix\UI\Toolbar\Facade\Toolbar::getButtons() as $button)
		{
			if ($button instanceof UI\Buttons\Button && str_contains($button->getLink(), 'sign/b2e/doc/'))
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
		$this->arResult['ENTITY_TYPE_ID'] = \Bitrix\Sign\Document\Entity\SmartB2e::getEntityTypeId();
		$this->arResult['SHOW_TARIFF_SLIDER'] = $this->accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_READ)
			&& B2eTariff::instance()->isB2eRestrictedInCurrentTariff();
	}
}
