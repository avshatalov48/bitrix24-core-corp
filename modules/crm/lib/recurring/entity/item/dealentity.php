<?php
namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Crm\Recurring\Calculator;
use Bitrix\Crm\Recurring\Entity;
use Bitrix\Crm\Recurring\Entity\ParameterMapper;
use Bitrix\Main\Type\Date;

abstract class DealEntity extends BaseEntity
{
	const ON_DEAL_RECURRING_ADD_EVENT = 'OnAfterCrmDealRecurringAdd';
	const ON_DEAL_RECURRING_UPDATE_EVENT = 'OnAfterCrmDealRecurringUpdate';
	const ON_DEAL_RECURRING_DELETE_EVENT = 'OnAfterCrmDealRecurringDelete';
	const ON_DEAL_RECURRING_EXPOSE_EVENT = 'OnAfterCrmDealRecurringExpose';

	/** @var \CCrmDeal $controllerInstance  */
	protected static $controllerInstance = null;
	protected static $ufInstance = null;

	/**
	 * @return \CCrmDeal
	 */
	protected function getControllerInstance()
	{
		if(self::$controllerInstance === null)
		{
			self::$controllerInstance = new \CCrmDeal(false);
		}
		return self::$controllerInstance;
	}

	protected function getUserFieldEntityID()
	{
		return \CCrmDeal::GetUserFieldEntityID();
	}

	protected function prepareTimelineItem(array $currentFields, array $previousFields = array())
	{
		$preparedCurrent = array();

		if (!empty($currentFields['MODIFY_BY_ID']))
			$preparedCurrent['MODIFY_BY_ID'] = $currentFields['MODIFY_BY_ID'];

		if (!empty($currentFields['CREATED_BY_ID']))
			$preparedCurrent['CREATED_BY_ID'] = $currentFields['CREATED_BY_ID'];

		if ($currentFields["ACTIVE"] == 'Y' && $currentFields["NEXT_EXECUTION"] instanceof Date)
		{
			$preparedCurrent['VALUE'] = $currentFields["NEXT_EXECUTION"]->toString();

			$controllerFields = array(
				'FIELD_NAME' => "NEXT_EXECUTION",
				'CURRENT_FIELDS' => $preparedCurrent
			);

			if ($previousFields['NEXT_EXECUTION'] instanceof Date)
				$controllerFields['PREVIOUS_FIELDS']["VALUE"] = $previousFields['NEXT_EXECUTION']->toString();
		}
		else
		{
			$preparedCurrent['VALUE'] = $currentFields["ACTIVE"];
			$controllerFields = array(
				'FIELD_NAME' => "ACTIVE",
				'CURRENT_FIELDS' => $preparedCurrent,
				'PREVIOUS_FIELDS' => array('VALUE' => $previousFields["ACTIVE"])
			);
		}

		return $controllerFields;
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

		parent::onFieldChange($name);
	}

	protected function getNextDate(array $params, $startDate = null)
	{
		if ($params['MODE'] === Calculator::SALE_TYPE_NON_ACTIVE_DATE)
		{
			return null;
		}
		return Entity\Deal::getNextDate($params, $startDate);
	}

	public static function getFormMapper(array $params = [])
	{
		if (!empty($params['PERIOD_DEAL']))
		{
			return new ParameterMapper\FirstFormDeal();
		}

		return new ParameterMapper\SecondFormDeal();
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

		$nextExecution = parent::calculateNextExecutionDate($startDate);
		if (
			$nextExecution instanceof Date
			&& $startDate instanceof Date
			&& $startDate->getTimestamp() > $today->getTimestamp()
			&& $nextExecution->getTimestamp() > $startDate->getTimestamp()
		)
		{
			$nextExecution = $startDate;
		}

		return $nextExecution;
	}

	protected function getIgnoredTemplateFields(): array
	{
		return ['ORDER_STAGE'];
	}
}
