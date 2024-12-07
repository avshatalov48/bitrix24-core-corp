<?php

namespace Bitrix\ListsMobile\EntityEditor;

use Bitrix\Main\Loader;

class ElementField
{
	private array $property;
	private int $entityId = 0;
	private mixed $value = null;

	public function __construct(array $property)
	{
		$this->property = $property;
	}

	public function setEntityId(int $entityId): static
	{
		if ($entityId >= 0)
		{
			$this->entityId = $entityId;
		}

		return $this;
	}

	public function setEntityValue($value): static
	{
		$this->value = $value;

		return $this;
	}

	public function getPreparedProperty(): array
	{
		$prepared = [
			'sort' => $this->property['SORT'] ?? null,
			'name' => $this->property['FIELD_ID'],
			'title' => $this->property['NAME'],
			'editable' => $this->resolveEditable(),
			'required' => $this->property['IS_REQUIRED'] === 'Y',
			'multiple' => $this->property['MULTIPLE'] === 'Y',
			'showAlways' => true,
			'showNew' => true,
			'custom' => [
				'default' => $this->property['DEFAULT_VALUE'] ?? null,
				'isTrusted' => $this->resolveIsTrusted(),
			],
		];

		return $this->prepareTypeProperty($prepared);
	}

	private function prepareTypeProperty(array $preparedProperty): array
	{
		$property = $this->property;
		$propertyType = $property['TYPE'];

		$type = '';
		$data = [];
		switch ($propertyType)
		{
			case 'S':
				$type = 'string';
				break;
			case 'S:HTML':
				$type = 'text';
				break;
			case 'S:Date':
			case 'S:DateTime':
				$type = 'datetime';
				$data = ['enableEditInView' => true, 'enableTime' => $propertyType === 'S:DateTime'];
				break;
			case 'S:employee':
				$value = $this->value ?? $preparedProperty['custom']['default'];
				$type = 'user';
				$data = ['entityList' => $value ? Helper::getUserEntityList($value) : [], 'hasSolidBorder' => false];
				break;
			case 'S:Money':
				$type = 'money';
				break;
			case 'N':
			case 'N:Sequence':
				$type = 'number';
				$data = ['type' => $propertyType === 'N' ? 'double' : 'int', 'precision' => 12];
				break;
			case 'F':
				$value = $this->value ?? $preparedProperty['custom']['default'];

				$type = 'file';
				$data = [
					'fileInfo' => $value ? Helper::getFileInfo($value) : [],
					'mediaType' => 'file',
					'controller' => ['endpoint' => 'listsmobile.UI.FileUploader.EntityFieldUploaderController'],
					'controllerOptionNames' => ['elementId' => 'ID', 'iBlockId' => 'IBLOCK_ID'],
				];
				$preparedProperty['custom']['default'] = null;
				break;
			case 'S:DiskFile':
				if (\Bitrix\Main\ModuleManager::isModuleInstalled('disk'))
				{
					$type = 'file';
					$data = [
						'fileInfo' => [],
						'mediaType' => 'file',
						'controller' => ['endpoint' => 'disk.uf.integration.diskUploaderController'],
						'controllerOptionNames' => [],
					];
					if ($this->value !== null)
					{
						$fileInfo = Helper::getDiskFileInfo($this->value);
						$preparedProperty['custom']['value'] = $fileInfo ? array_values($fileInfo) : null;
						$preparedProperty['custom']['default'] = null;
					}
					elseif ($preparedProperty['custom']['default'] !== null)
					{
						$fileInfo = Helper::getDiskFileInfo($preparedProperty['custom']['default']);
						$preparedProperty['custom']['default'] = $fileInfo ? array_values($fileInfo) : null;
					}
				}
				break;
			case 'L':
				$type = 'select';
				$data = [
					'items' => array_map(
						static fn($enum) => ['value' => $enum['ID'], 'name' => $enum['VALUE']],
						$property['ENUM_VALUES'] ?? []
					),
				];
				break;
			case 'G':
				$preparedProperty['custom']['hasSections'] = (
					!isset($property['HAS_SECTIONS']) || $property['HAS_SECTIONS'] === 'Y'
				);

				$value = $this->value ?? $preparedProperty['custom']['default'];
				$linkIBlockId = (int)($property['LINK_IBLOCK_ID'] ?? -1);

				$type = 'entity-selector';
				$data = [
					'selectorType' => 'iblock-property-section',
					'provider' => [
						'options' => [
							'iblockId' => $linkIBlockId,
						],
					],
					'entityList' => $value ? Helper::getIBlockSectionEntityList($value, $linkIBlockId) : [],
				];
				break;
			case 'E':
			case 'E:EList':
				$value = $this->value ?? $preparedProperty['custom']['default'];

				$type = 'entity-selector';
				$data = [
					'selectorType' => 'iblock-property-element',
					'provider' => [
						'options' => [
							'iblockId' => (int)($property['LINK_IBLOCK_ID'] ?? -1),
						],
					],
					'entityList' => $value ? Helper::getIBlockElementEntityList($value) : [],
				];
				break;
			case 'S:ECrm':
				if (Loader::includeModule('crm'))
				{
					$value =
						!empty($property['FULL_CONVERTED_VALUE'])
							? $property['FULL_CONVERTED_VALUE']
							: $property['FULL_DEFAULT_CONVERTED_VALUE']
					;

					$dynamicTypeIds = [];
					$entityIds = [];
					foreach ($property['USER_TYPE_SETTINGS'] ?? [] as $entityType => $flag)
					{
						if ($entityType === 'VISIBLE')
						{
							continue;
						}

						if ($flag === 'Y')
						{
							$entityTypeId = \CCrmOwnerType::ResolveID($entityType);
							if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
							{
								$dynamicTypeIds[] = $entityTypeId;
							}
							else
							{
								$entityIds[] = mb_strtolower($entityType);
							}
						}
					}

					if ($dynamicTypeIds)
					{
						$entityIds[] = 'dynamic_multiple';
					}

					$type = 'crm';
					$data = [
						'selectorTitle' => $property['NAME'],
						'entityIds' => $entityIds,
						'entityList' => $value ? Helper::getCrmEntityList($value) : [],
					];

					if ($dynamicTypeIds)
					{
						$data['provider'] = [
							'options' => [
								'dynamic_multiple' => [
									'dynamicTypeIds' => $dynamicTypeIds,
								],
							],
						];
					}
				}
				break;
		}

		$preparedProperty['type'] = $type;
		if ($data)
		{
			$preparedProperty['data'] = $data;
		}

		return $preparedProperty;
	}

	public function isAddReadOnlyField(): bool
	{
		$settings = $this->getSettings();

		return isset($settings['ADD_READ_ONLY_FIELD']) && $settings['ADD_READ_ONLY_FIELD'] === 'Y';
	}

	public function isEditReadOnlyField(): bool
	{
		$settings = $this->getSettings();

		return isset($settings['EDIT_READ_ONLY_FIELD']) && $settings['EDIT_READ_ONLY_FIELD'] === 'Y';
	}

	public function isShowInAddForm(): bool
	{
		$settings = $this->getSettings();

		return isset($settings['SHOW_ADD_FORM']) && $settings['SHOW_ADD_FORM'] === 'Y';
	}

	public function isShowInEditForm(): bool
	{
		$settings = $this->getSettings();

		return isset($settings['SHOW_EDIT_FORM']) && $settings['SHOW_EDIT_FORM'] === 'Y';
	}

	private function getSettings(): array
	{
		return (
			isset($this->property['SETTINGS']) && is_array($this->property['SETTINGS'])
				? $this->property['SETTINGS']
				: []
		);
	}

	private function resolveEditable(): bool
	{
		if ($this->property['TYPE'] === 'N:Sequence' && isset($this->property['USER_TYPE_SETTINGS']['write']))
		{
			return $this->property['USER_TYPE_SETTINGS']['write'] === 'Y';
		}

		return (
			!(
				($this->entityId === 0 && $this->isAddReadOnlyField())
				|| ($this->entityId > 0 && $this->isEditReadOnlyField())
			)
		);
	}

	private function resolveIsTrusted(): bool
	{
		return (
			($this->property['TYPE'] === 'N:Sequence' || $this->property['FIELD_ID'] === 'ACTIVE_FROM')
			&& !$this->resolveEditable()
		);
	}
}
