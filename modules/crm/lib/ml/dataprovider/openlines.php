<?php

namespace Bitrix\Crm\Ml\DataProvider;

use Bitrix\Im\Model\MessageTable;
use Bitrix\ImOpenLines\Model\SessionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Ml\FeatureBuilder;

class OpenLines extends Base
{
	protected const MAX_DIALOGS = 20;

	public function getFeatureMap()
	{
		if(!Loader::includeModule("imopenlines") || !Loader::includeModule("im"))
		{
			return false;
		}

		return [
			"OL_SESSIONS_TOTAL" => ["dataType" => "int"],
			"OL_SESSIONS_LAST_WEEK" => ["dataType" => "int"],
			"OL_SESSIONS_LAST_MONTH" => ["dataType" => "int"],
			"OL_SESSIONS_OLDER_MONTH" => ["dataType" => "int"],
			"OL_SOURCE" => ["dataType" => "string"], // most frequent open line session source
			"OL_CLIENT_ANSWER_TIME" => ["dataType" => "int"],  // median value
			"OL_OPERATOR_ANSWER_TIME" => ["dataType" => "int"],  // median value
			"OL_MESSAGE_COUNT" => ["dataType" => "int"],
			"OL_OPERATOR_MESSAGE_TEXT" => ["dataType" => "text"],
			"OL_CLIENT_MESSAGE_TEXT" => ["dataType" => "text"],
		];
	}

	/**
	 * Returns open lines features vector for crm entity.
	 *
	 *	OL_SESSIONS_TOTAL
	 *	OL_SESSIONS_LAST_WEEK
	 *	OL_SESSIONS_LAST_MONTH
	 *	OL_SESSIONS_OLDER_MONTH
	 *	OL_SOURCE
	 *	OL_CLIENT_ANSWER_TIME
	 *	OL_OPERATOR_ANSWER_TIME
	 *	OL_MESSAGE_COUNT
	 *	OL_OPERATOR_MESSAGE_TEXT
	 *	OL_CLIENT_MESSAGE_TEXT
	 *
	 * @param $entityTypeId
	 * @param $entityId
	 *
	 * @return array | false
	 */
	public function getFeatures($entityTypeId, $entityId)
	{
		if(!Loader::includeModule("imopenlines") || !Loader::includeModule("im"))
		{
			return false;
		}

		$activities = ActivityTable::getList([
			"select" => ["ID", "COMPLETED", "START_TIME", "END_TIME", "CREATED", "ASSOCIATED_ENTITY_ID"],
			"filter" => [
				"=PROVIDER_ID" => \Bitrix\Crm\Activity\Provider\OpenLine::ACTIVITY_PROVIDER_ID,
				"=BINDINGS.OWNER_TYPE_ID" => $entityTypeId,
				"=BINDINGS.OWNER_ID" => $entityId,
			],
			"order" => [
				"START_TIME" => "desc"
			],
			"limit" => static::MAX_DIALOGS

		])->fetchAll();

		$result["OL_SESSIONS_TOTAL"] = count($activities);

		// OL_SESSIONS_LAST_WEEK
		$weekBefore = clone $this->currentDate;
		$weekBefore->add("-1 weeks");
		$linesLastWeek = FeatureBuilder::filterActivitiesByDate($activities, $weekBefore, $this->currentDate);
		$result["OL_SESSIONS_LAST_WEEK"] = count($linesLastWeek);

		// OL_SESSIONS_LAST_MONTH
		$monthBefore = clone $this->currentDate;
		$monthBefore->add("-1 weeks");
		$linesLastMonth = FeatureBuilder::filterActivitiesByDate($activities, $monthBefore, $this->currentDate);
		$result["OL_SESSIONS_LAST_MONTH"] = count($linesLastMonth);

		// OL_SESSIONS_OLDER_MONTH
		$from = DateTime::createFromTimestamp(0);
		$linesOld = FeatureBuilder::filterActivitiesByDate($activities, $from, $monthBefore);
		$result["OL_SESSIONS_OLDER_MONTH"] = count($linesOld);

		$sessionIds = array_map(function($act){return $act["ASSOCIATED_ENTITY_ID"];}, $activities);
		$sessions = SessionTable::getList([
			"select" => ["*"],
			"filter" => [
				"=ID" => $sessionIds
			]
		])->fetchAll();

		// OL_SOURCE
		$sources = array_map(function($session){return $session["SOURCE"];}, $sessions);
		$sourcesCount = array_count_values($sources);
		$max = 0;
		$maxSource = "";
		foreach ($sourcesCount as $k => $v)
		{
			if($v > $max)
			{
				$max = $v;
				$maxSource = $k;
			}
		}
		$result["OL_SOURCE"] = $maxSource;

		$chatIds = array_map(function($session){return $session["CHAT_ID"];}, $sessions);

		$messages = MessageTable::getList([
			"select" => ["CHAT_ID", "AUTHOR_ID", "MESSAGE", "DATE_CREATE", "IS_OPERATOR" => "AUTHOR.IS_REAL_USER"],
			"filter" => [
				"=CHAT_ID" => $chatIds,
				">AUTHOR_ID" => 0
			]
		])->fetchAll();

		// OL_CLIENT_ANSWER_TIME
		// OL_OPERATOR_ANSWER_TIME
		$firstMessages = [];
		foreach ($messages as $messageFields)
		{
			$last = count($firstMessages) > 0 ? $firstMessages[count($firstMessages) - 1] : null;

			if(!$last || $last['IS_OPERATOR'] !== $messageFields['IS_OPERATOR'])
			{
				$firstMessages[] = [
					'IS_OPERATOR' => $messageFields['IS_OPERATOR'],
					'DATE_CREATE' => $messageFields['DATE_CREATE'],
				];
			}
		}

		$opAnswerTimes = [];
		$clientAnswerTimes = [];
		$firstMessagesCount = count($firstMessages);
		if($firstMessagesCount > 1)
		{
			for ($i = 1; $i < $firstMessagesCount; $i++)
			{
				$time = $firstMessages[$i]['DATE_CREATE']->getTimestamp() - $firstMessages[$i - 1]['DATE_CREATE']->getTimestamp();
				if($firstMessages[$i]['IS_OPERATOR'] === 'Y')
				{
					$opAnswerTimes[] = $time;
				}
				else
				{
					$clientAnswerTimes[] = $time;
				}
			}
			sort($opAnswerTimes);
			sort($clientAnswerTimes);

			$result["OL_CLIENT_ANSWER_TIME"] = FeatureBuilder::getMedianValue($clientAnswerTimes);
			$result["OL_OPERATOR_ANSWER_TIME"] = FeatureBuilder::getMedianValue($opAnswerTimes);
		}

		// OL_MESSAGE_COUNT
		$result["OL_MESSAGE_COUNT"] = count($messages);

		// OL_OPERATOR_MESSAGE_TEXT
		$operatorMessages = array_filter($messages, function($message){return $message["IS_OPERATOR"] === "Y";});
		$operatorMessages = array_map(function($message){return $message["MESSAGE"];}, $operatorMessages);
		$result["OL_OPERATOR_MESSAGE_TEXT"] = FeatureBuilder::clearText(join(" ", $operatorMessages));

		// OL_CLIENT_MESSAGE_TEXT
		$clientMessages = array_filter($messages, function($message){return $message["IS_OPERATOR"] !== "Y";});
		$clientMessages = array_map(function($message){return $message["MESSAGE"];}, $clientMessages);
		$result["OL_CLIENT_MESSAGE_TEXT"] = FeatureBuilder::clearText(join(" ", $clientMessages));

		return $result;
	}
}