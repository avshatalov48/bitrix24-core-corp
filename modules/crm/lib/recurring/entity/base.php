<?php
namespace Bitrix\Crm\Recurring\Entity;

use Bitrix\Main\Type\Date,
	Bitrix\Crm\Recurring\Calculator;

abstract class Base
{
	public function __construct(){}

	const NO_LIMITED = 'N';
	const LIMITED_BY_DATE = 'D';
	const LIMITED_BY_TIMES = 'T';

	const SETTED_FIELD_VALUE = 0;
	const CALCULATED_FIELD_VALUE = 1;

	abstract public function createEntity(array $entityFields, array $recurringParams);
	abstract public function update($primary, array $data);
	abstract public function expose(array $entityFields, $recurringParams);
	abstract public function cancel($entityId, $reason = "");
	abstract public function activate($invoiceId);
	abstract public function deactivate($invoiceId);
	abstract public function getList(array $parameters = array());
	abstract public function getRuntimeTemplateField() : array;

	/**
	 * @return bool
	 */
	public function isAllowedExpose()
	{
		return true;
	}

	/**
	 * @param array $params
	 * @param null $startDate
	 *
	 * @return Date
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function getNextDate(array $params, $startDate = null)
	{
		if (!($startDate instanceof Date))
		{
			$startDate = new Date();
		}
		$instance = Calculator::getInstance();
		$instance->setStartDate($startDate);
		$instance->setParams($params);
		return $instance->calculateDate();
	}

	public static function getInstance()
	{
		return null;
	}
}
