<?php
namespace Bitrix\Crm\Counter;

use Bitrix\Main;

class AggregateCounter extends CounterBase
{
	/** @var string  */
	protected $code = '';
	/** @var int */
	protected $userID = 0;
	/** @var EntityCounter[]|null  */
	protected $counters = null;

	/**
	 * @param string $code Counter code.
	 * @param array $data Item data.
	 * @param int $userID User ID.
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function __construct($code, array $data, $userID = 0)
	{
		$this->code = $code;
		$this->counters  = array();
		foreach($data as $item)
		{
			$entityTypeID = (int)$item['entityTypeID'];
			$counterTypeID = (int)$item['counterTypeID'];
			$this->setUserID($userID > 0 ? $userID : \CCrmSecurityHelper::GetCurrentUserID());
			$extras = isset($item['extras']) && is_array($item['extras']) ? $item['extras'] : null;

			$this->counters[] = EntityCounterFactory::create(
				$entityTypeID,
				$counterTypeID,
				$this->getUserID(),
				$extras
			);
		}
	}

	public function getValue($recalculate = false)
	{
		$value = 0;
		foreach($this->counters as $counter)
		{
			$value += $counter->getValue($recalculate);
		}

		$currentValue = 0;
		$map = \CUserCounter::GetValues($this->userID, '**');
		if(isset($map[$this->code]))
		{
			$currentValue = (int)$map[$this->code];
		}

		if($currentValue !== $value)
		{
			\CUserCounter::Set($this->userID, $this->code, $value, '**', '', false);
		}

		return $value;
	}
	public function getCode()
	{
		return $this->code;
	}
}