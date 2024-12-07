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

		$queueHistory = $this->session['QUEUE_HISTORY'];
		$currentOperatorId = 0;

		$res = ImOpenLines\Queue::getList([
			'select' => [
				'ID',
				'USER_ID'
			],
			'filter' => [
				'=CONFIG_ID' => $this->config['ID']
			],
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			],
		]);

		$queueUsers = $res->fetchAll();
		$userIds = array_map(function ($user) {
			return (int)$user['USER_ID'];
		}, $queueUsers);

		$operatorList = $this->getAvailableOperators($userIds, $currentOperator);

		$this->processingEmptyQueue($this->config['ID'], count($userIds));

		if(!empty($operatorList))
		{
			foreach ($operatorList as $operatorId)
			{
				if(!empty($queueHistory[$operatorId]))
				{
					continue;
				}

				$currentOperatorId = $operatorId;
				break;
			}

			if(empty($currentOperatorId))
			{
				$currentOperatorId = reset($operatorList);
				$queueHistory = [$currentOperatorId => true];
			}
			else
			{
				$queueHistory[$currentOperatorId] = true;
			}

			$result = [
				'RESULT' => true,
				'OPERATOR_ID' => $currentOperatorId,
				'OPERATOR_LIST' => [$currentOperatorId],
				'DATE_QUEUE' => (new DateTime())->add($queueTime . ' SECONDS'),
				'QUEUE_HISTORY' => $queueHistory,
			];
		}

		return $result;
	}
}