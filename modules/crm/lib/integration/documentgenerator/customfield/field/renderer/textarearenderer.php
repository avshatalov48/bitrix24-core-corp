<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Renderer;

final class TextareaRenderer extends TextRenderer
{
	public const TYPE_TEXTAREA_FIELD = 'textarea';

	public static function getSupportedTypes(): array
	{
		return [
			self::TYPE_TEXTAREA_FIELD,
		];
	}

	protected function renderField(): string
	{
		return <<<HTML
			<div class="{$this->cssClassInputContainer}">
				{$this->renderLabel()}
				{$this->renderTextarea()}
				{$this->renderHelpText()}
			</div>
HTML;
	}

	protected function renderTextarea(): string
	{
		$attributes = $this->buildAttributes([
			'class' => self::CSS_CLASS_INPUT
		]);

		$value = htmlspecialcharsbx($this->field['VALUE'] ?? '');

		return "<textarea{$attributes}>{$value}</textarea>";
	}

	protected function getBaseAttributes(): array
	{
		$attributes = parent::getBaseAttributes();

		unset($attributes['value']);

		return $attributes;
	}
}
