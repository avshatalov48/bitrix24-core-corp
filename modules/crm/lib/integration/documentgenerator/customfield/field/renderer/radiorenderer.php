<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Renderer;

final class RadioRenderer extends AbstractRenderer
{
	public const TYPE_RADIO_FIELD = 'radio';

	public static function getSupportedTypes(): array
	{
		return [
			self::TYPE_RADIO_FIELD,
		];
	}

	protected function renderField(): string
	{
		return <<<HTML
			<div class="{$this->cssClassInputContainer}">
				<fieldset>
					{$this->renderLabel()}
					{$this->renderRadioButtons()}
				</fieldset>
				{$this->renderHelpText()}
			</div>
HTML;
	}

	protected function renderLabel(): string
	{
		$html = '<legend class="' . self::CSS_CLASS_TITLE . '">';
		$html .= htmlspecialcharsbx($this->field['TITLE']);

		if ($this->isRequired())
		{
			$html .= ' <span class="required">*</span>';
		}

		$html .= '</legend>';

		return $html;
	}

	protected function renderRadioButtons(): string
	{
		$html = [];
		$options = $this->field['OPTIONS'] ?? [];
		$selectedValue = $this->field['VALUE'] ?? '';
		$fieldName = htmlspecialcharsbx($this->field['UID']);

		foreach ($options as $value => $text)
		{
			$res = htmlspecialcharsbx($value);
			$checked = $selectedValue === $value ? ' checked' : '';
			$text = htmlspecialcharsbx($text);

			$html[] = <<<HTML
				<div class="docs-template-radio-item">
					<input type="radio" name="{$fieldName}" id="{$res}" value="{$res}" {$checked} />
					<label for="{$res}">{$text}</label>
				</div>
HTML;
		}

		return implode("\n", $html);
	}

	protected function validateCustomRules(): void
	{
		if (empty($this->field['OPTIONS']) || !is_array($this->field['OPTIONS']))
		{
			throw new \InvalidArgumentException("Radio field must have OPTIONS array");
		}
	}

	protected function hasInputId(): bool
	{
		return false;
	}
}
