<?php

namespace Bitrix\Crm\Component\EntityList\Grid\Panel\Action\Item;

use Bitrix\Crm\Component\EntityList\Grid\Panel\Event;
use Bitrix\Crm\Data\EntityFieldsHelper;
use Bitrix\Crm\Item;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\TransactionWrapper;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Actions;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Result;
use Bitrix\Main\Type\ArrayHelper;

final class EditAction extends \Bitrix\Main\Grid\Panel\Action\EditAction
{
	public function __construct(
		private Factory $factory,
		private Context $context,
		private ?int $categoryId = null,
		private ?array $editableFieldsWhitelist = null, // null - all fields are editable
		private array $columnNameToEditableFieldNameMap = []
	)
	{
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		$fields = $request->getPost('FIELDS');
		if (!is_array($fields))
		{
			return null;
		}

		$itemIds = array_keys($fields);
		ArrayHelper::normalizeArrayValuesByInt($itemIds);
		if (empty($itemIds))
		{
			return null;
		}

		$items = $this->factory->getItems([
			'filter' => ['@ID' => $itemIds],
		]);

		$overallResult = new Result();
		foreach ($items as $item)
		{
			$fieldsForItem = $fields[$item->getId()] ?? null;
			if (!is_array($fieldsForItem))
			{
				continue;
			}

			$result = $this->processItem($item, $fieldsForItem);
			if (!$result->isSuccess())
			{
				$overallResult->addErrors($result->getErrors());
			}
		}

		return $overallResult;
	}

	private function processItem(Item $item, array $fieldsForItemFromRequest): Result
	{
		$mappedFields = EntityFieldsHelper::replaceFieldNamesByMap(
			$fieldsForItemFromRequest,
			$this->columnNameToEditableFieldNameMap,
		);

		$filteredFields = $this->filterFieldsToEdit($mappedFields);
		if (empty($filteredFields))
		{
			return new Result();
		}

		foreach ($filteredFields as $fieldName => $value)
		{
			if ($item->hasField($fieldName))
			{
				$item->set($fieldName, $value);
			}
		}

		if (
			$item->getEntityTypeId() === \CCrmOwnerType::Lead
			&& $item->isChanged(Item::FIELD_NAME_STAGE_ID)
			&& is_string($item->remindActual(Item::FIELD_NAME_STAGE_ID))
			&& $this->factory->getStageSemantics($item->remindActual(Item::FIELD_NAME_STAGE_ID)) === PhaseSemantics::SUCCESS
		)
		{
			// we cant move a converted lead via group actions
			$item->reset(Item::FIELD_NAME_STAGE_ID);
		}

		return $this->persistUpdatedItem($item);
	}

	/**
	 * @param Array<string, mixed> $fieldsForItem
	 *
	 * @return Array<string, mixed>
	 */
	private function filterFieldsToEdit(array $fieldsForItem): array
	{
		if ($this->editableFieldsWhitelist === null)
		{
			return $fieldsForItem;
		}

		if (empty($this->editableFieldsWhitelist))
		{
			return [];
		}

		return array_filter(
			$fieldsForItem,
			fn(string $fieldName) => in_array($fieldName, $this->editableFieldsWhitelist, true),
			ARRAY_FILTER_USE_KEY
		);
	}

	private function persistUpdatedItem(Item $item): Result
	{
		$operation = $this->factory->getUpdateOperation($item, $this->context);

		return (new TransactionWrapper($operation))->launch();
	}

	public function getControl(): ?array
	{
		$control = (new Snippet())->getEditButton();

		foreach ($control['ONCHANGE'] as &$onchange)
		{
			if ($onchange['ACTION'] === Actions::CALLBACK)
			{
				$onchange['DATA'] = [
					[
						'JS' =>
							(new Event('loadEnumsAndEditSelected'))
								->addEntityTypeId($this->factory->getEntityTypeId())
								->addParam('categoryId', $this->categoryId)
								->buildJsCallback()
						,
					]
				];
			}
		}

		return $control;
	}
}
