<?php

namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Crm\Automation\Starter;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\RecurringTable;
use Bitrix\Crm\Recurring\Calculator;
use Bitrix\Crm\Recurring\Entity;
use Bitrix\Crm\Recurring\Manager;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Operation\CopyResult;
use Bitrix\Crm\Timeline\DynamicRecurringController;
use Bitrix\Main;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use CCrmBizProcEventType;
use CCrmBizProcHelper;
use CCrmOwnerType;
use Exception;

class DynamicExist extends DynamicEntity
{
	private array $previousRecurringFields = [];
	private ?Item $templateItem = null;

	public function expose(bool $recalculate = false): Result
	{
		$result = new Result();
		if ($this->isChanged())
		{
			$result->addError(new Error('Error exposing. Recurring item was changed. Need to save changes before exposing.'));

			return $result;
		}

		$exposeResult = $this->addExposingItem();

		if ($exposeResult->isSuccess())
		{
			$newItemId = $exposeResult->getId();
			$result->setData(['NEW_ITEM_ID' => $newItemId]);

			$this->onAfterExpose($newItemId, $exposeResult->getData()['item'] ?? []);

			$this->setFieldNoDemand('COUNTER_REPEAT', (int)$this->recurringFields['COUNTER_REPEAT'] + 1);
			$this->setFieldNoDemand('LAST_EXECUTION', new Date());

			if ($recalculate)
			{
				$this->setFieldNoDemand('NEXT_EXECUTION', $this->calculateNextExecutionDate());
				if ($this->isActive())
				{
					$this->setFieldNoDemand('ACTIVE', 'Y');
				}
				else
				{
					$this->deactivate();
				}
			}

			$this->save();
		}
		else
		{
			$result->addErrors($exposeResult->getErrors());
		}

		return $result;
	}

	private function isChanged(): bool
	{
		return !empty($this->previousRecurringFields);
	}

	protected function addExposingItem(): AddResult
	{
		$result = new AddResult();

		$factory = $this->getControllerInstance();
		$this->prepareTemplateItemBeforeExpose($factory);

		try
		{
			$exposeResult = $this->copyTemplateItem($factory);

			if ($exposeResult->isSuccess())
			{
				$item = $exposeResult->getCopy();
				$result->setId($item->getId());
				$result->setData(['item' => $item]);
			}
			else
			{
				$result->addErrors($exposeResult->getErrors());
			}
		}
		catch (SystemException $exception)
		{
			$result->addError(new Error($exception->getMessage(), $exception->getCode()));
		}

		return $result;
	}

	private function prepareTemplateItemBeforeExpose(Factory $factory): void
	{
		$this->templateItem->setIsRecurring(false);

		if ($factory->isStagesEnabled())
		{
			$this->templateItem->setStageId($this->getDefaultStageId($factory));
		}
		$this->templateItem->setBegindate($this->calculateBeginDate() ?? new Date());
		$this->templateItem->setClosedate($this->calculateCloseDate());
	}

	private function getDefaultStageId(Factory $factory): ?string
	{
		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');

		return $fieldRepository->getDefaultStageIdResolver($factory->getEntityTypeId())();
	}

	protected function calculateBeginDate(): ?Date
	{
		$beginDateType = (int)$this->getCalculateParameter('BEGINDATE_TYPE');
		if ($beginDateType !== Entity\Base::CALCULATED_FIELD_VALUE)
		{
			return null;
		}

		return Entity\Dynamic::getNextDate([
			'MODE' => Manager::MULTIPLY_EXECUTION,
			'MULTIPLE_TYPE' => Calculator::SALE_TYPE_CUSTOM_OFFSET,
			'MULTIPLE_CUSTOM_TYPE' => (int)$this->getCalculateParameter('OFFSET_BEGINDATE_TYPE'),
			'MULTIPLE_CUSTOM_INTERVAL_VALUE' => (int)$this->getCalculateParameter('OFFSET_BEGINDATE_VALUE'),
		]);
	}

	protected function calculateCloseDate(): ?Date
	{
		$closeDateType = (int)$this->getCalculateParameter('CLOSEDATE_TYPE');
		if ($closeDateType !== Entity\Base::CALCULATED_FIELD_VALUE)
		{
			return null;
		}

		return Entity\Dynamic::getNextDate([
			'MODE' => Manager::MULTIPLY_EXECUTION,
			'MULTIPLE_TYPE' => Calculator::SALE_TYPE_CUSTOM_OFFSET,
			'MULTIPLE_CUSTOM_TYPE' => (int)$this->getCalculateParameter('OFFSET_CLOSEDATE_TYPE'),
			'MULTIPLE_CUSTOM_INTERVAL_VALUE' => (int)$this->getCalculateParameter('OFFSET_CLOSEDATE_VALUE'),
		]);
	}

	private function copyTemplateItem(Factory $factory): CopyResult
	{
		return $factory
			->getCopyOperation($this->templateItem)
			->disableCheckAccess()
			->disableCheckFields()
			->disableSaveToTimeline()
			->launch()
		;
	}

	protected function setFieldNoDemand($name, $value): void
	{
		if (!array_key_exists($name, $this->previousRecurringFields))
		{
			$this->previousRecurringFields[$name] = $this->recurringFields[$name];
		}

		parent::setFieldNoDemand($name, $value);
	}

	public function setTemplateItem(Item $item): self
	{
		$this->templateItem = clone $item;

		return $this;
	}

	protected function getChangeableFields(): array
	{
		return [
			'PARAMS',
			'ACTIVE',
			'IS_LIMIT',
			'LIMIT_REPEAT',
			'LIMIT_DATE',
			'START_DATE',
		];
	}

	public static function load(int $id): ?self
	{
		if ($id <= 0)
		{
			return null;
		}

		$fields = RecurringTable::getById($id)->fetch();

		if (is_array($fields))
		{
			return (new self($fields['ID']))->initFields($fields);
		}

		return null;
	}

	private function initFields(array $fields = []): self
	{
		unset($fields['ID']);

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
			throw new Main\ArgumentException('Unknown entityTypeId', 'fields');
		}

		$this->recurringFields = $fields;
		$this->templateId = $fields['ITEM_ID'];

		$params = $fields['PARAMS'] ?? [];
		$this->calculateParameters = $this->formatCalculateParameters(is_array($params) ? $params : []);

		return $this;
	}

	protected function onFieldChange($name): void
	{
		parent::onFieldChange($name);

		if ($name === 'ACTIVE')
		{
			$nextExecution = $this->calculateNextExecutionDate($this->recurringFields['START_DATE']);
			$this->setFieldNoDemand('NEXT_EXECUTION', $nextExecution);
		}
	}

	private function onAfterExpose(int $newId, Item $item): void
	{
		$entityTypeId = $this->entityTypeId;

		CCrmBizProcHelper::AutoStartWorkflows(
			$entityTypeId,
			$newId,
			CCrmBizProcEventType::Create,
			$errors,
		);

		$starter = new Starter($entityTypeId, $newId);
		$starter->runOnAdd();

		$eventParams = [
			'ID' => $this->id,
			'RECURRING_ID' => $this->templateId,
			'ITEM_ID' => $newId,
		];
		$event = new Event('crm', $this->getOnRecurringExposeEventName(), $eventParams);
		$event->send();

		$fields = $item->getCompatibleData();
		$fields['RECURRING_ID'] = $this->templateId;

		DynamicRecurringController::getInstance($entityTypeId)->onExpose(
			$newId,
			['FIELDS' => $fields],
		);
	}

	public function deactivate(): void
	{
		$this->setFieldNoDemand('ACTIVE', 'N');
		$this->setFieldNoDemand('NEXT_EXECUTION', null);
	}

	public function save(): Result
	{
		$result = new Result();

		if (!$this->isChanged())
		{
			return $result;
		}

		$changedFields = array_keys($this->previousRecurringFields);
		$updateFields = array_intersect_key($this->recurringFields, array_flip($changedFields));
		$updateResult = $this->update($updateFields);
		if (!$updateResult->isSuccess())
		{
			return $updateResult;
		}

		$this->onAfterSave($updateFields);
		$this->previousRecurringFields = [];

		return $result;
	}

	protected function update($updateFields): UpdateResult
	{
		return RecurringTable::update($this->id, $updateFields);
	}

	protected function onAfterSave(array $updateFields): void
	{
		if (!empty($this->templateFields[Item::FIELD_NAME_UPDATED_BY]))
		{
			$updateFields[Item::FIELD_NAME_UPDATED_BY] = $this->templateFields[Item::FIELD_NAME_UPDATED_BY];
		}

		$entityTypeId = $this->entityTypeId;

		DynamicRecurringController::getInstance($entityTypeId)->onModify(
			$this->templateId,
			$this->prepareTimelineItem($updateFields, $this->previousRecurringFields),
		);

		$entityModifyFields = [
			'TYPE' => $this->entityTypeId === CCrmOwnerType::SmartInvoice
				? CCrmOwnerType::SmartInvoiceName
				: CCrmOwnerType::CommonDynamicName,
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ID' => $this->id,
			'FIELDS' => $updateFields,
		];

		$event = new Main\Event('crm', static::ON_CRM_ENTITY_RECURRING_MODIFY, $entityModifyFields);
		$event->send();

		$updateFields['ID'] = $this->id;
		$updateFields['ITEM_ID'] = $this->templateId;
		$updateFields['ENTITY_TYPE_ID'] = $entityTypeId;

		$event = new Main\Event('crm', $this->getOnRecurringUpdateEventName(), $updateFields);
		$event->send();
	}

	private function prepareTimelineItem(array $currentFields, array $previousFields): array
	{
		$preparedCurrent = [];

		if (!empty($currentFields[Item::FIELD_NAME_UPDATED_BY]))
		{
			$preparedCurrent[Item::FIELD_NAME_UPDATED_BY] = $currentFields[Item::FIELD_NAME_UPDATED_BY];
		}

		if (!empty($currentFields[Item::FIELD_NAME_CREATED_BY]))
		{
			$preparedCurrent[Item::FIELD_NAME_CREATED_BY] = $currentFields[Item::FIELD_NAME_CREATED_BY];
		}

		if ($currentFields['ACTIVE'] === 'Y' && $currentFields['NEXT_EXECUTION'] instanceof Date)
		{
			$preparedCurrent['VALUE'] = $currentFields['NEXT_EXECUTION']->toString();

			$controllerFields = [
				'FIELD_NAME' => 'NEXT_EXECUTION',
				'CURRENT_FIELDS' => $preparedCurrent,
			];

			if ($previousFields['NEXT_EXECUTION'] instanceof Date)
			{
				$controllerFields['PREVIOUS_FIELDS']['VALUE'] = $previousFields['NEXT_EXECUTION']->toString();
			}
		}
		else
		{
			$preparedCurrent['VALUE'] = $currentFields['ACTIVE'];
			$controllerFields = [
				'FIELD_NAME' => 'ACTIVE',
				'CURRENT_FIELDS' => $preparedCurrent,
				'PREVIOUS_FIELDS' => ['VALUE' => $previousFields['ACTIVE']],
			];
		}

		return $controllerFields;
	}

	public function delete(): Result
	{
		$result = new Main\Result();

		try
		{
			$result = RecurringTable::delete($this->id);
		}
		catch (Exception $e)
		{
			$result->addError(new Error($e->getMessage()));
		}

		if ($result->isSuccess())
		{
			(new Event(
				'crm',
				$this->getOnRecurringDeleteEventName(),
				['ID' => $this->id],
			))->send();
		}

		return $result;
	}
}
