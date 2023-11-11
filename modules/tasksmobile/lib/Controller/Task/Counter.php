<?php

namespace Bitrix\TasksMobile\Controller\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Internals;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;

class Counter extends Controller
{
	public function configureActions(): array
	{
		return [
			'get' => [
				'+prefilters' => [
					new CloseSession(),
				],
			],
		];
	}

	/**
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getAction(int $userId = 0, array $counterTypes = []): array
	{
		$userId = ($userId ?: CurrentUser::get()->getId());
		$counterProvider = Internals\Counter::getInstance($userId);

		if (empty($counterTypes))
		{
			$counterTypes = [
				CounterDictionary::COUNTER_SONET_TOTAL_EXPIRED,
				CounterDictionary::COUNTER_SONET_TOTAL_COMMENTS,
				CounterDictionary::COUNTER_SONET_FOREIGN_EXPIRED,
				CounterDictionary::COUNTER_SONET_FOREIGN_COMMENTS,
				CounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS,
				CounterDictionary::COUNTER_SCRUM_FOREIGN_COMMENTS,
			];
		}
		$counters = [];
		foreach ($counterTypes as $type)
		{
			$counters[$type] = $counterProvider->get($type);
		}

		return $this->convertKeysToCamelCase($counters);
	}
}