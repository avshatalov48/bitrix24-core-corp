<?php

namespace Bitrix\Booking\Component\Booking;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\UI;

final class Toolbar
{
	public function __construct(
		private readonly string $afterTitleContainerId,
		private readonly string $counterPanelContainerId,
	)
	{
	}

	public function build(): void
	{
		UI\Toolbar\Facade\Toolbar::deleteFavoriteStar();
		$this->addAfterTitleContainerAndButtons();
		$this->addFilter();
		$this->addCounterPanelContainerAndRightButtons();
	}

	private function addAfterTitleContainerAndButtons(): void
	{
		UI\Toolbar\Facade\Toolbar::addAfterTitleHtml(<<<HTML
			<div class="booking-toolbar-custom-html --margin-right">
				<div id="{$this->afterTitleContainerId}"></div>
			</div>
		HTML);
	}

	private function getCreateButton(): UI\Buttons\Button
	{
		return (new UI\Buttons\Button([
			'click' => new UI\Buttons\JsCode("
				alert('create');
			"),
		]))
			->setColor(UI\Buttons\Color::SUCCESS)
			->setText(Loc::getMessage('BOOKING_TOOLBAR_BUTTON_CREATE'))
		;
	}

	private function addFilter(): void
	{
		$filter = new Filter();

		(new Options($filter::getId()))->reset();

		UI\Toolbar\Facade\Toolbar::addFilter($filter->getOptions());
	}

	private function addCounterPanelContainerAndRightButtons(): void
	{
		UI\Toolbar\Facade\Toolbar::addRightCustomHtml(<<<HTML
			<div class="booking-toolbar-custom-html --margin-left">
				<div id="{$this->counterPanelContainerId}"></div>
			</div>
		HTML);
	}

	private function getSettingsButton(): UI\Buttons\Button
	{
		return (new UI\Buttons\Button([
			'click' => new UI\Buttons\JsCode("
				alert('settings');
			"),
		]))
			->setColor(UI\Buttons\Color::LIGHT_BORDER)
			->addClass('ui-btn-themes')
			->setIcon(UI\Buttons\Icon::SETTINGS)
		;
	}
}
