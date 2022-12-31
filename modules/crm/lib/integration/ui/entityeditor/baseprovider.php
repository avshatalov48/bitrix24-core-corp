<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor;

use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Display\Field;

abstract class BaseProvider extends \Bitrix\UI\EntityEditor\BaseProvider
{
	protected const USER_PROVIDER_CONTEXT = 'crm';

	/** @var int */
	private $entityId;

	/** @var array */
	private $params;

	/** @var SupportsEditorProvider */
	private $component;

	private $displayItem;

	public function __construct(int $entityId = 0, array $params = [])
	{
		$this->entityId = max($entityId, 0);
		$this->params = $params;

		$this->initializeComponent();
		$this->initializeDisplayItem();
	}

	public function getEntityId(): int
	{
		return $this->entityId;
	}

	protected function getParams(): array
	{
		return $this->params;
	}

	abstract protected function getComponentName(): string;

	private function initializeComponent(): void
	{
		$componentName = $this->getComponentName();
		$componentClass = \CBitrixComponent::includeComponentClass($componentName);

		$this->component = new $componentClass();
		$this->component->initComponent($componentName);
		$this->component->initializeParams($this->getParams());
		$this->component->setEntityID($this->getEntityId());
		$this->component->initializeData();
	}

	private function getEntityTypeId(): int
	{
		return \CCrmOwnerType::ResolveID($this->getEntityTypeName());
	}

	private function flattenConfigToFieldNames(array $entityConfig): array
	{
		$fields = [];

		if (!empty($entityConfig))
		{
			if ($entityConfig[0]['type'] === 'section')
			{
				$entityConfig = [
					[
						'elements' => $entityConfig,
					],
				];
			}

			foreach ($entityConfig as $column)
			{
				if (!empty($column['elements']) && is_array($column['elements']))
				{
					foreach ($column['elements'] as $section)
					{
						if (!empty($section['elements']) && is_array($section['elements']))
						{
							foreach ($section['elements'] as $field)
							{
								$fields[] = $field['name'];
							}
						}
					}
				}
			}
		}

		return $fields;
	}

	protected function createField(string $fieldName, Collection $fieldsCollection): Field
	{
		$field = $fieldsCollection->getField($fieldName);
		if (!$field)
		{
			throw new \DomainException("Field: {$fieldName} not found.");
		}

		return
			Field::createByType($field->getType(), $field->getName())
				->setTitle($field->getTitle())
		;
	}

	private function getPreparedItem(): \Bitrix\Crm\Item
	{
		$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
		if (!$factory)
		{
			throw new \DomainException(sprintf('Factory not found for {%s}', $this->getEntityTypeName()));
		}

		$item = $factory->getItem($this->getEntityId());
		if ($item)
		{
			return $item;
		}

		return $factory->createItem();
	}

	private function initializeDisplayItem(): void
	{
		$item = $this->getPreparedItem();

		$entityTypeId = $this->getEntityTypeId();
		$fieldsCollection = Container::getInstance()
			->getFactory($this->getEntityTypeId())
			->getFieldsCollection();

		$entityConfig = $this->component->prepareConfiguration();
		// ToDo multiple fields
		// $fieldsInfo = $this->component->prepareFieldInfos();
		$displayFieldNames = $this->flattenConfigToFieldNames($entityConfig);
		$displayFieldNames = array_diff($displayFieldNames, [
			'OPPORTUNITY_WITH_CURRENCY',
			'POST', // contact
			'PHONE', // contact
			'EMAIL', // contact
			'WEB', // contact
			'IM', // contact
			'LINK', // contact
			'COMPANY', // contact
			'ADDRESS', // contact
			'REQUISITES', // contact
			'EXPORT', // contact
			'CLIENT',
			'OBSERVER',
			'UTM',
			'PRODUCT_ROW_SUMMARY',
			'RECURRING',
		]);

		$displayFields = [];
		foreach ($displayFieldNames as $displayFieldName)
		{
			$displayFields[$displayFieldName] =
				$this
					->createField($displayFieldName, $fieldsCollection)
					->setContext(Field::MOBILE_CONTEXT);
		}

		$itemId = $item->getId();
		$this->displayItem =
			(new Display($entityTypeId, $displayFields))
				->setItems([$itemId => $item])
				->getValues($itemId);
	}

	public function getGUID(): string
	{
		return $this->component->getDefaultGuid();
	}

	public function getEntityFields(): array
	{
		$fields = $this->component->prepareFieldInfos();
		$fieldMap = array_column($fields, null, 'name');

		foreach ($this->displayItem as $name => $displayField)
		{
			if (isset($displayField['config']))
			{
				if (!is_array($fieldMap[$name]['data']))
				{
					$fieldMap[$name]['data'] = [];
				}

				$fieldMap[$name]['data'] = array_merge($fieldMap[$name]['data'], $displayField['config']);
			}
		}

		return array_values($fieldMap);
	}

	public function getEntityConfig(): array
	{
		return $this->component->prepareConfiguration();
	}

	public function getEntityData(): array
	{
		$fields = $this->component->prepareEntityData();

		foreach ($this->displayItem as $name => $field)
		{
			$fields[$name] = $field['value'];
		}

		return $fields;
	}

	public function getEntityControllers(): array
	{
		return $this->component->prepareEntityControllers();
	}
}
