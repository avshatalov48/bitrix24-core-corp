<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Renderer;

class TextRenderer extends AbstractRenderer
{
	public const TYPE_TEXT_FIELD_TEXT = 'text';
	public const TYPE_TEXT_FIELD_EMAIL = 'email';
	public const TYPE_TEXT_FIELD_URL = 'url';
	public const TYPE_TEXT_FIELD_NUMBER = 'number';

	protected const CSS_CLASS_INPUT = 'docs-template-load-input';
	public static function getSupportedTypes(): array
	{
		return [
			self::TYPE_TEXT_FIELD_TEXT,
			self::TYPE_TEXT_FIELD_EMAIL,
			self::TYPE_TEXT_FIELD_URL,
			self::TYPE_TEXT_FIELD_NUMBER,
		];
	}

	protected function renderField(): string
	{
		return <<<HTML
			<div class="{$this->cssClassInputContainer}">
				{$this->renderLabel()}
				{$this->renderInput()}
				{$this->renderHelpText()}
			</div>
HTML;
	}

	protected function renderInput(): string
	{
		$attributes = $this->buildAttributes([
			'class' => self::CSS_CLASS_INPUT,
			'type' => $this->field['TYPE']
		]);

		return "<input{$attributes} />";
	}

	protected function validateCustomRules(): void
	{
		$validation = $this->field['VALIDATION'] ?? [];

		if (isset($validation['minLength']) && !is_numeric($validation['minLength']))
		{
			throw new \InvalidArgumentException("Configuration field 'minLength' validation must be numeric");
		}

		if (isset($validation['maxLength']) && !is_numeric($validation['maxLength']))
		{
			throw new \InvalidArgumentException("Configuration field 'maxLength' validation must be numeric");
		}

		if ($this->field['TYPE'] === self::TYPE_TEXT_FIELD_NUMBER)
		{
			$this->validateNumberField();
		}

		if ($this->field['TYPE'] === self::TYPE_TEXT_FIELD_EMAIL)
		{
			$this->validateEmailField();
		}
	}

	private function validateNumberField(): void
	{
		$validation = $this->field['VALIDATION'] ?? [];

		if (isset($validation['min']) && !is_numeric($validation['min']))
		{
			throw new \InvalidArgumentException("Configuration field 'min' validation must be numeric");
		}

		if (isset($validation['max']) && !is_numeric($validation['max']))
		{
			throw new \InvalidArgumentException("Configuration field 'max' validation must be numeric");
		}
	}

	private function validateEmailField(): void
	{
		$validation = $this->field['VALIDATION'] ?? [];

		if (isset($validation['pattern']) && !is_string($validation['pattern']))
		{
			throw new \InvalidArgumentException("Configuration field 'pattern' validation must be string");
		}
	}
}
