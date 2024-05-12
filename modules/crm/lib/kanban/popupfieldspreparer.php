<?php

namespace Bitrix\Crm\Kanban;

class PopupFieldsPreparer
{
	private const DISABLED_FIELDS_FOR_VIEW_TYPE = [
		'PHONE',
		'EMAIL',
		'WEB',
		'IM',
	];

	private const DISABLED_FIELDS_FOR_EDIT_TYPE = [
		'ID',
		'CLOSED',
		'DATE_CREATE',
		'DATE_MODIFY',
		'COMMENTS',
		'OPPORTUNITY',
	];

	private array $fieldsSections;
	private array $disabledFields = [];
	private array $selectedFields = [];
	private ?array $popupFields = null;
	private array $fieldsWithAssignedCategories = [];
	private ?string $additionalSectionCode = null;

	public function __construct(
		private \Bitrix\Crm\Kanban\Entity $entity,
		private \CBitrixComponent $detailComponent,
		private string $viewType
	)
	{
		$this->detailComponent->prepareFieldInfos();

		$configuration = [];
		if (method_exists($this->detailComponent, 'prepareKanbanConfiguration'))
		{
			$configuration = $this->detailComponent->prepareKanbanConfiguration();
		}
		elseif (method_exists($this->detailComponent, 'prepareConfiguration'))
		{
			$configuration = $this->detailComponent->prepareConfiguration();
		}
		elseif(method_exists($this->detailComponent, 'getEditorEntityConfig'))
		{
			$configuration = $this->detailComponent->getEditorEntityConfig();
		}

		$this->fieldsSections = $this->entity->prepareFieldsSections($configuration);
	}

	public function setSelectedFields(array $selectedFields): self
	{
		$this->selectedFields = $selectedFields;

		return $this;
	}

	public function getData(): array
	{
		$result = [
			'sections' => [],
			'categories' => [],
			'options' => [],
		];

		$this->additionalSectionCode = null;
		$this->fieldsWithAssignedCategories = [];

		foreach ($this->fieldsSections as $fieldsSection)
		{
			if (!$this->isSuitableFieldsSection($fieldsSection))
			{
				continue;
			}

			$this->appendSection($result, $fieldsSection);
			$this->appendCategory($result, $fieldsSection);
			$this->appendElements($result, $fieldsSection);
		}

		$this->appendAdditionalSectionFields($result['options']);
		$this->prepareFieldOptions($result['options']);
		$this->prepareFieldSections($result['sections'], $result['options']);

		return $result;
	}

	protected function getPopupFields(): array
	{
		if (!$this->popupFields)
		{
			$this->popupFields = array_values($this->entity->getPopupFields($this->viewType));
		}

		return $this->popupFields;
	}

	protected function getDisabledFields(): array
	{
		$entityTypeName = $this->entity->getTypeName();
		$viewType = $this->viewType;
		$key = $entityTypeName . '_' . $viewType;

		if (!isset($this->disabledFields[$key]))
		{
			$kanban = Desktop::getInstance($entityTypeName);

			$disabledMoreFields = $kanban->getDisabledMoreFields();
			$disabledFields = (
				$viewType === 'view'
					? self::DISABLED_FIELDS_FOR_VIEW_TYPE
					: self::DISABLED_FIELDS_FOR_EDIT_TYPE
			);

			$this->disabledFields[$key] = array_merge($disabledMoreFields, $disabledFields);
		}

		return $this->disabledFields[$key];
	}

	protected function isSuitableFieldsSection(array $fieldsSection): bool
	{
		$type = $fieldsSection['type'] ?? null;
		if ($type !== 'section')
		{
			return false;
		}

		$name = $fieldsSection['name'] ?? null;
		if (!$name)
		{
			return false;
		}

		if (
			isset($fieldsSection['viewTypes'])
			&& !in_array($this->viewType, $fieldsSection['viewTypes'], true)
		)
		{
			return false;
		}

		return true;
	}

	protected function appendSection(array &$result, array $fieldsSection): void
	{
		$result['sections'][] = [
			'key' => $fieldsSection['name'] ?? null,
			'title' => $fieldsSection['title'] ?? null,
		];
	}

	protected function appendCategory(array &$result, array $fieldsSection): void
	{
		$name = $fieldsSection['name'] ?? null;
		$title = $fieldsSection['title'] ?? null;

		$result['categories'][] = [
			'key' => $name,
			'sectionKey' => $name,
			'title' => $title,
		];
	}

	protected function appendElements(array &$result, array $fieldsSection): void
	{
		$elements = $fieldsSection['elements'] ?? null;
		$elementsRule = $fieldsSection['elementsRule'] ?? null;

		if (is_array($elements))
		{
			$result['options'] = array_merge(
				$result['options'],
				$this->getOptionsFromSectionElements($fieldsSection['name'], $elements)
			);
		}
		elseif ($elements === null && $elementsRule)
		{
			$result['options'] = array_merge(
				$result['options'],
				$this->getOptionsFromSectionWithRule($fieldsSection['name'], $elementsRule)
			);
		}
		elseif ($elements === '*')
		{
			$this->additionalSectionCode = $fieldsSection['name'];
		}
	}

	protected function getOptionsFromSectionElements(string $categoryKey, array $elements): array
	{
		$sectionFields = array_map(static fn(array $element) => $element['name'], $elements);

		$options = [];
		foreach ($this->getPopupFields() as $popupField)
		{
			$fieldName = $popupField['NAME'] ?? null;
			if(
				!isset($fieldName)
				|| in_array($fieldName, $this->getDisabledFields(), true)
				|| in_array($fieldName, $this->fieldsWithAssignedCategories, true)
			)
			{
				continue;
			}

			if (!in_array($fieldName, $sectionFields, true))
			{
				continue;
			}

			$this->fieldsWithAssignedCategories[] = $fieldName;

			$option = [
				'categoryKey' => $categoryKey,
				'defaultValue' => false,
				'id' => $popupField['NAME'],
				'title' => $popupField['LABEL'],
			];

			$options[] = $option;
		}

		return $options;
	}

	protected function getOptionsFromSectionWithRule(string $categoryKey, string $elementsRule): array
	{
		$options = [];

		foreach ($this->getPopupFields() as $popupField)
		{
			$fieldName = $popupField['NAME'] ?? null;
			if (
				!preg_match($elementsRule, $fieldName)
				|| in_array($fieldName, $this->getDisabledFields(), true)
			)
			{
				continue;
			}

			$this->fieldsWithAssignedCategories[] = $fieldName;

			$option = [
				'categoryKey' => $categoryKey,
				'defaultValue' => false,
				'id' => $popupField['NAME'],
				'title' => $popupField['LABEL'],
			];

			$options[] = $option;
		}

		return $options;
	}

	protected function appendAdditionalSectionFields(array &$options): void
	{
		if ($this->additionalSectionCode === null)
		{
			return;
		}

		$disabledFields = $this->getDisabledFields();

		foreach ($this->getPopupFields() as $popupField)
		{
			$fieldName = $popupField['NAME'] ?? null;
			if(
				!isset($fieldName)
				|| in_array($fieldName, $disabledFields, true)
				|| in_array($fieldName, $this->fieldsWithAssignedCategories, true)
			)
			{
				continue;
			}

			$this->fieldsWithAssignedCategories[] = $fieldName;

			$option = [
				'categoryKey' => $this->additionalSectionCode,
				'id' => $popupField['NAME'],
				'title' => $popupField['LABEL'],
			];

			$options[] = $option;
		}
	}

	protected function prepareFieldOptions(array &$options): void
	{
		$defaultSelectFields = array_keys($this->entity->getDefaultAdditionalFields($this->viewType));

		foreach ($options as &$option)
		{
			$option['value'] = in_array($option['id'], $this->selectedFields, true);
			$option['defaultValue'] = in_array($option['id'], $defaultSelectFields, true);
		}
		unset($option);
	}

	protected function prepareFieldSections(array &$sections, array $fields): void
	{
		foreach ($sections as &$section)
		{
			$section['value'] = $this->isSectionHasFields($section['key'], $fields);
		}
		unset($section);
	}

	protected function isSectionHasFields(string $sectionName, array $fields): bool
	{
		foreach ($fields as $field)
		{
			if ($field['categoryKey'] === $sectionName)
			{
				return true;
			}
		}

		return false;
	}
}
