<?php

namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Crm\Recurring\Calculator;
use Bitrix\Crm\Recurring\Entity\Dynamic;
use Bitrix\Crm\Recurring\Entity\ParameterMapper\EntityForm;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Type\Date;

abstract class DynamicEntity extends BaseEntity
{
	/**
	 * event names
	 * OnAfterCrmSmartInvoiceRecurringAdd
	 * OnAfterCrmSmartInvoiceRecurringUpdate
	 * OnAfterCrmSmartInvoiceRecurringDelete
	 * OnAfterCrmSmartInvoiceRecurringExpose
	 */

	protected ?int $entityTypeId = null;

	private function getOnRecurringEventName(string $eventName): string
	{
		if ($this->entityTypeId === \CCrmOwnerType::SmartInvoice)
		{
			return 'OnAfterCrmSmartInvoiceRecurring' . $eventName;
		}

		return 'OnAfterCrmDynamicRecurring' . $eventName;
	}

	protected function getUserFieldEntityID(): string
	{
		return $this->getControllerInstance()->getUserFieldEntityID();
	}

	protected function getControllerInstance(): Factory
	{
		if (empty(self::$controllerInstance[$this->entityTypeId]))
		{
			self::$controllerInstance[$this->entityTypeId] = Container::getInstance()->getFactory($this->entityTypeId);
		}

		return self::$controllerInstance[$this->entityTypeId];
	}

	protected function onFieldChange($name): void
	{
		if ($name === 'START_DATE' && !($this->recurringFields['START_DATE'] instanceof Date))
		{
			$startDateString = null;

			if (CheckDateTime($this->recurringFields['START_DATE']))
			{
				$startDateString = $this->recurringFields['START_DATE'];
			}
			$startDate = new Date($startDateString);

			$this->setFieldNoDemand('START_DATE', $startDate);
		}

		parent::onFieldChange($name);
	}

	protected function getNextDate(array $params, $startDate = null): ?Date
	{
		if ($params['MODE'] === Calculator::SALE_TYPE_NON_ACTIVE_DATE)
		{
			return null;
		}

		return Dynamic::getNextDate($params, $startDate);
	}

	public static function getFormMapper(array $params = []): EntityForm
	{
		return new EntityForm();
	}

	protected function calculateNextExecutionDate(?Date $startDate = null): ?Date
	{
		$nextExecution = parent::calculateNextExecutionDate($startDate);
		if ($nextExecution === null || $startDate === null)
		{
			return $nextExecution;
		}

		$today = new Date();
		if (
			$startDate->getTimestamp() > $today->getTimestamp()
			&& $nextExecution->getTimestamp() > $startDate->getTimestamp()
		)
		{
			$nextExecution = $startDate;
		}

		return $nextExecution;
	}

	protected function getOnRecurringAddEventName(): string
	{
		return $this->getOnRecurringEventName('Add');
	}

	protected function getOnRecurringUpdateEventName(): string
	{
		return $this->getOnRecurringEventName('Update');
	}

	protected function getOnRecurringDeleteEventName(): string
	{
		return $this->getOnRecurringEventName('Delete');
	}

	protected function getOnRecurringExposeEventName(): string
	{
		return $this->getOnRecurringEventName('Expose');
	}
}
