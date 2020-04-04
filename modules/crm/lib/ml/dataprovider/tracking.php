<?php

namespace Bitrix\Crm\Ml\DataProvider;

use Bitrix\Crm\Ml\FeatureBuilder;
use Bitrix\Crm\Tracking\Internals\TraceChannelTable;
use Bitrix\Crm\Tracking\Internals\TraceTable;

class Tracking extends Base
{

	public function getFeatureMap()
	{
		return [
			"TRACKING_SOURCE_ID" => ["dataType" => "string"],
			"TRACKING_IS_MOBILE" => ["dataType" => "string"],
			"TRACKING_PAGES" => ["dataType" => "text"],
			"TRACKING_CHANNELS" => ["dataType" => "text"]
		];
	}

	public function getFeatures($entityTypeId, $entityId)
	{
		$trace = TraceTable::getList([
			"select" => ["*"],
			"filter" => [
				"=ENTITY.ENTITY_TYPE_ID" => $entityTypeId,
				"=ENTITY.ENTITY_ID" => $entityId
			],
			"limit" => 1
		])->fetch();

		$result = array_fill_keys(array_keys($this->getFeatureMap()), "");

		if(!$trace)
		{
			return $result;
		}

		$traceId = $trace["ID"];

		$traceChannels = TraceChannelTable::getList([
			"select" => ["CODE", "VALUE"],
			"filter" => [
				"=TRACE_ID" => $traceId
			]
		])->fetchAll();

		$result["TRACKING_SOURCE_ID"] = (string)$trace["SOURCE_ID"];
		$result["TRACKING_IS_MOBILE"] = (string)$trace["IS_MOBILE"];

		if(is_array($trace["PAGES_RAW"]))
		{
			$titles = array_map(function($page){return $page["TITLE"];}, $trace["PAGES_RAW"]);
			$result["TRACKING_PAGES"] = FeatureBuilder::clearText(join(" ", $titles));
		}

		if(is_array($traceChannels))
		{
			$traceChannels = array_map(function($channel){return $channel["CODE"] . "|" . $channel["VALUE"];}, $traceChannels);
			$traceChannels = join(" ", $traceChannels);
		}
		else
		{
			$traceChannels = "";
		}

		$result["TRACKING_CHANNELS"] = $traceChannels;

		return $result;
	}
}