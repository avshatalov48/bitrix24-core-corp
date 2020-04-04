<?
IncludeModuleLangFile(__FILE__);

class CXDILFSchemeRSSAtom
{
	public static function GetFeed($ar, $objXML)
	{

		$arRSSResult = array();

		if (
			is_array($ar) && isset($ar["feed"])
			&& is_array($ar["feed"]) && isset($ar["feed"]["#"])
		)
		{
			$arRSSResult = $ar["feed"]["#"];
		}

		if (
			is_array($arRSSResult) 
			&& !empty($arRSSResult)
		)
		{
			$arRSSResult = self::FormatArray($arRSSResult, $objXML);

			if (
				!empty($arRSSResult)
				&& array_key_exists("entry", $arRSSResult)
				&& is_array($arRSSResult["entry"])
				&& !empty($arRSSResult["entry"])
			)
			{
				$arRSSResult["entry"] = array_reverse($arRSSResult["entry"]);
			}
		}

		return $arRSSResult;
	}

	private function FormatArray(&$arRes, $objXML)
	{
		$entries = $objXML->GetTree()->children[0]->elementsByName('entry');

		if(is_array($arRes["title"][0]["#"]))
			$arRes["title"][0]["#"] = $arRes["title"][0]["#"]["cdata-section"][0]["#"];
		if(is_array($arRes["link"][0]["#"]))
			$arRes["link"][0]["#"] = $arRes["link"][0]["#"]["cdata-section"][0]["#"];
		if(is_array($arRes["subtitle"][0]["#"]))
			$arRes["subtitle"][0]["#"] = $arRes["description"][0]["#"]["cdata-section"][0]["#"];

		$arResult = array(
			"title" => $arRes["title"][0]["#"],
			"link" => $arRes["link"][0]["#"],
			"description" => $arRes["subtitle"][0]["#"],
			"updated" => $arRes["updated"][0]["#"],
			"ttl" => 0,
		);

		if ($arRes["logo"])
		{
			$arResult["image"] = array(
				'url' => $arRes["logo"][0]["#"]
			);
		}

		foreach($arRes["entry"] as $i => $arItem)
		{
			if(!is_array($arItem) || !is_array($arItem["#"]))
				continue;

			if(is_array($arItem["#"]["title"][0]["#"]))
				$arItem["#"]["title"][0]["#"] = $arItem["#"]["title"][0]["#"]["cdata-section"][0]["#"];

			$description = '';

			if (
				isset($arItem["#"]["content"])
				&& is_array($arItem["#"]["content"])
			)
			{

				$type = (
					isset($arItem["#"]["content"][0]["@"])
					&& is_array($arItem["#"]["content"][0]["@"])
					&& isset($arItem["#"]["content"][0]["@"]["type"])
						? $arItem["#"]["content"][0]["@"]["type"]
						: false
				);

				if ($type == "xhtml")
				{
					$description = $entries[$i]->elementsByName('content')[0]->children[0]->__toString();
				}
				else
				{
					$description = $arItem["#"]["content"][0]["#"];
				}
			}
			elseif (
				isset($arItem["#"]["summary"])
				&& is_array($arItem["#"]["summary"])
			)
			{
				$description = $arItem["#"]["summary"][0]["#"];
			}

			if (is_array($description))
			{
				$description = $description["cdata-section"][0]["#"];
			}

			$arResult["item"][$i]["description"] = $description;

			if(is_array($arItem["#"]["title"][0]["#"]))
				$arItem["#"]["title"][0]["#"] = $arItem["#"]["title"][0]["#"]["cdata-section"][0]["#"];
			$arResult["item"][$i]["title"] = $arItem["#"]["title"][0]["#"];

			if(is_array($arItem["#"]["link"][0]["#"]))
				$arItem["#"]["link"][0]["#"] = $arItem["#"]["link"][0]["#"]["cdata-section"][0]["#"];

			if (
				empty($arItem["#"]["link"][0]["#"])
				&& !empty($arItem["#"]["link"][0]["@"])
				&& is_array($arItem["#"]["link"][0]["@"])
				&& !empty($arItem["#"]["link"][0]["@"]["href"])
			)
			{
				$arItem["#"]["link"][0]["#"] = $arItem["#"]["link"][0]["@"]["href"];
			}

			$arResult["item"][$i]["link"] = $arItem["#"]["link"][0]["#"];

			$arResult["item"][$i]["category"] = $arItem["#"]["category"][0]["#"];
			$arResult["item"][$i]["pubDate"] = $arItem["#"]["published"][0]["#"];

			$arRes["item"][$i] = $arItem;
		}

		return $arResult;
	}

}
?>