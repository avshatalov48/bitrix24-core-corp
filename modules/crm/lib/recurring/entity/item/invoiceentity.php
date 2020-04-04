<?php
namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Crm\Recurring\Calculator,
	Bitrix\Crm\Recurring\Entity,
	Bitrix\Main\Type\Date,
	Bitrix\Crm\Recurring\DateType,
	Bitrix\Crm\Recurring\Entity\ParameterMapper\FirstFormInvoice;

abstract class InvoiceEntity extends BaseEntity
{
	const ON_INVOICE_RECURRING_ADD_EVENT = 'OnAfterCrmInvoiceRecurringAdd';
	const ON_INVOICE_RECURRING_UPDATE_EVENT = 'OnAfterCrmInvoiceRecurringUpdate';
	const ON_INVOICE_RECURRING_DELETE_EVENT = 'OnAfterCrmInvoiceRecurringDelete';
	const ON_INVOICE_RECURRING_EXPOSE_EVENT = 'OnAfterCrmInvoiceRecurringExpose';

	/** @var \CCrmInvoice $controllerInstance  */
	protected static $controllerInstance = null;
	protected static $ufInstance = null;

	/**
	 * @return \CCrmInvoice
	 */
	protected function getControllerInstance()
	{
		if(self::$controllerInstance === null)
		{
			self::$controllerInstance = new \CCrmInvoice(false);
		}
		return self::$controllerInstance;
	}

	protected function getUserFieldEntityID()
	{
		return \CCrmInvoice::GetUserFieldEntityID();
	}

	protected function onFieldChange($name)
	{
		if ($name === 'START_DATE')
		{
			if (!($this->recurringFields['START_DATE'] instanceof Date))
			{
				$startDateString = null;
				if (CheckDateTime($this->recurringFields['START_DATE']))
				{
					$startDateString = $this->recurringFields['START_DATE'];
				}
				$startDate = new Date($startDateString);
				$this->setFieldNoDemand('START_DATE', $startDate);
			}
		}
		elseif ($name === 'EMAIL_ID')
		{
			if ((int)$this->recurringFields['EMAIL_ID'] <= 0)
			{
				$this->setFieldNoDemand('EMAIL_ID', null);
			}
			if (empty($this->recurringFields['EMAIL_ID']))
			{
				$this->setFieldNoDemand('SEND_BILL', 'N');
			}
		}

		parent::onFieldChange($name);
	}

	protected function getNextDate(array $params, $startDate = null)
	{
		if ($params['PERIOD'] === Calculator::SALE_TYPE_NON_ACTIVE_DATE)
		{
			return null;
		}
		return Entity\Invoice::getNextDate($params, $startDate);
	}

	public static function getFormMapper(array $params = [])
	{
		return new FirstFormInvoice();
	}

	/**
	 * @param Date|null $startDate
	 *
	 * @return Date|null
	 * @throws \Bitrix\Main\ObjectException
	 */
	protected function calculateNextExecutionDate(Date $startDate = null)
	{
		$today = new Date();
		if ($startDate instanceof Date && $startDate->getTimestamp() > $today->getTimestamp())
		{
			$map = Entity\Invoice::getParameterMapper($this->calculateParameters);
			$map->fillMap($this->calculateParameters);
			if ($map->checkMatchingDate(clone $startDate))
			{
				return $startDate;
			}
		}

		return parent::calculateNextExecutionDate($startDate);
	}
}
