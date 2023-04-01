<?php

namespace Bitrix\Crm\FieldSet;

use Bitrix\Crm\WebForm;
use CCrmOwnerType;

class Item
{
	private $id = 0;

	private $code = '';

	private $entityTypeId = CCrmOwnerType::Undefined;

	private $clientEntityTypeId = CCrmOwnerType::Undefined;

	private $requisitePresetId = null;

	private $isSystem = false;

	private $fields = [];

	public function getTitle(): string
	{
		return CCrmOwnerType::resolveName($this->entityTypeId)
			. ' / '
			. CCrmOwnerType::resolveName($this->clientEntityTypeId)
			. ($this->getId() ? ' #' . $this->getId() : '')
		;
	}

	public function setFields(array $fields): self
	{
		$this->fields = $this->filterFields($fields, true);
		return $this;
	}

	public function getFields(): array
	{
		return $this->filterFields($this->fields);
	}

	public function setId($id): self
	{
		$this->id = $id;
		return $this;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setEntityTypeId($typeId): self
	{
		$this->entityTypeId = $typeId;
		return $this;
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	public function setClientEntityTypeId($typeId): self
	{
		$this->clientEntityTypeId = $typeId;
		return $this;
	}

	public function getClientEntityTypeId(): int
	{
		return $this->clientEntityTypeId;
	}

	public function getRequisitePresetId(): ?int
	{
		return $this->requisitePresetId;
	}

	public function setRequisitePresetId($id): self
	{
		$this->requisitePresetId = $id;
		return $this;
	}

	public function setSystem(bool $mode): self
	{
		$this->isSystem = $mode;
		return $this;
	}

	public function isSystem(): bool
	{
		return $this->isSystem;
	}

	public function setCode(?string $code): self
	{
		$this->code = $code;
		return $this;
	}

	public function getCode(): ?string
	{
		return $this->code;
	}

	public function getOptions(): array
	{
		return [
			'id' => $this->getId(),
			'code' => $this->getCode(),
			'title' => $this->getTitle(),
			'fields' => $this->getFields(),
			'entityTypeId' => $this->getEntityTypeId(),
			'clientEntityTypeId' => $this->getClientEntityTypeId(),
			'requisite' => [
				'presetId' => $this->getRequisitePresetId(),
			],
		];
	}

	public function setOptions(array $options): self
	{
		$this->setId((int)($options['id'] ?? 0));
		$this->setEntityTypeId((int)($options['entityTypeId'] ?? 0));
		$this->setClientEntityTypeId((int)($options['clientEntityTypeId'] ?? 0));
		$this->setRequisitePresetId((int)($options['presetId'] ?? 0));

		// should be last call!
		$this->setFields($options['fields'] ?? []);

		return $this;
	}

	private function filterFields(array $fields, bool $isSet = false): array
	{
		if (!$fields)
		{
			return [];
		}

		$fieldNames = [];

		$hiddenTypes = [
			\CCrmOwnerType::SmartDocument,
			WebForm\EntityFieldProvider::TYPE_VIRTUAL,
		];
		$entityFields = WebForm\EntityFieldProvider::getFieldsTree($hiddenTypes, $this->getRequisitePresetId());
		$fields = array_map(
			function (array $field) use ($isSet, $hiddenTypes)
			{
				$name = $field['name'] ?? '';
				$fieldData = WebForm\EntityFieldProvider::getField($name, $hiddenTypes, $this->requisitePresetId);

				$label = $field['label'] ?? '';
				$mainLabel = $fieldData['caption'] ?? '';
				$label = ($label === '' || $label === $mainLabel) ? '' : $label;
				if (!$isSet && $label === '')
				{
					$label = $mainLabel;
				}

				$data = [
					'name' => $name,
					'label' => $label,
					'required' => (bool)($field['required'] ?? true),
					'multiple' => (bool)($field['multiple'] ?? false),
				];

				if (!$isSet && $fieldData)
				{
					$entityTypeId = mb_strpos($fieldData['entity_field_name'], 'RQ_') === 0
						? \CCrmOwnerType::Requisite
						: \CCrmOwnerType::resolveID($fieldData['entity_name']);

					$data['editing'] = [
						'entityTypeId' => $entityTypeId,
					];
				}

				return $data;
			},
			$fields
		);

		if ($this->entityTypeId)
		{
			$typeName = CCrmOwnerType::resolveName($this->entityTypeId);
			$fieldNames = array_merge(
				$fieldNames,
				array_column(
					$entityFields[$typeName]['FIELDS'] ?? [],
					'name'
				)
			);
		}
		if ($this->clientEntityTypeId)
		{
			$typeName = CCrmOwnerType::resolveName($this->clientEntityTypeId);
			$fieldNames = array_merge(
				$fieldNames,
				array_column(
					$entityFields[$typeName]['FIELDS'] ?? [],
					'name'
				)
			);
		}

		if ($this->getRequisitePresetId())
		{
			$rqPresetFields = WebForm\Requisite::instance()->getPreset($this->getRequisitePresetId())['fields'] ?? null;
			if ($rqPresetFields)
			{
				if ($this->clientEntityTypeId)
				{
					$entityTypeName = \CCrmOwnerType::resolveName($this->clientEntityTypeId);
					$prefix = "{$entityTypeName}_";
					$prefixRq = "{$entityTypeName}_RQ_";

					foreach ($rqPresetFields as &$field)
					{
						if (mb_strpos($field['name'], $prefixRq) !== 0)
						{
							if (mb_strpos($field['name'], 'RQ_') !== 0)
							{
								$field['name'] = 'RQ_'. $field['name'];
							}

							$field['name'] = $prefix . $field['name'];
						}
					}
				}

				$fieldNames = array_merge(
					$fieldNames,
					array_column($rqPresetFields, 'name')
				);
			}
		}

		$fields = array_filter(
			$fields,
			function (array $field) use ($fieldNames)
			{
				return in_array($field['name'], $fieldNames);
			}
		);

		ksort($fields);
		return array_values($fields);
	}
}
