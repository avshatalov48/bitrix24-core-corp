<?php

namespace  Bitrix\Crm\Ml\DataProvider;

use Bitrix\Crm\ActivityTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\Model\TranscriptLineTable;
use Bitrix\Voximplant\StatisticTable;
use Bitrix\Crm\Ml\FeatureBuilder;

class Telephony extends Base
{
	const MAX_WORDS_TRANSCRIPTION = 100;

	public function getFeatureMap()
	{
		return [
			"CALLS_TOTAL" => ["dataType" => "int"],
			"CALLS_INCOMING" => ["dataType" => "int"],
			"CALLS_OUTGOING" => ["dataType" => "int"],
			"CALLS_CALLBACK" => ["dataType" => "int"],
			"CALLS_LAST_WEEK" => ["dataType" => "int"],
			"CALLS_LAST_MONTH" => ["dataType" => "int"],
			"CALLS_OLDER_MONTH" => ["dataType" => "int"],
			"CALLS_TRANSCRIPTION" => ["dataType" => "text"],
			"CALLS_MEDIAN_DURATION" => ["dataType" => "int"],
		];
	}

	/**
	 * Returns telephony features vector for crm entity.
	 *
	 * "CALLS_TOTAL"
	 * "CALLS_INCOMING"
	 * "CALLS_OUTGOING"
	 * "CALLS_CALLBACK"
	 * "CALLS_LAST_WEEK"
	 * "CALLS_LAST_MONTH"
	 * "CALLS_OLDER_MONTH"
	 * "CALLS_TRANSCRIPTION"
	 * "CALLS_MEDIAN_DURATION"
	 *
	 * @param $entityTypeId
	 * @param $entityId
	 */
	public function getFeatures($entityTypeId, $entityId)
	{
		$activities = ActivityTable::getList([
			"select" => ["ID", "DIRECTION", "COMPLETED", "START_TIME", "END_TIME", "CREATED", "ORIGIN_ID"],
			"filter" => [
				[
					"LOGIC" => "OR",
					"=IS_CALL" => 1,
					[
						"=PROVIDER_ID" => \Bitrix\Crm\Activity\Provider\Call::ACTIVITY_PROVIDER_ID,
					]
				],
				"=BINDINGS.OWNER_TYPE_ID" => $entityTypeId,
				"=BINDINGS.OWNER_ID" => $entityId,
			],
			"order" => [
				"START_TIME" => "asc"
			]
		])->fetchAll();

		$result["CALLS_TOTAL"] = count($activities);

		$callsIncoming = 0;
		$callsOutgoing = 0;
		$callsCallback = 0;

		foreach ($activities as $activity)
		{
			if (mb_strpos($activity["ORIGIN_ID"], "VI_callback") === 0)
			{
				$callsCallback++;
			}
			else if($activity["DIRECTION"] == \CCrmActivityDirection::Incoming)
			{
				$callsIncoming++;
			}
			else
			{
				$callsOutgoing++;
			}
		}

		$result["CALLS_INCOMING"] = $callsIncoming;
		$result["CALLS_OUTGOING"] = $callsOutgoing;
		$result["CALLS_CALLBACK"] = $callsCallback;

		// CALLS_LAST_WEEK
		$weekBefore = clone $this->currentDate;
		$weekBefore->add("-1 weeks");
		$callsLastWeek = FeatureBuilder::filterActivitiesByDate($activities, $weekBefore, $this->currentDate);
		$result["CALLS_LAST_WEEK"] = count($callsLastWeek);

		// CALLS_LAST_MONTH
		$monthBefore = clone $this->currentDate;
		$monthBefore->add("-1 weeks");
		$callsLastMonth = FeatureBuilder::filterActivitiesByDate($activities, $monthBefore, $this->currentDate);
		$result["CALLS_LAST_MONTH"] = count($callsLastMonth);

		// CALLS_OLDER_MONTH
		$from = DateTime::createFromTimestamp(0);
		$callsOld = FeatureBuilder::filterActivitiesByDate($activities, $from, $monthBefore);
		$result["CALLS_OLDER_MONTH"] = count($callsOld);

		if(Loader::includeModule("voximplant"))
		{
			$origins = array_column($activities, "ORIGIN_ID");
			$origins = array_filter($origins, function($origin) {return mb_strpos($origin, "VI_") !== false;});
			$callIds = array_map(
				function($origin) {
					return mb_substr($origin, 3);
				},
				$origins
			);

			if(count($callIds) > 0)
			{
				$calls = StatisticTable::getList([
					"select" => ["CALL_DURATION", "SESSION_ID"],
					"filter" => ["=CALL_ID" => $callIds],
					"order" => ["CALL_DURATION" => "asc"]
				])->fetchAll();

				// CALLS_MEDIAN_DURATION
				$durations = array_column($calls, "DURATION");
				$result["CALLS_MEDIAN_DURATION"] = FeatureBuilder::getMedianValue($durations);

				// CALLS_TRANSCRIPTION
				$messages = TranscriptLineTable::getList([
					"select" => ["MESSAGE"],
					"filter" => [
						"=TRANSCRIPT.CALL_ID" => $callIds
					]
				])->fetchAll();
				$messages = array_column($messages, "MESSAGE");

				if(count($messages) > 0)
				{
					$result["CALLS_TRANSCRIPTION"] = FeatureBuilder::clearText(
						join(" ", $messages),
						static::MAX_WORDS_TRANSCRIPTION
					);
				}
			}
		}

		return $result;
	}
}