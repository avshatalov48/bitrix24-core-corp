<?php
namespace Bitrix\ImOpenLines\Queue;

use \Bitrix\Main\Type\DateTime;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Model\QueueTable;

/**
 * Class Evenly
 * @package Bitrix\ImOpenLines\Queue
 */
class Evenly extends Queue
{
	/**
	 * @param int $currentOperator
	 *
	 * @return array
	 */
	public function getOperatorsQueue($currentOperator = 0): array
	{
		$queueTime = $this->getQueueTime();

		$result = [
			'RESULT' => false,
			'OPERATOR_ID' => 0,
			'OPERATOR_LIST' => [],
			'DATE_QUEUE' => (new DateTime())->add($queueTime . ' SECONDS'),
			'QUEUE_HISTORY' => [],
		];

		$queueHistory = $this->session['QUEUE_HISTORY'];

		$res = ImOpenLines\Queue::getList([
			'select' => [
				'ID',
				'USER_ID'
			],
			'filter' => [
				'=CONFIG_ID' => $this->config['ID']
			],
			'order' => [
				'LAST_ACTIVITY_DATE' => 'ASC',
				'LAST_ACTIVITY_DATE_EXACT' => 'ASC'
			],
		]);

		$userIds = [];
		$operators = [];
		while($queueUser = $res->fetch())
		{
			$userIds[] = (int)$queueUser['USER_ID'];
			$operators[(int)$queueUser['USER_ID']] = $queueUser;
		}

		$operatorList = $this->getAvailableOperators($userIds, $currentOperator);
		foreach ($operatorList as $userId)
		{
			$queueHistory[$userId] = true;
		}

		$this->processingEmptyQueue($this->config['ID'], count($userIds));

		if(!empty($operatorList))
		{
			$operatorId = reset($operatorList);

			$queueHistory[$operatorId] = true;

			if ($operators[$operatorId] > 0)
			{
				QueueTable::update($operators[$operatorId]['ID'], ['LAST_ACTIVITY_DATE' => new DateTime(), 'LAST_ACTIVITY_DATE_EXACT' => microtime(true) * 10000]);
			}

			$result = [
				'RESULT' => true,
				'OPERATOR_ID' => $operatorId,
				'OPERATOR_LIST' => [$operatorId],
				'DATE_QUEUE' => (new DateTime())->add($queueTime . ' SECONDS'),
				'QUEUE_HISTORY' => $queueHistory,
			];
		}

		return $result;
	}
}