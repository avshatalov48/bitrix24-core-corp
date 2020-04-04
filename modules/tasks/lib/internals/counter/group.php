<?php

namespace Bitrix\Tasks\Internals\Counter;

use Bitrix\Main\Application;
use Bitrix\Main\Type\Date;
use Bitrix\Tasks\Internals\Counter;

class Group
{
	private static $instances = array();
	private $groupId;

	/**
	 * @return mixed
	 */
	public function getGroupId()
	{
		return $this->groupId;
	}

	/**
	 * @param mixed $groupId
	 */
	public function setGroupId($groupId)
	{
		$this->groupId = $groupId;
	}

	private function __construct($groupId)
	{
		$this->setGroupId($groupId);
	}

	public static function getInstance($groupId)
	{
		if(!array_key_exists($groupId, self::$instances))
		{
			self::$instances[$groupId] = new self($groupId);
		}

		return self::$instances[$groupId];
	}

	private function getMap()
	{
		return array(
			//			'OPENED',
			//			'CLOSED',
			'MY_EXPIRED',
			'MY_EXPIRED_SOON',
			'MY_NOT_VIEWED',
			'MY_WITHOUT_DEADLINE',
			'ORIGINATOR_WITHOUT_DEADLINE',
			'ORIGINATOR_EXPIRED',
			'ORIGINATOR_WAIT_CTRL',
			//			'AUDITOR_EXPIRED',
			//			'ACCOMPLICES_EXPIRED',
			//			'ACCOMPLICES_EXPIRED_SOON',
			//			'ACCOMPLICES_NOT_VIEWED'
		);
	}

	public function getCounters()
	{
		$select = array();
		foreach ($this->getMap() as $key)
		{
			$select[] = "SUM({$key}) AS {$key}";
		}

		$sql = "
			SELECT
				GROUP_ID, ".join(',', $select)."
			FROM 
				b_tasks_counters 
			WHERE
				GROUP_ID = {$this->getGroupId()}
			GROUP BY 
				GROUP_ID";

		$res = Application::getConnection()->query($sql);
		$counters = $res->fetch();

		$data = array(
			'total' => array(
				'counter' => $counters['MY_EXPIRED'] +
							 $counters['MY_EXPIRED_SOON'] +
							 $counters['ORIGINATOR_EXPIRED'] +
							 $counters['ORIGINATOR_WAIT_CTRL'],
				'code' => ''
			),
			'wo_deadline' => array(
				'counter' => $counters['MY_WITHOUT_DEADLINE'],
				'code' => Counter\Type::TYPE_WO_DEADLINE
			),
			'expired' => array(
				'counter' => $counters['MY_EXPIRED'],
				'code' => Counter\Type::TYPE_EXPIRED
			),
			'expired_soon' => array(
				'counter' => $counters['MY_EXPIRED_SOON'],
				'code' => Counter\Type::TYPE_EXPIRED_CANDIDATES
			),
			'wait_ctrl' => array(
				'counter' => $counters['ORIGINATOR_WAIT_CTRL'],
				'code' => Counter\Type::TYPE_WAIT_CTRL
			),
		);

		return $data;
	}


}