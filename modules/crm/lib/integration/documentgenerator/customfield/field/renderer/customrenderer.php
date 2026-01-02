<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Renderer;

final class CustomRenderer extends AbstractRenderer
{
	public const TYPE_CUSTOM_FIELD = 'custom';

	protected const CSS_CLASS_INPUT = 'docs-template-load-input';

	public static function getSupportedTypes(): array
	{
		return [
			self::TYPE_CUSTOM_FIELD,
		];
	}

	protected function renderField(): string
	{
		$html = $this->field['HTML'] ?? '';

		return <<<HTML
			<div class="{$this->cssClassInputContainer}">
				{$this->renderLabel()}
				{$html}
				{$this->renderHelpText()}
			</div>
HTML;
	}

	protected function validateCustomRules(): void
	{
		if (empty($this->field['HTML']))
		{
			throw new \InvalidArgumentException("Custom field must have HTML content");
		}
	}

	protected function getContainerClass(): string
	{
		return $this->field['CONTAINER_CLASS'] ?? parent::getContainerClass();
	}
}
