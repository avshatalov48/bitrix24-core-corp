<?php
namespace Bitrix\ImOpenLines\Queue;

use \Bitrix\Main\Type\DateTime;

use \Bitrix\Im;

use \Bitrix\ImOpenLines,
	\Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Model\SessionTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

/**
 * Class All
 * @package Bitrix\ImOpenLines\Queue
 */
class All extends Queue
{
	/**
	 * Check the ability to send a dialog to the operator.
	 *
	 * @param $userId
	 * @param int $currentOperator
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isOperatorAvailable($userId, $currentOperator = 0)
	{
		$result = false;

		if($this->isOperatorActive($userId))
		{
			if($userId != $currentOperator)
			{
				$freeCountChatOperator = ImOpenLines\Queue::getCountFreeSlotOperator($userId, $this->config['ID']);

				if($freeCountChatOperator > 0)
				{
					$result = true;
				}
			}
			else
			{
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param int $currentOperator
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getOperatorsQueue($currentOperator = 0)
	{
		$result = [
			'RESULT' => false,
			'OPERATOR_ID' => 0,
			'OPERATOR_LIST' => [],
			'DATE_QUEUE' => (new DateTime())->add(ImOpenLines\Queue::UNDISTRIBUTED_QUEUE_TIME . ' SECONDS'),
			'QUEUE_HISTORY' => [],
		];

		$operatorList = [];
		$queueHistory = [];

		$res = ImOpenLines\Queue::getList([
			'select' => [
				'ID',
				'USER_ID'
			],
			'filter' => [
				'=CONFIG_ID' => $this->config['ID']
			]
		]);

		while($queueUser = $res->fetch())
		{
			if($this->isOperatorAvailable($queueUser['USER_ID'], $currentOperator))
			{
				$operatorList[] = $queueUser['USER_ID'];
				$queueHistory[$queueUser['USER_ID']] = true;
			}
		}

		if(!empty($operatorList))
		{
			$result = [
				'RESULT' => true,
				'OPERATOR_ID' => 0,
				'OPERATOR_LIST' => $operatorList,
				'DATE_QUEUE' => (new DateTime())->add(ImOpenLines\Queue::UNDISTRIBUTED_QUEUE_TIME . ' SECONDS'),
				'QUEUE_HISTORY' => $queueHistory,
			];
		}

		return $result;
	}

	/**
	 * Transfer the dialog to the next operator.
	 *
	 * @param bool $manual
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function transferToNext($manual = true)
	{
		$result = false;

		ImOpenLines\Debug::addQueue($this->config['ID'], $this->session['ID'], 'start' . __METHOD__, ['manual' => $manual]);

		if($this->startLock())
		{
			if($manual)
			{
				self::sendMessageSkipAlone($this->session['CHAT_ID']);
			}
			else
			{
				$updateSessionCheck = [
					'REASON_RETURN' => ImOpenLines\Queue::REASON_DEFAULT
				];

				$resultOperatorQueue = $this->getOperatorsQueue();

				if($resultOperatorQueue['RESULT'] == true)
				{
					$this->chat->setOperators($resultOperatorQueue['OPERATOR_LIST']);
					$this->chat->update(['AUTHOR_ID' => 0]);
					$updateSessionCheck['UNDISTRIBUTED'] = 'N';

					$result = true;
				}
				else
				{
					$this->chat->setOperators();
					$this->chat->update(['AUTHOR_ID' => 0]);
					$updateSessionCheck['UNDISTRIBUTED'] = 'Y';
				}

				$updateSessionCheck['DATE_QUEUE'] = $resultOperatorQueue['DATE_QUEUE'];

				$reasonReturn = SessionCheckTable::getById($this->session['ID'])->fetch()['REASON_RETURN'];

				SessionCheckTable::update($this->session['ID'], $updateSessionCheck);

				$updateSession = [
					'OPERATOR_ID' => $resultOperatorQueue['OPERATOR_ID'],
					'QUEUE_HISTORY' => $resultOperatorQueue['QUEUE_HISTORY']
				];

				$this->sessionManager->update($updateSession);

				ImOpenLines\Debug::addQueue($this->config['ID'], $this->session['ID'],__METHOD__, ['resultOperatorQueue' => $resultOperatorQueue, 'reasonReturn' => $reasonReturn]);
			}

			$this->stopLock();
		}

		ImOpenLines\Debug::addQueue($this->config['ID'], $this->session['ID'], 'stop' . __METHOD__);

		return $result;
	}
}