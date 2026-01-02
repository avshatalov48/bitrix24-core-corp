<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Control;

final class Button extends AbstractControl
{
	public function __construct(
		public \Bitrix\UI\Buttons\Button $button,
	)
	{
	}

	public function getName(): string
	{
		return 'ButtonControl';
	}

	public function getProps(): array
	{
		return [
			'buttonOptions' => [
				'text' => $this->button->getText(),
				'style' => $this->button->getStyle(),
				'size' => $this->button->getSize(),
				'color' => $this->button->getColor(),
				'link' => $this->button->getLink(),
				'events' => $this->button->getEvents(),
				'disabled' => $this->button->isDisabled(),
				'useAirDesign' => true,
				'wide' => true,
			],
		];
	}
}
