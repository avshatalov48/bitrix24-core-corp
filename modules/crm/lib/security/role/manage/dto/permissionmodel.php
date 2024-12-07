<?php

namespace Bitrix\Crm\Security\Role\Manage\DTO;

class PermissionModel
{
	public function __construct(
		private string $entity,
		private string $permissionCode,
		private string $field,
		private ?string $filedValue,
		private ?string $attribute = null,
		private ?array $settings = null,
	)
	{
	}

	public function entity(): string
	{
		return $this->entity;
	}

	public function permissionCode(): string
	{
		return $this->permissionCode;
	}

	public function field(): string
	{
		return $this->field;
	}

	public function filedValue(): ?string
	{
		return $this->filedValue;
	}

	public function attribute(): ?string
	{
		return $this->attribute;
	}

	public function settings(): ?array
	{
		return $this->settings;
	}

	public function isValidIdentifier(): bool
	{
		if ($this->field === '-')
		{
			return !empty($this->entity) && !empty($this->permissionCode);
		}

		return !empty($this->entity) && !empty($this->permissionCode) && !empty($this->filedValue);
	}

	public static function creteFromAppForm(array $form): self
	{
		$permissionCode = $form['permissionCode'] ?? '';
		$entity = $form['entityCode'] ?? '';
		$field = $form['stageField'] ?? '-';
		$fieldValue = $form['stageCode'] ?? null;

		$attr = $form['value'] ?? null;
		$settings = $form['settings'] ?? [];

		return new self($entity, $permissionCode, $field, $fieldValue, $attr, $settings);
	}

	/**
	 * @return self[]
	 */
	public static function creteFromAppFormBatch(array $items): array
	{
		$result = [];
		foreach ($items as $item)
		{
			$result[] = self::creteFromAppForm($item);
		}

		return $result;
	}

}