<?php
namespace Bitrix\ImOpenLines\Queue;

use \Bitrix\Main\Event,
	\Bitrix\Main\Type\DateTime;

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
	 */
	public function isOperatorAvailable($userId, $currentOperator = 0)
	{
		$result = false;

		if($this->isOperatorActive($userId) === true)
		{
			if((int)$userId !== (int)$currentOperator)
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
	 * Returns the default queue time
	 *
	 * @return int
	 */
	public function getQueueTime()
	{
		return ImOpenLines\Queue::UNDISTRIBUTED_QUEUE_TIME;
	}

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

		$operatorList = [];
		$queueHistory = [];
		$fullCountOperators = 0;

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

		while($queueUser = $res->fetch())
		{
			$fullCountOperators++;
			if($this->isOperatorAvailable($queueUser['USER_ID'], $currentOperator))
			{
				$operatorList[] = $queueUser['USER_ID'];
				$queueHistory[$queueUser['USER_ID']] = true;
			}
		}

		$this->processingEmptyQueue($this->config['ID'], $fullCountOperators);

		if(!empty($operatorList))
		{
			$result = [
				'RESULT' => true,
				'OPERATOR_ID' => 0,
				'OPERATOR_LIST' => $operatorList,
				'DATE_QUEUE' => (new DateTime())->add($queueTime . ' SECONDS'),
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

				$reasonReturn = SessionCheckTable::getById($this->session['ID'])->fetch()['REASON_RETURN'];

				//Event
				$resultOperatorQueue = self::sendEventOnBeforeSessionTransfer(
					[
						'session' => $this->session,
						'config' => $this->config,
						'chat' => $this->chat,
						'reasonReturn' => $reasonReturn,
						'newOperatorQueue' => $resultOperatorQueue
					]
				);
				// END Event

				if((bool)$resultOperatorQueue['RESULT'] === true)
				{
					if(!empty($resultOperatorQueue['OPERATOR_LIST']))
					{
						$this->chat->setOperators($resultOperatorQueue['OPERATOR_LIST'], $this->session['ID']);
						$this->chat->update(['AUTHOR_ID' => 0]);
					}
					elseif(
						!empty($resultOperatorQueue['OPERATOR_ID']) &&
						(int)$this->session['OPERATOR_ID'] !== (int)$resultOperatorQueue['OPERATOR_ID']
					)
					{
						$leaveTransfer = (string)$this->config['WELCOME_BOT_LEFT'] === Config::BOT_LEFT_CLOSE && Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()? 'N':'Y';

						$this->chat->transfer(
							[
								'FROM' => $this->session['OPERATOR_ID'],
								'TO' => $resultOperatorQueue['OPERATOR_ID'],
								'MODE' => Chat::TRANSFER_MODE_AUTO,
								'LEAVE' => $leaveTransfer
							],
							$this->sessionManager
						);
					}

					$updateSessionCheck['UNDISTRIBUTED'] = 'N';

					$result = true;
				}
				else
				{
					$this->chat->setOperators([], $this->session['ID']);
					$this->chat->update(['AUTHOR_ID' => 0]);
					$updateSessionCheck['UNDISTRIBUTED'] = 'Y';
				}

				if (
					Im\User::getInstance($this->session['OPERATOR_ID'])->isBot()
					&& $this->config['NO_ANSWER_RULE'] == Session::RULE_TEXT
					&& $this->session['SEND_NO_ANSWER_TEXT'] !== 'Y'
					&& $this->session['STATUS'] <= Session::STATUS_CLIENT
				)
				{
					$updateSessionCheck['DATE_NO_ANSWER'] = (new DateTime())->add($this->config['NO_ANSWER_TIME'] . ' SECONDS');
				}

				$updateSessionCheck['DATE_QUEUE'] = $resultOperatorQueue['DATE_QUEUE'];

				SessionCheckTable::update($this->session['ID'], $updateSessionCheck);

				$updateSession = [
					'OPERATOR_ID' => $resultOperatorQueue['OPERATOR_ID'],
					'QUEUE_HISTORY' => $resultOperatorQueue['QUEUE_HISTORY'],

					//TODO: Fix. You need to rework the status bar. Not optimal. Called in several other places.
					'OPERATOR_FROM_CRM' => 'N',

					//TODO: Fix. Hard-wired status. Potentially bad decision.
					'STATUS' => Session::STATUS_SKIP
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