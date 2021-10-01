<?php
namespace Bitrix\ImOpenLines\Queue;

use \Bitrix\Main\Type\DateTime;

use \Bitrix\ImOpenLines;

/**
 * Class Strictly
 * @package Bitrix\ImOpenLines\Queue
 */
class Strictly extends Queue
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
		$operatorId = 0;
		$fullCountOperators = 0;

		$select = [
			'ID',
			'USER_ID'
		];

		$filter = ['=CONFIG_ID' => $this->config['ID']];
		$order = [
			'SORT' => 'ASC',
			'ID' => 'ASC'
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
			foreach ($operators as $operator)
			{
				if(!empty($queueHistory[$operator['USER_ID']]))
				{
					continue;
				}

				$operatorId = $operator['USER_ID'];

				break;
			}

			if(empty($operatorId))
			{
				$operatorId = reset($operators)['USER_ID'];
				$queueHistory = [$operatorId => true];
			}
			else
			{
				$queueHistory[$operatorId] = true;
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