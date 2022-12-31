<?php


namespace Bitrix\Crm\Service\Display\Field;


use Bitrix\Crm\Service\Display\Options;

class StatusField extends StringField
{
	public const TYPE = 'status';

	public function getType(): string
	{
		return self::TYPE;
	}

	protected function render(Options $displayOptions, $entityId, $value): string
	{
		throw new Exception('Multiple values are not supported');
	}

	protected function renderSingleValue($fieldValue, int $itemId, Options $displayOptions): string
	{
		$this->setWasRenderedAsHtml(true);

		$valueConfig = $this->getValueConfig($fieldValue);
		$text = $valueConfig['text'] ?? null;
		if (!$text)
		{
			return '';
		}

		$cssPrefix = $this->getDisplayParam('cssPrefix', '');
		$classes = $cssPrefix . ' ' . $cssPrefix . '-' . $valueConfig['cssPostfix'];

		return '<div class="' . $classes .'">' . $text . '</div>';
	}
}
