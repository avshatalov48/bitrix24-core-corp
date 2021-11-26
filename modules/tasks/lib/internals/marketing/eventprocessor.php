<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Internals\Marketing;

use Bitrix\Main\Web\Json;
use Bitrix\Tasks\Util\Type\DateTime;

class EventProcessor
{
	private const LIMIT = 50;

	public function __construct()
	{

	}

	/**
	 * @throws Exception\UnknownEventException
	 */
	public function proceed()
	{
		$queue = $this->getQueue();

		foreach ($queue as $event)
		{
			$res = (new EventManager($event['USER_ID']))->execute($event['EVENT'], $event['PARAMS']);
			if ($res)
			{
				$this->markExecuted($event['ID']);
			}
		}
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getQueue(): array
	{
		$res = MarketingTable::getList([
			'filter' => [
				'=DATE_EXECUTED' => 0,
				'<DATE_SHEDULED' => DateTime::getCurrentTimestamp(),
			],
			'limit' => $this->getLimit(),
		]);

		$queue = [];
		while ($row = $res->fetch())
		{
			$queue[] = [
				'ID' => (int) $row['ID'],
				'USER_ID' => (int) $row['USER_ID'],
				'EVENT' => $row['EVENT'],
				'PARAMS' => !empty($row['PARAMS']) ? Json::decode($row['PARAMS']) : null,
				'DATE_SHEDULED' => $row['DATE_SHEDULED'],
			];
		}

		return $queue;
	}

	/**
	 * @param int $id
	 */
	private function markExecuted(int $id)
	{
		MarketingTable::update($id, [
			'DATE_EXECUTED' => DateTime::getCurrentTimestamp(),
		]);
	}

	/**
	 * @return int
	 */
	private function getLimit(): int
	{
		$limit = \COption::GetOptionString("tasks", "tasksMarketingLimit", "");
		if ($limit === "")
		{
			$limit = self::LIMIT;
		}
		return (int)$limit;
	}
}