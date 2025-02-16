<?php
namespace Bitrix\ImOpenLines\Session;

use Bitrix\Im\User;
use Bitrix\ImOpenLines\Chat;
use Bitrix\ImOpenLines\Config;
use Bitrix\ImOpenLines\ConfigStatistic;
use Bitrix\ImOpenLines\Connector;
use Bitrix\ImOpenLines\Debug;
use Bitrix\ImOpenLines\Mail;
use Bitrix\ImOpenLines\Model\SessionCheckTable;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\ImOpenLines\Queue;
use Bitrix\ImOpenLines\Recent;
use Bitrix\ImOpenLines\Relation;
use Bitrix\ImOpenLines\Session;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

class Update
{
	private array $updateCheckTable = [];
	private array $updateChatSession = [];
	private array $oldSessionState;
	private bool $skipRecent = false;
	private ?DateTime $updateDateCrmClose = null;
	private array $newData;
	private Session $session;
	private ?bool $isResponseOperator = null;

	public function __construct(
		Session $session
	)
	{
		$this->session = $session;
		$this->oldSessionState = $this->session->getSession();
	}

	public function setData(array $newData): self
	{
		$this->newData = $newData;

		return $this;
	}

	public function save(): bool
	{
		$this->prepareFields();

		$this->setConfig();

		$this->updateSessionDate();

		$this->closeSession();

		$this->checkVote();

		if (!empty($this->updateChatSession))
		{
			$this->session->getChat()->updateFieldData([Chat::FIELD_SESSION => $this->updateChatSession]);
		}

		$this->updateSessionCheck();

		$this->crmUpdateCloseDate();

		$this->updateStatus();

		$this->saveData();

		$this->updateRecent();

		$this->responseOperator();

		Debug::addSession($this->session,  __METHOD__, [
			'fields' => $this->newData,
			'updateCheckTable' => $this->updateCheckTable,
			'updateChatSession' => $this->updateChatSession,
			'updateDateCrmClose' => $this->updateDateCrmClose
		]);

		return true;
	}

	private function prepareFields(): void
	{
		$this->skipRecent = isset($this->newData['SKIP_RECENT']) && $this->newData['SKIP_RECENT'];
	}

	private function checkIfWaitAction(): bool
	{
		return (
				$this->session->getSessionField('WAIT_ACTION') === 'Y'
				&& $this->newData['WAIT_ACTION'] !== 'N'
			)
			||
			(
				isset($this->newData['WAIT_ACTION'])
				&& $this->newData['WAIT_ACTION'] === 'Y'
			);
	}

	private function modifyOnNotPause(): DateTime
	{
		$dateCrmClose = new DateTime();
		$dateCrmClose->add('1 DAY');
		$dateCrmClose->add($this->session->getConfig('AUTO_CLOSE_TIME').' SECONDS');

		$fullCloseTime = $this->session->getConfig('FULL_CLOSE_TIME');

		/** var DateTime */
		$dateClose = clone $this->newData['DATE_MODIFY'];

		if (
			isset($this->newData['USER_ID'])
			&& User::getInstance($this->newData['USER_ID'])->isConnector()
		)
		{
			if (
				$this->session->getSessionField('VOTE_SESSION')
				&& $this->checkIfWaitAction()
			)
			{
				$this->setStatus(Session::STATUS_WAIT_CLIENT);
				if (!empty($fullCloseTime))
				{
					$dateClose->add($fullCloseTime . ' MINUTES');
				}

				$this->updateDateCrmClose = $dateCrmClose;
			}
			else
			{
				$dateClose->add('1 MONTH');
				$this->updateCheckTable['DATE_CLOSE'] = $dateClose;
				$this->updateChatSession['WAIT_ACTION'] = "N";
				$this->session->setSessionField('WAIT_ACTION', 'N');
				$this->newData['WAIT_ACTION'] = "N";

				if (
					$this->session->getSessionField('STATUS') >= Session::STATUS_OPERATOR
					|| $this->session->getSessionField('STATUS') == Session::STATUS_ANSWER
				)
				{
					$this->updateDateCrmClose = $dateCrmClose;
				}

				if ($this->session->getSessionField('WAIT_ANSWER') === 'N')
				{
					$status = $this->session->getSessionField('STATUS') >= Session::STATUS_OPERATOR
						? Session::STATUS_CLIENT_AFTER_OPERATOR
						: Session::STATUS_CLIENT;
					$this->setStatus($status);
				}
			}
		}
		else
		{
			if (isset($this->newData['SKIP_DATE_CLOSE']))
			{
				$dateClose->add('1 MONTH');
			}
			elseif (
				(
					$this->session->getSessionField('WAIT_ANSWER') == 'Y'
					&& $this->newData['WAIT_ANSWER'] != 'N'
				)
				||
				(
					isset($this->newData['WAIT_ANSWER'])
					&& $this->newData['WAIT_ANSWER'] == 'Y'
				)
			)
			{
				$status = $this->session->getSessionField('STATUS') >= Session::STATUS_CLIENT_AFTER_OPERATOR
					? Session::STATUS_CLIENT_AFTER_OPERATOR
					: Session::STATUS_CLIENT;
				$this->setStatus($status);

				$dateClose->add('1 MONTH');

				if ($this->session->getSessionField('STATUS') >=  Session::STATUS_OPERATOR)
				{
					$this->updateDateCrmClose = $dateCrmClose;
				}
			}
			elseif ($this->checkIfWaitAction())
			{
				$this->setStatus(Session::STATUS_WAIT_CLIENT);
				if (!empty($fullCloseTime))
				{
					$dateClose->add($fullCloseTime . ' MINUTES');
				}

				$this->updateDateCrmClose = $dateCrmClose;
			}
			else
			{
				$this->setStatus(Session::STATUS_OPERATOR);
				$dateClose->add($this->session->getConfig('AUTO_CLOSE_TIME') . ' SECONDS');

				$this->updateDateCrmClose = $dateCrmClose;
			}
		}

		return $dateClose;
	}

	private function modifyOnPause(): DateTime
	{
		$dateCrmClose = new DateTime();
		$dateCrmClose->add('1 DAY');
		$dateCrmClose->add($this->session->getConfig('AUTO_CLOSE_TIME').' SECONDS');
		$fullCloseTime = $this->session->getConfig('FULL_CLOSE_TIME');
		$dateClose = clone $this->newData['DATE_MODIFY'];

		$dateCrmClose->add('6 DAY'); // 6+1 = 7
		if (
			$this->session->getSessionField('WAIT_ACTION') === 'N'
			&& isset($this->newData['USER_ID'])
			&& User::getInstance($this->newData['USER_ID'])->isConnector()
		)
		{
			$dateClose->add('1 WEEK');

			if (
				$this->session->getSessionField('STATUS') >= Session::STATUS_OPERATOR
				|| $this->session->getSessionField('STATUS') == Session::STATUS_ANSWER
			)
			{
				$this->updateDateCrmClose = $dateCrmClose;
			}

			if ($this->session->getSessionField('WAIT_ANSWER') == 'N')
			{
				$status = $this->session->getSessionField('STATUS') >= Session::STATUS_OPERATOR
					? Session::STATUS_CLIENT_AFTER_OPERATOR
					: Session::STATUS_CLIENT;
				$this->setStatus($status);
			}
		}
		else
		{
			if (isset($this->newData['SKIP_DATE_CLOSE']))
			{
				$dateClose->add('1 WEEK');
			}
			elseif (
				$this->session->getSessionField('WAIT_ANSWER') === 'Y'
				&& $this->newData['WAIT_ANSWER'] !== 'N'
				|| $this->newData['WAIT_ANSWER'] === 'Y'
			)
			{
				$dateClose->add('1 WEEK');

				$status = $this->session->getSessionField('STATUS') >= Session::STATUS_CLIENT_AFTER_OPERATOR
					? Session::STATUS_CLIENT_AFTER_OPERATOR
					: Session::STATUS_CLIENT;
				$this->setStatus($status);

				if ($this->session->getSessionField('STATUS') >=  Session::STATUS_OPERATOR)
				{
					$this->updateDateCrmClose = $dateCrmClose;
				}
			}
			elseif (
				$this->session->getSessionField('WAIT_ACTION') === 'Y'
				&& $this->newData['WAIT_ACTION'] !== 'N'
				|| $this->newData['WAIT_ACTION'] === 'Y'
			)
			{
				$this->setStatus(Session::STATUS_WAIT_CLIENT);
				if (!empty($fullCloseTime))
				{
					$dateClose->add($fullCloseTime . ' MINUTES');
				}

				$this->updateDateCrmClose = $dateCrmClose;
			}
			else
			{
				$dateClose->add('1 WEEK');

				$this->setStatus(Session::STATUS_OPERATOR);
				$this->updateDateCrmClose = $dateCrmClose;
			}
		}

		return $dateClose;
	}

	private function crmUpdateCloseDate(): void
	{
		if (
			!empty($this->updateDateCrmClose)
			&& $this->session->getSessionField('CRM_ACTIVITY_ID')
		)
		{
			$crmManager = $this->session->getCrmManager();
			if ($crmManager->isLoaded())
			{
				$crmManager->setSessionDataClose($this->updateDateCrmClose);
			}
		}
	}

	private function saveData(): void
	{
		foreach ($this->newData as $key => $value)
		{
			$this->session->setSessionField($key, $value);
		}

		$this->checkClose();

		if (isset($this->newData['MESSAGE_COUNT']))
		{
			$this->newData['MESSAGE_COUNT'] = new SqlExpression('?# + 1', 'MESSAGE_COUNT');
			ConfigStatistic::getInstance((int)$this->session->getSessionField('CONFIG_ID'))->addMessage();
		}

		if (
			$this->session->getSessionField('ID')
			&& !empty($this->newData)
		)
		{
			SessionTable::update($this->session->getSessionField('ID'), $this->newData);
		}
	}

	private function updateRecent(): void
	{
		if (
			$this->oldSessionState['STATUS'] < Session::STATUS_ANSWER
			&& $this->session->getSessionField('STATUS') >= Session::STATUS_ANSWER
			&& !$this->skipRecent
		)
		{
			$relation = new Relation($this->session->getSessionField('CHAT_ID'));
			$relation->removeAllRelations(false, [(int)$this->session->getSessionField('OPERATOR_ID')]);
			Recent::clearRecent($this->session->getSessionField('ID'));
		}
	}

	private function updateStatus(): void
	{
		if (
			isset($this->newData['SKIP_CHANGE_STATUS'])
			&& $this->newData['SKIP_CHANGE_STATUS'] === true
			&& isset($this->newData['STATUS'])
		)
		{
			unset($this->newData['STATUS']);
		}

		unset(
			$this->newData['USER_ID'],
			$this->newData['SKIP_DATE_CLOSE'],
			$this->newData['SKIP_CHANGE_STATUS'],
			$this->newData['SKIP_RECENT'],
			$this->newData['FORCE_CLOSE'],
			$this->newData['INPUT_MESSAGE']
		);

		if (
			!empty($this->newData['STATUS'])
			&& $this->session->getSessionField('STATUS') != $this->newData['STATUS'])
		{
			$this->session->chat->updateSessionStatus($this->newData['STATUS'], $this->session->getSession()['ID']);

			if (
				(int)$this->session->getSessionField('STATUS') !== Session::STATUS_OPERATOR
				&& (int)$this->newData['STATUS'] === Session::STATUS_OPERATOR
			)
			{
				$this->isResponseOperator = true;
			}
			elseif (
				(int)$this->session->getSessionField('STATUS') === Session::STATUS_OPERATOR
				&& (int)$this->newData['STATUS'] !== Session::STATUS_OPERATOR
			)
			{
				$this->isResponseOperator = false;
			}

			$sessionClose = false;
			if (
				isset($this->newData['CLOSED'])
				&& $this->newData['CLOSED'] === 'Y'
			)
			{
				$sessionClose = true;
			}

			if (
				!empty($this->session->getSessionField('SOURCE'))
				&& Connector::isLiveChat($this->session->getSessionField('SOURCE'))
				&& !empty($this->session->getSessionField('USER_CODE'))
			)
			{
				$chatEntityId = \Bitrix\ImOpenLines\Chat::parseLinesChatEntityId($this->session->getSessionField('USER_CODE'));
				if (!empty($chatEntityId['connectorChatId']))
				{
					\Bitrix\Pull\Event::add($this->session->getSessionField('USER_ID'), [
						'module_id' => 'imopenlines',
						'command' => 'sessionStatus',
						'params' => [
							'chatId' => (int)$chatEntityId['connectorChatId'],
							'sessionId' => (int)$this->session->getSessionField('ID'),
							'sessionStatus' => (int)$this->newData['STATUS'],
							'spam' => $this->session->getSessionField('SPAM') == 'Y',
							'sessionClose' => $sessionClose
						]
					]);
				}
			}
		}
	}

	private function updateSessionCheck(): void
	{
		if (
			array_key_exists('SEND_NO_ANSWER_TEXT', $this->newData)
			&& $this->newData['SEND_NO_ANSWER_TEXT'] === 'Y'
		)
		{
			$this->updateCheckTable['DATE_NO_ANSWER'] = null;
		}

		if (!empty($this->updateCheckTable))
		{
			if (
				isset($this->updateCheckTable['DATE_CLOSE'])
				&& $this->session->getSessionField('CRM_ACTIVITY_ID') > 0
				&& (
					!isset($this->newData['CLOSED'])
					|| $this->newData['CLOSED'] === 'N'
				)
			)
			{
				if (
					(
						$this->session->getSessionField('STATUS') >= Session::STATUS_ANSWER
						&& !in_array($this->session->getSessionField('STATUS'), [Session::STATUS_CLIENT, Session::STATUS_CLIENT_AFTER_OPERATOR])
					)
					||
					(
						$this->newData['STATUS'] >= Session::STATUS_ANSWER
						&& !in_array($this->newData['STATUS'], [Session::STATUS_CLIENT, Session::STATUS_CLIENT_AFTER_OPERATOR])
					)
				)
				{
					if ($this->updateCheckTable['DATE_CLOSE'])
					{
						$dateCheckClose = clone $this->updateCheckTable['DATE_CLOSE'];
					}
					else
					{
						$dateCheckClose = new DateTime();
					}
					$dateCheckClose->add($this->session->getConfig('AUTO_CLOSE_TIME').' SECONDS');
					$dateCheckClose->add('1 DAY');

					$crmManager = $this->session->getCrmManager();
					if ($crmManager->isLoaded())
					{
						$crmManager->setSessionDataClose($dateCheckClose);
					}
				}
			}

			if ($this->checkReturnSessionInQueue())
			{
				$this->updateCheckTable['DATE_QUEUE'] = new DateTime();
			}

			SessionCheckTable::update($this->session->getSessionField('ID'), $this->updateCheckTable);
		}

		foreach ($this->updateCheckTable as $key => $value)
		{
			$this->session->setSessionField('CHECK_' . $key, $value);
		}
	}

	private function responseOperator(): void
	{
		if ($this->isResponseOperator !== null)
		{
			$automaticActionMessages = new \Bitrix\ImOpenLines\AutomaticAction\Messages($this->session);

			if ($this->isResponseOperator === true)
			{
				$automaticActionMessages->setStatusResponseOperator();
			}
			elseif($this->isResponseOperator === false)
			{
				$automaticActionMessages->setStatusNotResponseOperator();
			}
		}
	}

	private function checkVote(): void
	{
		if (
			isset($this->newData['STATUS']) &&
			$this->newData['STATUS'] < Session::STATUS_WAIT_CLIENT &&
			!array_key_exists('WAIT_VOTE', $this->newData) &&
			$this->session->getSessionField('WAIT_VOTE') === 'Y'
		)
		{
			$this->newData['WAIT_VOTE'] = 'N';
		}

		if (
			array_key_exists('WAIT_VOTE', $this->newData) &&
			$this->newData['WAIT_VOTE'] !== 'Y' &&
			!array_key_exists('DATE_CLOSE_VOTE', $this->newData) &&
			$this->session->getSessionField('DATE_CLOSE_VOTE') !== null
		)
		{
			$this->newData['DATE_CLOSE_VOTE'] = null;
		}

		if (
			array_key_exists('WAIT_VOTE', $this->newData) &&
			$this->newData['WAIT_VOTE'] !== 'Y' &&
			!array_key_exists('DATE_CLOSE_VOTE', $this->newData) &&
			$this->session->getSessionField('DATE_CLOSE_VOTE') !== null
		)
		{
			$this->newData['DATE_CLOSE_VOTE'] = null;
		}

		if (
			Connector::isLiveChat($this->session->getSessionField('SOURCE')) &&
			array_key_exists('DATE_CLOSE_VOTE', $this->newData)
		)
		{
			\Bitrix\Pull\Event::add($this->session->getSessionField('USER_ID'), [
				'module_id' => 'imopenlines',
				'command' => 'sessionDateCloseVote',
				'params' => [
					'sessionId' => (int)$this->session->getSessionField('ID'),
					'dateCloseVote' => (!empty($this->newData['DATE_CLOSE_VOTE']) && $this->newData['DATE_CLOSE_VOTE'] instanceof DateTime) ? date('c', $this->newData['DATE_CLOSE_VOTE']->getTimestamp()): '',
				]
			]);
		}
	}

	private function updateSessionDate(): void
	{
		if (array_key_exists('CHECK_DATE_CLOSE', $this->newData))
		{
			$this->updateCheckTable['DATE_CLOSE'] = $this->newData['CHECK_DATE_CLOSE'];
			unset($this->newData['CHECK_DATE_CLOSE']);
		}
		elseif (
			isset($this->newData['DATE_MODIFY'])
			&& $this->newData['DATE_MODIFY'] instanceof DateTime
			&& (!isset($this->newData['CLOSED']) || $this->newData['CLOSED'] !== 'Y')
		)
		{
			if ($this->session->getSessionField('PAUSE') === 'N' || $this->newData['PAUSE'] === 'N'
			)
			{
				$dateClose = $this->modifyOnNotPause();
			}
			else
			{
				$dateClose = $this->modifyOnPause();
			}

			if ($dateClose)
			{
				$this->updateCheckTable['DATE_CLOSE'] = $dateClose;
			}
		}

		if (
			isset($this->newData['DATE_LAST_MESSAGE'])
			&& $this->newData['DATE_LAST_MESSAGE'] instanceof DateTime
			&& $this->session->getSessionField('DATE_CREATE') instanceof DateTime
		)
		{
			$this->newData['TIME_DIALOG'] = $this->newData['DATE_LAST_MESSAGE']->getTimestamp() - $this->session->getSessionField('DATE_CREATE')->getTimestamp();
		}

		if (
			!isset($this->newData['DATE_FIRST_LAST_USER_ACTION'])
			&& isset($this->newData['INPUT_MESSAGE'])
			&& $this->newData['INPUT_MESSAGE'] === true
			&& (
				empty($this->session->getSessionField('DATE_FIRST_LAST_USER_ACTION'))
				|| (
					!empty($this->newData['STATUS'])
					&& (
						(int)$this->newData['STATUS'] === Session::STATUS_CLIENT
						|| (int)$this->newData['STATUS'] === Session::STATUS_CLIENT_AFTER_OPERATOR
					)
					&& (int)$this->session->getSessionField('STATUS') > Session::STATUS_CLIENT_AFTER_OPERATOR
				)
			)
		)
		{
			$this->newData['DATE_FIRST_LAST_USER_ACTION'] = new DateTime();
		}
	}

	private function closeSession(): void
	{
		if (
			isset($this->newData['CLOSED']) &&
			$this->newData['CLOSED'] === 'Y'
		)
		{
			if ($this->session->getSessionField('SPAM') == 'Y')
			{
				$this->setStatus(Session::STATUS_SPAM);
				$this->updateChatSession['ID'] = 0;
			}
			else
			{
				$this->setStatus(Session::STATUS_CLOSE);
			}

			$this->newData['PAUSE'] = 'N';
			$this->updateChatSession['PAUSE'] = 'N';

			$this->updateCheckTable = [];

			ConfigStatistic::getInstance((int)$this->session->getSessionField('CONFIG_ID'))->addClosed()->deleteInWork();

			if (!isset($this->newData['FORCE_CLOSE']) || $this->newData['FORCE_CLOSE'] != 'Y')
			{
				$this->session->chat->close();
			}

			if (
				Connector::isLiveChat($this->session->getSessionField('SOURCE')) &&
				$this->session->getSessionField('SPAM') !== 'Y'
			)
			{
				if (
					Loader::includeModule('im') &&
					User::getInstance($this->session->getSessionField('USER_ID'))->isOnline())

				{
					\CAgent::AddAgent(
						'\Bitrix\ImOpenLines\Mail::sendOperatorAnswerAgent('.$this->session->getSessionField('ID').');',
						"imopenlines",
						"N",
						60,
						"",
						"Y",
						\ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL")
					);
				}
				else
				{
					Mail::sendOperatorAnswer($this->session->getSessionField('ID'));
				}
			}

			SessionCheckTable::delete($this->session->getSessionField('ID'));
		}
		elseif (isset($this->newData['PAUSE']))
		{
			if ($this->newData['PAUSE'] == 'Y')
			{
				$datePause = new DateTime();
				$datePause->add('1 WEEK');

				$this->updateCheckTable['DATE_CLOSE'] = $datePause;
				$this->updateCheckTable['DATE_QUEUE'] = null;
			}
		}
		elseif (isset($this->newData['WAIT_ANSWER']))
		{
			if ($this->newData['WAIT_ANSWER'] == 'Y')
			{
				$this->setStatus(Session::STATUS_SKIP);
				$this->newData['PAUSE'] = 'N';
				$this->updateChatSession['PAUSE'] = 'N';

				$dateQueue = new DateTime();
				//TODO: A bad place! Potential problem. Can change the distribution time logic by ignoring rules from the queue.
				$dateQueue->add($this->session->getConfig('QUEUE_TIME') . ' SECONDS');
				$this->updateCheckTable['DATE_QUEUE'] = $dateQueue;
			}
			else
			{
				if (
					$this->session->getSessionField('STATUS') < Session::STATUS_ANSWER &&
					$this->newData['STATUS'] < Session::STATUS_ANSWER
				)
				{
					$this->setStatus(Session::STATUS_ANSWER);
				}
				$this->newData['WAIT_ACTION'] = isset($this->newData['WAIT_ACTION'])? $this->newData['WAIT_ACTION']: 'N';
				$this->newData['PAUSE'] = 'N';
				$this->updateChatSession['WAIT_ACTION'] = $this->newData['WAIT_ACTION'];
				$this->updateChatSession['PAUSE'] = 'N';

				$this->updateCheckTable['DATE_QUEUE'] = null;
			}
		}
	}

	private function checkClose(): void
	{
		if (
			$this->session->getSessionField('STATUS') < Session::STATUS_CLOSE
			&& $this->session->getSessionField('CLOSED') === 'Y'
		)
		{
			$this->session->setSessionField('STATUS', Session::STATUS_CLOSE);
			$this->setStatus(Session::STATUS_CLOSE);
		}
	}

	private function setStatus(int $status): self
	{
		$this->newData['STATUS'] = $status;

		return $this;
	}

	private function setConfig(): self
	{
		if (isset($this->newData['CONFIG_ID']))
		{
			$configManager = new Config();
			$config = $configManager->get($this->newData['CONFIG_ID']);
			if ($config)
			{
				$this->session->setConfig($config);
				$this->updateChatSession['LINE_ID'] = $this->newData['CONFIG_ID'];
			}
			else
			{
				unset($this->newData['CONFIG_ID']);
			}
		}

		return $this;
	}

	/**
	 * Checks if the session should be returned to the queue.
	 * Returns `true` if the session should be returned to the queue, `false` otherwise.
	 *
	 * @return bool
	 */
	private function checkReturnSessionInQueue(): bool
	{
		if (
			$this->session->getConfig('CHECK_AVAILABLE') !== 'Y'
			|| $this->oldSessionState['STATUS'] != Session::STATUS_OPERATOR
			|| $this->newData['STATUS'] != Session::STATUS_CLIENT_AFTER_OPERATOR
			|| $this->session->getSessionField('PAUSE') === 'Y'
		)
		{
			return false;
		}

		$queueManager = Queue::initialization($this->session);
		return $queueManager->isOperatorActive((int)$this->session->getSessionField('OPERATOR_ID')) !== true;
	}
}
