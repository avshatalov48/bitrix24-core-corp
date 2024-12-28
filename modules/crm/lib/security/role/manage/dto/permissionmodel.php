<?php

namespace Bitrix\Crm\Security\Role\Manage\DTO;

use Bitrix\Crm\Security\Role\Model\EO_RolePermission;

class PermissionModel implements \JsonSerializable
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

	public function toArray(): array
	{
		return [
			'entity' => $this->entity,
			'permissionCode' => $this->permissionCode,
			'field' => $this->field,
			'filedValue' => $this->filedValue,
			'attribute' => $this->attribute,
			'settings' => $this->settings,
		];
	}

	public function jsonSerialize(): array
	{
		return $this->toArray();
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

	public static function createFromEntityObject(EO_RolePermission $permissionEntity): self
	{
		return new self(
			$permissionEntity->getEntity() ?? '',
			$permissionEntity->getPermType() ?? '',
			$permissionEntity->getField() ?? '',
			$permissionEntity->getFieldValue(),
			$permissionEntity->getAttr(),
			$permissionEntity->getSettings()
		);
	}

	public static function createFromDbArray(array $permissionParams): self
	{
		return new self(
			$permissionParams['ENTITY'] ?? '',
			$permissionParams['PERM_TYPE'] ?? '',
			$permissionParams['FIELD'] ?? '',
			$permissionParams['FIELD_VALUE'] ?? null,
			$permissionParams['ATTR'] ?? null,
			$permissionParams['SETTINGS'] ?? null
		);
	}
}
