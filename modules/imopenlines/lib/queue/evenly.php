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

		$operators = [];
		$queueHistory = $this->session['QUEUE_HISTORY'];
		$fullCountOperators = 0;

		$select = [
			'ID',
			'USER_ID'
		];

		$filter = ['=CONFIG_ID' => $this->config['ID']];
		$order = [
			'LAST_ACTIVITY_DATE' => 'asc',
			'LAST_ACTIVITY_DATE_EXACT' => 'asc'
		];

		$res = ImOpenLines\Queue::getList([
			'select' => $select,
			'filter' => $filter,
			'order' => $order
		]);

		while($queueUser = $res->fetch())
		{
			$fullCountOperators++;
			if($this->isOperatorAvailable($queueUser['USER_ID'], $currentOperator))
			{
				$operators[$queueUser['USER_ID']] = $queueUser;
			}
		}

		$this->processingEmptyQueue($this->config['ID'], $fullCountOperators);

		if(!empty($operators))
		{
			$operatorId = reset($operators)['USER_ID'];

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