<?php

namespace Bitrix\BizprocMobile\EntityEditor\Fields;

use Bitrix\Main\Loader;

class CrmField extends BaseField
{
	protected bool $isCrmModuleIncluded;
	protected array $allowedEntityTypes = [];
	protected string $firstTypeAbbr = '';
	protected ?array $entitiesInfo = null;

	public function __construct(array $property, mixed $value, array $documentType)
	{
		parent::__construct($property, $value, $documentType);

		$this->isCrmModuleIncluded = Loader::includeModule('crm');

		foreach ($this->getPropertyOptions() as $entityType => $flag)
		{
			if ($flag === 'Y')
			{
				$this->allowedEntityTypes[] = $entityType;
			}
		}

		if ($this->allowedEntityTypes && $this->isCrmModuleIncluded)
		{
			$this->firstTypeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeName($this->allowedEntityTypes[0]);
		}
	}

	protected function getPropertyOptions(): array
	{
		$options = $this->property['Options'] ?? null;

		$defaultOptions = [];
		if ($this->isCrmModuleIncluded)
		{
			$defaultOptions = [
				'LEAD' => 'Y',
				'CONTACT' => 'Y',
				'COMPANY' => 'Y',
				'DEAL' => 'Y',
			];
		}

		return is_array($options) ? $options : $defaultOptions;
	}

	public function getType(): string
	{
		return 'crm';
	}

	public function getConfig(): array
	{
		$config = [
			'selectorTitle' => $this->getTitle(),
			'entityIds' => [],
			'entityList' => $this->getEntityList(),
		];

		$dynamicTypeIds = [];
		if ($this->isCrmModuleIncluded)
		{
			foreach ($this->allowedEntityTypes as $entityType)
			{
				$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
				if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
				{
					$dynamicTypeIds[] = $entityTypeId;
				}
				else
				{
					$config['entityIds'][] = mb_strtolower($entityType);
				}
			}

			if ($dynamicTypeIds)
			{
				$config['entityIds'][] = 'dynamic_multiple';
				$config['provider'] = [
					'options' => [
						'dynamic_multiple' => ['dynamicTypeIds' => $dynamicTypeIds]
					],
				];
			}
		}

		return $config;
	}

	protected function convertToMobileType($value): ?string
	{
		$entityInfo = $this->getEntityInfo($value);
		if ($entityInfo)
		{
			[
				'id' => $id,
				'typeId' => $typeId,
				'isDynamic' => $isDynamic,
			] = $entityInfo;

			return $isDynamic ? ($typeId . ':' . $id) : $id;
		}

		return null;
	}

	public function convertValueToMobile(): string|array|null
	{
		if (!$this->isMultiple())
		{
			$value = is_array($this->value) && $this->value ? $this->value[array_key_first($this->value)] : $this->value;

			return $this->convertToMobileType($value);
		}

		$multipleValue = [];
		if (is_array($this->value))
		{
			foreach ($this->value as $singleValue)
			{
				$multipleValue[] = $this->convertToMobileType($singleValue);
			}
		}

		return $multipleValue;
	}

	protected function convertToWebType($value): ?string
	{
		if (
			$this->isCrmModuleIncluded
			&& is_array($value)
			&& count($value) === 2
			&& isset($value[0], $value[1])
		)
		{
			$typeAbbr = null;
			$id = 0;

			if ($value[0] === 'dynamic_multiple')
			{
				$parts = explode(':', $value[1]);
				if (count($parts) === 2)
				{
					$typeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeID($parts[0]);
					$id = $parts[1];
				}
			}
			else
			{
				$typeAbbr = \CCrmOwnerTypeAbbr::ResolveByTypeName($value[0]);
				$id = $value[1];
			}

			if (!empty($typeAbbr) && !empty($id))
			{
				if (count($this->allowedEntityTypes) === 1)
				{
					return (string)$id;
				}

				return $typeAbbr . '_' . $id;
			}
		}

		return null;
	}

	public function convertValueToWeb(): string|array|null
	{
		if (!$this->isMultiple())
		{
			$value = is_array($this->value) && $this->value ? $this->value[array_key_first($this->value)] : $this->value;

			return $this->convertToWebType($value);
		}

		$multipleValue = [];
		if (is_array($this->value))
		{
			foreach ($this->value as $singleValue)
			{
				$multipleValue[] = $this->convertToWebType($singleValue);
			}
		}

		return $multipleValue;
	}

	protected function getEntityList(): array
	{
		$list = [];

		if ($this->isCrmModuleIncluded)
		{
			$draftEntities = (array)$this->value;
			foreach ($draftEntities as $entity)
			{
				$entityInfo = $this->getEntityInfo($entity);
				if ($entityInfo)
				{
					[
						'id' => $id,
						'typeId' => $typeId,
						'isDynamic' => $isDynamic,
						'caption' => $caption,
					] = $entityInfo;

					$list[] = [
						'id' => $isDynamic ? $typeId . ':' . $id : $id,
						'title' => $caption,
						'type' => $isDynamic ? 'dynamic_multiple' : mb_strtolower(\CCrmOwnerType::ResolveName($typeId)),
					];
				}
			}
		}

		return $list;
	}

	protected function getEntityInfo($entity): ?array
	{
		if (is_string($entity) && $entity !== '' && $this->isCrmModuleIncluded)
		{
			if ($this->entitiesInfo !== null && isset($this->entitiesInfo[$entity]))
			{
				return $this->entitiesInfo[$entity];
			}

			$parts = explode('_', $entity);
			$valueType = count($parts) > 1 ? $parts[0] : $this->firstTypeAbbr;
			$valueId = count($parts) > 1 ? $parts[1] : $entity;

			$valueTypeId = \CCrmOwnerTypeAbbr::ResolveTypeID($valueType);
			$caption = \CCrmOwnerType::GetCaption($valueTypeId, $valueId);

			$entityInfo = null;
			if (!empty($caption))
			{
				$entityInfo = [
					'id' => $valueId,
					'typeId' => $valueTypeId,
					'isDynamic' => \CCrmOwnerType::isPossibleDynamicTypeId($valueTypeId),
					'caption' => $caption,
				];
			}

			$this->entitiesInfo[$entity] = $entityInfo;

			return $entityInfo;
		}

		return null;
	}
}
