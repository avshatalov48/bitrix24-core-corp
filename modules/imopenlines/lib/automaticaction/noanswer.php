<?php
namespace Bitrix\ImOpenLines\AutomaticAction;

use \Bitrix\Main\Loader,
	\Bitrix\Main\Type\DateTime;

use \Bitrix\Pull;

use \Bitrix\ImOpenLines\Chat,
	\Bitrix\ImOpenLines\Tools,
	\Bitrix\ImOpenLines\Config,
	\Bitrix\ImOpenLines\Session,
	\Bitrix\ImOpenLines\Model\SessionTable,
	\Bitrix\ImOpenLines\Model\SessionCheckTable;

class NoAnswer
{
	/**
	 * Determines if there are sessions that are not answered.
	 *
	 * @return bool
	 */
	public static function isThereSessionNoAnswer()
	{
		$result = false;

		$count = SessionCheckTable::getCount(['!=DATE_NO_ANSWER' => null]);

		if(!empty($count) && $count > 0)
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * Send notification about unavailability of the operator.
	 *
	 * @param int $limitTime
	 * @param int $limit
	 */
	public static function sendMessageNoAnswer($limitTime = 60, $limit = 0)
	{
		$time = new Tools\Time;

		$configs = [];
		$chats = [];
		$configManager = new Config();

		$count = 0;
		$countIterationPull = 0;
		while($time->getElapsedTime() <= $limitTime && (empty($limit) || $count < $limit))
		{
			if($countIterationPull > 10 && Loader::includeModule('pull'))
			{
				$countIterationPull = 0;

				Pull\Event::send();
			}

			$select = SessionTable::getSelectFieldsPerformance('SESSION');
			$res = SessionCheckTable::getList([
				'select' => $select,
				'filter' => [
					'<=DATE_NO_ANSWER' => new DateTime()
				],
				'order' => [
					'DATE_NO_ANSWER'
				],
				'limit' => 1
			]);

			if ($row = $res->fetch())
			{
				$fields = [];
				foreach($row as $key=>$value)
				{
					$key = str_replace('IMOPENLINES_MODEL_SESSION_CHECK_SESSION_', '', $key);
					$fields[$key] = $value;
				}

				if (!isset($configs[$fields['CONFIG_ID']]))
				{
					$configs[$fields['CONFIG_ID']] = $configManager->get($fields['CONFIG_ID']);
				}
				if (!isset($chats[$fields['CHAT_ID']]))
				{
					$chats[$fields['CHAT_ID']] = new Chat($fields['CHAT_ID']);
				}

				$session = new Session();
				$session->loadByArray($fields, $configs[$fields['CONFIG_ID']], $chats[$fields['CHAT_ID']]);
				$resultNoAnswer = $session->sendMessageNoAnswer();

				if($resultNoAnswer == true)
				{
					$countIterationPull++;
				}
				$count++;
			}
			else
			{
				break;
			}
		}

		if (Loader::includeModule('pull') && $countIterationPull > 0)
		{
			Pull\Event::send();
		}
	}
}