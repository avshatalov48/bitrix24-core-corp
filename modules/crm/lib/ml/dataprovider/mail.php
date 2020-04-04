<?php

namespace Bitrix\Crm\Ml\DataProvider;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Ml\FeatureBuilder;

class Mail extends Base
{
	const MAX_MESSAGES = 20;
	const MAX_WORDS_IN_MESSAGE = 200;

	public function getFeatureMap()
	{
		if(!Loader::includeModule("mail"))
		{
			return null;
		}

		return [
			"EMAIL_COUNT_TOTAL" => ["dataType" => "int"],
			"EMAIL_COUNT_LAST_WEEK" => ["dataType" => "int"],
			"EMAIL_COUNT_LAST_MONTH" => ["dataType" => "int"],
			"EMAIL_COUNT_OLDER_MONTH" => ["dataType" => "int"],
			"EMAIL_OPERATOR_TEXT" => ["dataType" => "text"],
			"EMAIL_CLIENT_TEXT" => ["dataType" => "text"],
		];
	}

	/**
	 * Returns e-mail features vector for a crm entity.
	 *
	 * EMAIL_COUNT_TOTAL
	 * EMAIL_COUNT_LAST_WEEK
	 * EMAIL_COUNT_LAST_MONTH
	 * EMAIL_COUNT_OLDER_MONTH
	 * EMAIL_OPERATOR_TEXT
	 * EMAIL_CLIENT_TEXT
	 *
	 * @param $entityTypeId
	 * @param $entityId
	 *
	 * @return array | null
	 */
	public function getFeatures($entityTypeId, $entityId)
	{
		if(!Loader::includeModule("mail"))
		{
			return null;
		}

		$activities = ActivityTable::getList([
			"select" => ["ID", "COMPLETED", "START_TIME", "END_TIME", "CREATED", "DIRECTION", "SUBJECT", new ExpressionField("DESCRIPTION", "DESCRIPTION")],
			"filter" => [
				"=PROVIDER_ID" => \Bitrix\Crm\Activity\Provider\Email::getId(),
				"=PROVIDER_TYPE_ID" => 'EMAIL',
				"=BINDINGS.OWNER_TYPE_ID" => $entityTypeId,
				"=BINDINGS.OWNER_ID" => $entityId,
			],
			"order" => [
				"START_TIME" => "desc"
			],
			"limit" => static::MAX_MESSAGES
		])->fetchAll();

		// EMAIL_COUNT_TOTAL
		$result["EMAIL_COUNT_TOTAL"] = count($activities);

		// EMAIL_COUNT_LAST_WEEK
		$weekBefore = clone $this->currentDate;
		$weekBefore->add("-1 weeks");
		$mailLastWeek = FeatureBuilder::filterActivitiesByDate($activities, $weekBefore, $this->currentDate);
		$result["EMAIL_COUNT_LAST_WEEK"] = count($mailLastWeek);

		// EMAIL_COUNT_LAST_MONTH
		$monthBefore = clone $this->currentDate;
		$monthBefore->add("-1 weeks");
		$emailLastMonth = FeatureBuilder::filterActivitiesByDate($activities, $monthBefore, $this->currentDate);
		$result["EMAIL_COUNT_LAST_MONTH"] = count($emailLastMonth);

		// EMAIL_COUNT_OLDER_MONTH
		$from = DateTime::createFromTimestamp(0);
		$emailOld = FeatureBuilder::filterActivitiesByDate($activities, $from, $monthBefore);
		$result["EMAIL_COUNT_OLDER_MONTH"] = count($emailOld);

		// EMAIL_OPERATOR_TEXT
		$messages = array_filter($activities, function($act){return $act["DIRECTION"] == \CCrmActivityDirection::Outgoing;});
		$messages = array_map(
			function($act)
			{
				return FeatureBuilder::clearText($act["SUBJECT"] . " " . $act["DESCRIPTION"], static::MAX_WORDS_IN_MESSAGE);
			},
			$messages
		);
		//$result["EMAIL_OPERATOR_TEXT_RAW"] = join(" ", $messages);
		$result["EMAIL_OPERATOR_TEXT"] = join(" ", $messages);

		// EMAIL_CLIENT_TEXT
		$messages = array_filter($activities, function($act){return $act["DIRECTION"] == \CCrmActivityDirection::Incoming;});
		$messages = array_map(
			function($act)
			{
				return FeatureBuilder::clearText($act["SUBJECT"] . " " . $act["DESCRIPTION"], static::MAX_WORDS_IN_MESSAGE);
			},
			$messages
		);
		//$result["EMAIL_CLIENT_TEXT_RAW"] = join(" ", $messages);
		$result["EMAIL_CLIENT_TEXT"] = FeatureBuilder::clearText(join(" ", $messages));

		return $result;
	}

}