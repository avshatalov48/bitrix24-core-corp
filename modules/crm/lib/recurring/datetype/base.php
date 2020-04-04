<?php
namespace Bitrix\Crm\Recurring\DateType;

use Bitrix\Main\Type\Date;

abstract class Base
{
	protected $type = null;
	protected $interval = 0;
	/** @var $startDate Date */
	protected $startDate = null;
	protected $params = [];

	const FIELD_TYPE_NAME = 'TYPE';

	public function __construct(array $params)
	{
		$this->params = $params;
		$this->startDate = new Date();
	}

	public function setType($type)
	{
		if ($this->checkType($type))
		{
			$this->type = (int)$type;
		}
	}

	public function setInterval($interval)
	{
		$this->interval = (int)$interval >= 0 ? (int)$interval : 0;
	}

	public function setStartDate(Date $startDate)
	{
		$this->startDate = clone $startDate;
	}

	abstract protected function checkType($type);
	abstract public function calculate();
}
