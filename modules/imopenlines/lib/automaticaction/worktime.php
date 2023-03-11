<?php
namespace Bitrix\ImOpenLines\AutomaticAction;

use \Bitrix\Main\Type\DateTime;

use \Bitrix\ImOpenLines\Im,
	\Bitrix\ImOpenLines\Queue,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session;

/**
 * Class WorkTime
 * @package Bitrix\ImOpenLines\AutomaticAction
 */
class WorkTime
{
	/** @var Session */
	protected $sessionManager = null;
	protected $session = [];
	protected $config = [];
	/**Chat*/
	//protected $chat = null;

	/**
	 * Queue constructor.
	 * @param Session $session
	 */
	public function __construct($session)
	{
		$this->sessionManager = $session;
		$this->session = $session->getData();
		$this->config = $session->getConfig();
		//$this->chat = $session->getChat();
	}

	/**
	 * Is the current time the working time of the open line?
	 *
	 * @return bool
	 */
	public function isWorkTimeLine(): bool
	{
		$result = true;

		if ($this->config['WORKTIME_ENABLE'] != 'N')
		{
			$timezone = !empty($this->config["WORKTIME_TIMEZONE"]) ? new \DateTimeZone($this->config["WORKTIME_TIMEZONE"]) : null;
			$numberDate = new DateTime(null, null, $timezone);

			if (!empty($this->config['WORKTIME_DAYOFF']))
			{
				if (!is_array($this->config['WORKTIME_DAYOFF']))
				{
					$this->config['WORKTIME_DAYOFF'] = explode(',', $this->config['WORKTIME_DAYOFF']);
				}

				$allWeekDays = [
					'MO' => 1,
					'TU' => 2,
					'WE' => 3,
					'TH' => 4,
					'FR' => 5,
					'SA' => 6,
					'SU' => 7
				];
				$currentWeekDay = $numberDate->format('N');
				foreach($this->config['WORKTIME_DAYOFF'] as $day)
				{
					if ($currentWeekDay == $allWeekDays[$day])
					{
						$result = false;
						break;
					}
				}
			}

			if ($result && !empty($this->config['WORKTIME_HOLIDAYS']))
			{
				if (!is_array($this->config['WORKTIME_HOLIDAYS']))
				{
					$this->config['WORKTIME_HOLIDAYS'] = explode(',', $this->config['WORKTIME_HOLIDAYS']);
				}

				$currentDay = $numberDate->format('d.m');
				foreach($this->config['WORKTIME_HOLIDAYS'] as $holiday)
				{
					if ($currentDay == $holiday)
					{
						$result = false;
						break;
					}
				}
			}

			if ($result)
			{
				$currentTime = $numberDate->format('G.i');

				if (!($currentTime >= $this->config['WORKTIME_FROM'] && $currentTime <= $this->config['WORKTIME_TO']))
				{
					$result = false;
				}
			}
		}

		return $result;
	}

	/**
	 * Checking that the operator is working.
	 *
	 * @param bool $finish
	 * @param bool $vote
	 * @return bool
	 */
	public function checkOperatorWorkTime($finish = false, $vote = false): bool
	{
		if(
			$this->config['CHECK_AVAILABLE'] === 'Y'
			&& Config::isTimeManActive()
		)
		{
			$queueManager = Queue::initialization($this->sessionManager);

			// Dialog is accepted by the operator.
			if(
				$this->session['OPERATOR_ID'] > 0 && $this->session['STATUS'] >= Session::STATUS_ANSWER &&
				$queueManager->isRemoveSession($finish, $vote) === false
			)
			{
				if($queueManager->isOperatorActive($this->session['OPERATOR_ID'], true) === true)
				{
					$result = true;
				}
				else
				{
					$result = false;
				}
			}
			else
			{
				$result = $queueManager->isOperatorsActiveLine(true);
			}
		}
		else
		{
			$result = $this->isWorkTimeLine();
		}

		return $result;
	}

	/**
	 * Automatic processing on incoming message.
	 *
	 * @param bool $finish
	 * @param bool $vote
	 * @return bool
	 */
	public function automaticAddMessage($finish = false, $vote = false)
	{
		return $this->sendMessage($finish, $vote);
	}

	/**
	 * Automatic processing of outgoing message.
	 *
	 * @return bool
	 */
	public function automaticSendMessage()
	{
		if (
			$this->session['SEND_NO_WORK_TIME_TEXT'] != 'N'
			&& !empty($this->session['OPERATOR_ID'])
			&& Queue::isRealOperator($this->session['OPERATOR_ID'])
			&& $this->checkOperatorWorkTime()
		)
		{
			$this->sessionManager->update(['SEND_NO_WORK_TIME_TEXT' => 'N']);
		}

		return true;
	}

	/**
	 * Send a welcome message.
	 *
	 * @param bool $finish
	 * @param bool $vote
	 * @return bool|int
	 */
	public function sendMessage($finish = false, $vote = false)
	{
		$result = false;

		if (
			$this->config['WORKTIME_DAYOFF_RULE'] == Session::RULE_TEXT
			&& isset($this->config['WORKTIME_DAYOFF_TEXT'])
			&& $this->session['SEND_NO_WORK_TIME_TEXT'] != 'Y'
			&& $this->sessionManager->isEnableSendSystemMessage()
			&& $this->sessionManager->getAction() != Session::ACTION_CLOSED
			&& !$this->checkOperatorWorkTime($finish, $vote)
		)
		{
			$result = Im::addMessage([
				'TO_CHAT_ID' => $this->session['CHAT_ID'],
				'MESSAGE' => $this->config['WORKTIME_DAYOFF_TEXT'],
				'SYSTEM' => 'Y',
				'IMPORTANT_CONNECTOR' => 'Y',
				'NO_SESSION_OL' => 'Y',
				'PARAMS' => [
					'CLASS'=> 'bx-messenger-content-item-ol-output',
					'IMOL_FORM' => 'offline',
					'TYPE' => 'lines',
					'COMPONENT_ID' => 'bx-imopenlines-message'
				]
			]);

			$this->sessionManager->update([
				'SEND_NO_ANSWER_TEXT' => 'Y',
				'SEND_NO_WORK_TIME_TEXT' => 'Y'
			]);
		}

		return $result;
	}
}