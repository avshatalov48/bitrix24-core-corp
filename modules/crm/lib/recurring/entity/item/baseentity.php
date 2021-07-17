<?php
namespace Bitrix\Crm\Recurring\Entity\Item;

use Bitrix\Main\Type\Date;
use Bitrix\Crm\Recurring\Entity;

abstract class BaseEntity
{
	const ON_CRM_ENTITY_RECURRING_MODIFY = 'OnCrmRecurringEntityModify';

	protected $id = null;
	protected $templateId = null;

	/** @var array  */
	protected $recurringFields = [];

	/** @var array */
	protected $templateFields = [];

	/** @var array */
	protected $calculateParameters = [];

	/** @var \CCrmUserType */
	protected static $ufInstance = null;

	protected static $controllerInstance = null;

	protected function __construct($id = null)
	{
		$this->id = (int)$id;
	}

	abstract protected function getControllerInstance();

	abstract protected function getUserFieldEntityID();

	abstract protected function getNextDate(array $params, $startDate = null);

	abstract public function save();

	/**
	 * @param array $params
	 *
	 * @return Entity\ParameterMapper\Map
	 */
	abstract public static function getFormMapper(array $params = []);

	public function setTemplateFields(array $fields = [])
	{
		$ignoredFields = $this->getIgnoredTemplateFields();
		foreach ($fields as $name=>$value)
		{
			if (in_array($name, $ignoredFields, true))
			{
				continue;
			}
			$this->setTemplateField($name, $value);
		}
	}

	protected function getIgnoredTemplateFields(): array
	{
		return [];
	}

	public function setTemplateField($name, $value)
	{
		$this->templateFields[$name] = $value;
	}

	public function setField($name, $value)
	{
		if (in_array($name, $this->getChangeableFields()))
		{
			$this->setFieldNoDemand($name, $value);
			$this->onFieldChange($name);
		}
	}

	public function setFields(array $fields = [])
	{
		foreach ($fields as $name=>$value)
		{
			$this->setField($name, $value);
		}
	}

	public function getField($name)
	{
		return $this->recurringFields[$name];
	}

	protected function onFieldChange($name)
	{
		if ($this->isNeedRecalculationDate($name))
		{
			$startDate = $this->recurringFields['START_DATE'];
			if (!$this->recurringFields['START_DATE'] instanceof Date)
			{
				$startDate = new Date;
			}
			$nextExecution = $this->calculateNextExecutionDate($startDate);
			$this->setFieldNoDemand('NEXT_EXECUTION', $nextExecution);
			$activityValue = $this->isActive() ? 'Y' : 'N';
			$this->setFieldNoDemand('ACTIVE', $activityValue);
			if ($activityValue === 'N')
			{
				$this->setFieldNoDemand('NEXT_EXECUTION', null);
			}
		}
	}

	protected function isNeedRecalculationDate($fieldName)
	{
		return in_array($fieldName, ['PARAMS', 'IS_LIMIT', 'LIMIT_REPEAT', 'LIMIT_DATE', 'START_DATE']);
	}

	protected function setFieldsNoDemand(array $fields)
	{
		foreach ($fields as $ket=>$value)
		{
			$this->setFieldNoDemand($ket, $value);
		}
	}

	protected function setFieldNoDemand($name, $value)
	{
		if ($name === 'PARAMS')
		{
			$value = is_array($value) ? $value : [];
			$this->calculateParameters = $this->formatCalculateParameters($value);
		}

		$this->recurringFields[$name] = $value;
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	protected function formatCalculateParameters(array $params = [])
	{
		$mapper = static::getFormMapper($params);
		$mapper->fillMap($params);
		return $mapper->getFormattedMap();
	}

	/**
	 * @return array
	 */
	protected function getChangeableFields()
	{
		return [];
	}

	protected function getCalculateParameter($name)
	{
		return $this->calculateParameters[$name];
	}

	/**
	 * @return \CCrmUserType
	 */
	protected function getUserFieldInstance()
	{
		if(self::$ufInstance === null)
		{
			global $USER_FIELD_MANAGER;
			self::$ufInstance = new \CCrmUserType($USER_FIELD_MANAGER, $this->getUserFieldEntityID());
		}
		return self::$ufInstance;
	}

	protected function isActive()
	{
		if ($this->recurringFields['NEXT_EXECUTION'] instanceof Date)
		{
			$nextTimeStamp = $this->recurringFields['NEXT_EXECUTION']->getTimestamp();
		}
		else
		{
			return false;
		}

		$today = new Date();
		if ($today->getTimestamp() > $nextTimeStamp)
		{
			return false;
		}

		switch ($this->recurringFields['IS_LIMIT'])
		{
			case Entity\Base::LIMITED_BY_TIMES:
				return (int)$this->recurringFields['LIMIT_REPEAT'] > (int)$this->recurringFields['COUNTER_REPEAT'];
			case Entity\Base::LIMITED_BY_DATE:
				$endTimeStamp = ($this->recurringFields['LIMIT_DATE'] instanceof Date) ? $this->recurringFields['LIMIT_DATE']->getTimestamp() : 0;
				return $nextTimeStamp <= $endTimeStamp;
		}

		return true;
	}

	/**
	 * @param Date|null $startDate
	 *
	 * @return Date|null
	 */
	protected function calculateNextExecutionDate(Date $startDate = null)
	{
		return $this->getNextDate($this->calculateParameters, $startDate);
	}
}
