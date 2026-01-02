<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Renderer;

abstract class AbstractRenderer implements RendererInterface
{
	protected const CSS_CLASS_WRAP_CONTAINER = 'docs-template-load-block-wrap';
	protected const CSS_CLASS_TITLE = 'docs-template-load-title';
	protected const CSS_CLASS_HELP = 'docs-template-field-help';

	protected string $cssClassInputContainer = 'docs-template-load-input-container';
	protected array $field;
	protected array $config;

	abstract protected function renderField(): string;

	public function __construct(array $field, array $config = [])
	{
		$this->field = $field;
		$this->config = array_merge($this->getDefaultConfig(), $config);

		$this->validateConfig();
	}

	final public function render(): string
	{
		return $this->renderContainer(fn() =>  $this->renderField());
	}

	final public function validateConfig(): void
	{
		$this->validateRequiredFields();
		$this->validateFieldType();
		$this->validateCustomRules();
	}

	protected function renderContainer(callable $contentRenderer): string
	{
		$containerClass = $this->getContainerClass();
		$containerId = $this->getContainerId();

		$html = "<div class=\"{$containerClass}\" id=\"{$containerId}\">";
		$html .= '<div class="' . self::CSS_CLASS_WRAP_CONTAINER . '">';
		$html .= $contentRenderer();
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	protected function renderLabel(): string
	{
		$html = '<label class="' . self::CSS_CLASS_TITLE . '"';

		if ($this->hasInputId())
		{
			$html .= ' for="' . htmlspecialcharsbx($this->getInputId()) . '"';
		}

		$html .= '>';
		$html .= htmlspecialcharsbx($this->field['TITLE']);

		if ($this->isRequired())
		{
			$html .= ' <span class="required">*</span>';
		}

		$html .= '</label>';

		return $html;
	}

	protected function renderHelpText(): string
	{
		if (empty($this->field['HELP_TEXT']))
		{
			return '';
		}

		return '<div class="' . self::CSS_CLASS_HELP . '">' . htmlspecialcharsbx($this->field['HELP_TEXT']) . '</div>';
	}

	protected function buildAttributes(array $additionalAttributes = []): string
	{
		$attributes = array_merge(
			$this->getBaseAttributes(),
			$this->field['ATTRIBUTES'] ?? [],
			$additionalAttributes
		);

		$html = '';
		foreach ($attributes as $name => $value)
		{
			if ($value === null || $value === false)
			{
				continue;
			}

			if ($value === true)
			{
				$html .= ' ' . htmlspecialcharsbx($name);
			}
			else
			{
				$html .= ' ' . htmlspecialcharsbx($name) . '="' . htmlspecialcharsbx($value) . '"';
			}
		}

		return $html;
	}

	protected function getBaseAttributes(): array
	{
		$attributes = [
			'name' => $this->field['UID'],
			'id' => $this->getInputId(),
		];

		if ($this->isRequired())
		{
			$attributes['required'] = true;
		}

		if ($this->hasValue())
		{
			$attributes['value'] = $this->field['VALUE'];
		}

		return $attributes;
	}

	protected function validateRequiredFields(): void
	{
		$required = [
			'UID',
			'TITLE',
			'TYPE',
		];

		foreach ($required as $field)
		{
			if (!isset($this->field[$field]) || empty($this->field[$field]))
			{
				throw new \InvalidArgumentException("Field '{$field}' is required");
			}
		}
	}

	protected function validateFieldType(): void
	{
		$supportedTypes = static::getSupportedTypes();
		if (!in_array($this->field['TYPE'], $supportedTypes, true))
		{
			throw new \InvalidArgumentException(
				"Field type '{$this->field['TYPE']}' is not supported by " . static::class
			);
		}
	}

	protected function validateCustomRules(): void
	{}

	protected function getContainerId(): string
	{
		return 'custom-field-' . htmlspecialcharsbx($this->field['UID']);
	}

	protected function getInputId(): string
	{
		return htmlspecialcharsbx($this->field['UID']);
	}

	protected function getContainerClass(): string
	{
		return 'custom-field-container';
	}

	protected function isRequired(): bool
	{
		return !empty($this->field['REQUIRED']);
	}

	protected function hasValue(): bool
	{
		return isset($this->field['VALUE']) && $this->field['VALUE'] !== '';
	}

	protected function hasInputId(): bool
	{
		return true; // all fields have UID by default
	}

	protected function getDefaultConfig(): array
	{
		return [
			'escape_html' => true,
			'add_validation_attributes' => true,
		];
	}
}
