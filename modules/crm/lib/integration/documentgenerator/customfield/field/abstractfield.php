<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field;

use Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field\Validator\ValidatorInterface;

abstract class AbstractField implements FieldInterface
{
	protected string $uid;
	protected string $type;
	protected string $title = '';
	protected bool $required = false;
	protected mixed $value = null;
	protected string $javascript = '';
	protected array $attributes = [];
	protected array $validators = [];
	protected array $dependencies = [];
	protected string $helpText = '';

	public function __construct(string $uid)
	{
		$this->uid = $uid;
		$this->type = $this->getFieldType();
	}

	abstract protected function getFieldType(): string;

	final public function getUid(): string
	{
		return $this->uid;
	}

	final public function getType(): string
	{
		return $this->type;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function setTitle(string $title): static
	{
		$this->title = $title;

		return $this;
	}

	public function isRequired(): bool
	{
		return $this->required;
	}

	public function setRequired(bool $required = true): static
	{
		$this->required = $required;

		return $this;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}

	public function setValue(mixed $value): static
	{
		$this->value = $value;

		return $this;
	}

	public function getJavascript(): string
	{
		return $this->javascript;
	}

	public function setJavascript(string $javascript): static
	{
		$this->javascript = $javascript;

		return $this;
	}

	public function getAttributes(): array
	{
		return $this->attributes;
	}

	public function setAttribute(string $key, mixed $value): static
	{
		$this->attributes[$key] = $value;

		return $this;
	}

	public function setAttributes(array $attributes): static
	{
		$this->attributes = array_merge($this->attributes, $attributes);

		return $this;
	}

	public function getValidators(): array
	{
		return $this->validators;
	}

	public function setValidator(ValidatorInterface $validator): static
	{
		$this->validators[] = $validator;

		return $this;
	}

	public function getDependencies(): array
	{
		return $this->dependencies;
	}

	public function setDependency(string $fieldId, $condition, $value): static
	{
		$this->dependencies[] = [
			'field' => $fieldId,
			'condition' => $condition,
			'value' => $value,
		];

		return $this;
	}

	public function getHelpText(): string
	{
		return $this->helpText;
	}

	public function setHelpText(string $helpText): static
	{
		$this->helpText = $helpText;

		return $this;
	}

	public function toArray(): array
	{
		$config = [
			'UID' => $this->getUid(),
			'TYPE' => $this->getType(),
			'TITLE' => $this->getTitle(),
			'REQUIRED' => $this->isRequired(),
			'VALUE' => $this->getValue(),
			'JAVASCRIPT' => $this->getJavascript(),
			'ATTRIBUTES' => $this->getAttributes(),
			'HELP_TEXT' => $this->getHelpText(),
		];

		if (!empty($this->validators))
		{
			$config['VALIDATION'] = $this->serializeValidators();
		}

		if (!empty($this->dependencies))
		{
			$config['DEPENDENCIES'] = $this->getDependencies();
		}

		return $config;
	}

	protected function serializeValidators(): array
	{
		return array_reduce(
			$this->getValidators(),
			static function (array $carry, $validator)
			{
				return array_merge($carry, $validator->toArray());
			},
			[]
		);
	}
}
