<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\AutomatedSolution\Entity\AutomatedSolutionTable;
use Bitrix\Crm\UI\EntitySelector;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\UI\Filter\Type;

final class AutomatedSolutionDataProvider extends \Bitrix\Main\Filter\EntityDataProvider
{
	private Settings $settings;
	private Entity $entity;

	public function __construct(string $id)
	{
		$this->settings = new Settings(['ID' => $id]);
		$this->entity = AutomatedSolutionTable::getEntity();
	}

	public function getSettings(): Settings
	{
		return $this->settings;
	}

	public function prepareFields(): array
	{
		return [
			$this->createField(
				'ID',
				[
					'type' => mb_strtolower(Type::NUMBER),
					'name' => $this->getFieldCaption('ID'),
				],
			),
			$this->createField(
				'TITLE',
				[
					'type' => mb_strtolower(Type::STRING),
					'name' => $this->getFieldCaption('TITLE'),
					'default' => true,
				]
			),
			$this->createField(
				'CREATED_BY',
				[
					'type' => mb_strtolower(Type::ENTITY_SELECTOR),
					'name' => $this->getFieldCaption('CREATED_BY'),
					'default' => true,
					'partial' => true,
				],
			),
			$this->createField(
				'CREATED_TIME',
				[
					'type' => mb_strtolower(Type::DATE),
					'name' => $this->getFieldCaption('CREATED_TIME'),
					'default' => true,
				],
			),
			$this->createField(
				'UPDATED_BY',
				[
					'type' => mb_strtolower(Type::ENTITY_SELECTOR),
					'name' => $this->getFieldCaption('UPDATED_BY'),
					'default' => true,
					'partial' => true,
				],
			)
		];
	}

	private function getFieldCaption(string $fieldName): ?string
	{
		if ($this->entity->hasField($fieldName))
		{
			return $this->entity->getField($fieldName)->getTitle();
		}

		return null;
	}

	public function prepareFieldData($fieldID)
	{
		if (in_array($fieldID, ['CREATED_BY', 'UPDATED_BY'], true))
		{
			return $this->getUserEntitySelectorParams(
				EntitySelector::CONTEXT,
				[
					'fieldName' => $fieldID,
				],
			);
		}

		return null;
	}

	public function prepareListFilterParam(array &$filter, $fieldID)
	{
		if ($fieldID === 'TITLE')
		{
			$value = (string)($filter[$fieldID] ?? '');
			$this->addTitleSubstringFilter($filter, $value);
			unset($filter[$fieldID]);
		}
	}

	public function prepareFilterValue(array $rawFilterValue): array
	{
		$filterValue = parent::prepareFilterValue($rawFilterValue);

		$this->applySearchString($filterValue);

		return $filterValue;
	}

	private function applySearchString(array &$filterValue): void
	{
		$searchString = (string)($filterValue['FIND'] ?? null);

		$this->addTitleSubstringFilter($filterValue, $searchString);
	}

	private function addTitleSubstringFilter(array &$filter, string $value): void
	{
		$value = trim($value);
		if ($value !== '')
		{
			$filter['?TITLE'] = $value;
		}
	}
}
