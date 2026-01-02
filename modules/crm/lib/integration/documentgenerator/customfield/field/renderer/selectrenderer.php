<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Renderer;

final class SelectRenderer extends AbstractRenderer
{
	public const TYPE_SELECT_FIELD = 'select';

	protected const CSS_CLASS_INPUT = 'docs-template-load-select';

	public static function getSupportedTypes(): array
	{
		return [
			self::TYPE_SELECT_FIELD,
		];
	}

	protected function renderField(): string
	{
		return <<<HTML
			<div class="{$this->cssClassInputContainer}">
				{$this->renderLabel()}
				{$this->renderSelect()}
				{$this->renderHelpText()}
			</div>
HTML;
	}

	protected function renderSelect(): string
	{
		$attributes = $this->buildAttributes([
			'class' => self::CSS_CLASS_INPUT,
		]);

		$html = "<select{$attributes}>";
		$html .= $this->renderOptions();
		$html .= '</select>';

		return $html;
	}

	protected function renderOptions(): string
	{
		$html = '';
		$options = $this->field['OPTIONS'] ?? [];
		$selectedValue = $this->field['VALUE'] ?? '';

		foreach ($options as $value => $text)
		{
			$selected = $selectedValue === $value ? ' selected="selected"' : '';
			$html .= '<option value="' . htmlspecialcharsbx($value) . '"' . $selected . '>';
			$html .= htmlspecialcharsbx($text);
			$html .= '</option>';
		}

		return $html;
	}

	protected function validateCustomRules(): void
	{
		if (empty($this->field['OPTIONS']) || !is_array($this->field['OPTIONS']))
		{
			throw new \InvalidArgumentException("Select field must have OPTIONS array");
		}
	}

	protected function getBaseAttributes(): array
	{
		$attributes = parent::getBaseAttributes();

		unset($attributes['value']);

		return $attributes;
	}
}
