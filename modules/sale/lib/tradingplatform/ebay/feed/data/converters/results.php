<?php

namespace Bitrix\Sale\TradingPlatform\Ebay\Feed\Data\Converters;

use Bitrix\Main\ArgumentNullException;

class Results extends DataConverter
{
	public function convert($data)
	{
		if(!isset($data["RESULT_ID"]))
			throw new ArgumentNullException("data[\"RESULT_ID\"]");

		if(!isset($data["CONTENT"]))
			throw new ArgumentNullException("data[\"CONTENT\"]");

		$result["ARRAY"]= \Bitrix\Sale\TradingPlatform\Xml2Array::convert($data["CONTENT"]);
		$result["RESULT_ID"] = $data["RESULT_ID"];
		$result["XML"] = $data["CONTENT"] <> '' ? $data["CONTENT"] : "<?xml version='1.0' encoding='UTF-8'?>";

		return $result;
	}
}