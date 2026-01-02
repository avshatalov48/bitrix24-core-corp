<?php

namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Crm\Model\Dynamic\RecurringTable;
use Bitrix\Crm\Recurring\Calculator;
use Bitrix\Crm\Recurring\Entity\ParameterMapper\EntityForm;
use Bitrix\Crm\Recurring\Manager;
use Bitrix\Crm\Timeline\DynamicRecurringController;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\Result;
use CCrmOwnerType;

final class DynamicNew extends DynamicEntity
{
	protected ?int $basedId = null;

	protected function getChangeableFields(): array
	{
		return [
			'ITEM_ID',
			'BASED_ID',
			'PARAMS',
			'IS_LIMIT',
			'LIMIT_REPEAT',
			'LIMIT_DATE',
			'START_DATE',
			'CATEGORY_ID',
		];
	}

	public static function create(): self
	{
		return new self();
	}

	private function isInitializedFields(): bool
	{
		return !empty($this->recurringFields);
	}

	public function initFields(array $fields = []): void
	{
		if ($this->isInitializedFields())
		{
			return;
		}

		$entityTypeId = (int)($fields['ENTITY_TYPE_ID'] ?? 0);
		if (
			CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId)
			|| CCrmOwnerType::SmartInvoice === $entityTypeId
		)
		{
			$this->entityTypeId = (int)$fields['ENTITY_TYPE_ID'];
		}
		else
		{
			throw new ArgumentException('Unknown entityTypeId', 'fields');
		}

		$this->setFieldsNoDemand($fields);

		$itemId = (int)($fields['ITEM_ID'] ?? 0);
		if ($itemId > 0)
		{
			$this->templateId = $itemId;
		}

		$this->onFieldChange('START_DATE');
	}

	public function setTemplateField($name, $value): void
	{
		if ($name === 'ID')
		{
			$value = (int)$value;
			if ($value > 0)
			{
				$this->basedId = $value;
				$this->setFieldNoDemand('BASED_ID', $value);
			}

			return;
		}

		if ($name !== 'ACCOUNT_NUMBER')
		{
			parent::setTemplateField($name, $value);
		}
	}

	public function save(): Result
	{
		$result = new Result();

		if ($this->recurringFields['PARAMS'][EntityForm::FIELD_MODE_NAME] === Calculator::SALE_TYPE_NON_ACTIVE_DATE)
		{
			return $result;
		}

		if ((int)$this->templateId <= 0 && empty($this->templateFields))
		{
			$result->addError(new Error('Error saving. TemplateId is empty.'));

			return $result;
		}

		if (!empty($this->templateFields))
		{
			$saveResult = $this->saveTemplate();
			if (!$saveResult->isSuccess())
			{
				return $saveResult;
			}

			$this->setFieldNoDemand('ITEM_ID', $this->templateId);
		}

		$addResult = RecurringTable::add($this->recurringFields);
		if ($addResult->isSuccess())
		{
			$this->id = $addResult->getId();
			$result->setData([
				'ID' => $this->id,
				'ITEM_ID' => $this->templateId,
				'ENTITY_TYPE_ID' => $this->entityTypeId,
			]);

			$this->onAfterSave();
		}

		return $result;
	}

	private function saveTemplate(): Result
	{
		$this->setTemplateField('IS_RECURRING', 'Y');

		$result = new Result();

		try
		{
			$item = $this->getControllerInstance()->createItem($this->templateFields);
			$saveResult = $item->save();
			$this->templateId = $item->getId();
		}
		catch (\Exception $exception)
		{
			$result->addError(new Error($exception->getMessage(), $exception->getCode()));

			return $result;
		}

		if (!$this->templateId || !$saveResult->isSuccess())
		{
			$result->addError($saveResult->getError());

			return $result;
		}

		if (!empty($this->basedId))
		{
			$this->copyProducts();
		}

		$this->setFieldNoDemand('ITEM_ID', $this->templateId);

		return $result;
	}

	private function copyProducts(): void
	{
		$factory = $this->getControllerInstance();
		$products = $factory->getItem($this->basedId)?->getProductRows()?->toArray();
		if (!$products)
		{
			return;
		}

		$item = $factory->getItem($this->templateId);
		if (!$item)
		{
			return;
		}

		$item?->setProductRowsFromArrays($products);

		// @todo maybe disable bizproc, etc?
		$factory->getUpdateOperation($item)->disableAllChecks()->launch();
	}

	private function onAfterSave(): void
	{
		$eventFields = $this->recurringFields;
		$eventFields['ID'] = $this->id;

		Manager::initCheckAgent(Manager::DYNAMIC);

		DynamicRecurringController::getInstance($this->entityTypeId)->onCreate(
			$this->templateId,
			[
				'FIELDS' => $this->templateFields,
				'RECURRING' => $eventFields,
			],
		);

		$event = new Event('crm', $this->getOnRecurringAddEventName(), $eventFields);
		$event->send();

		$entityModifyFields = [
			'TYPE' => $this->entityTypeId === CCrmOwnerType::SmartInvoice
				? CCrmOwnerType::SmartInvoiceName
				: CCrmOwnerType::CommonDynamicName,
			'ID' => $this->id,
			'ENTITY_TYPE_ID' => $this->entityTypeId,
			'FIELDS' => $eventFields,
		];
		$event = new Event('crm', self::ON_CRM_ENTITY_RECURRING_MODIFY, $entityModifyFields);
		$event->send();
	}
}
