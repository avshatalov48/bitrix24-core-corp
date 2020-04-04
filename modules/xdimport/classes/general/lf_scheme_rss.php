<?
IncludeModuleLangFile(__FILE__);

class CXDILFSchemeRSS
{
	public static function Request($server, $page, $port, $params, $uri = false)
	{
		global $APPLICATION;

		if ($uri && strlen($uri) > 0)
		{
			$strURI = $uri;
		}
		else
		{
			$strURI = "http://".
				$server.
				(strlen($port) > 0 && intval($port) > 0 ? ":".intval($port) : "").
				(strlen($page) ? $page : "/").
				(strlen($params) > 0 ? "?".$params : "");
		}

		$http = new \Bitrix\Main\Web\HttpClient(array(
			"version" => "1.0",
			"socketTimeout" => 30,
			"streamTimeout" => 30,
			"redirect" => true,
			"redirectMax" => 5,
		));

		$strData = $http->get($strURI);
		$errors = $http->getError();

		$arRSSResult = array();

		if (
			!$strData 
			&& !empty($errors)
		)
		{
			$strError = "";

			foreach($errors as $errorCode => $errMes)
			{
				$strError .= $errorCode.": ".$errMes;
			}

			\CEventLog::Add(array(
				"SEVERITY" => "ERROR",
				"AUDIT_TYPE_ID" => "XDIMPORT_HTTP",
				"MODULE_ID" => "xdimport",
				"ITEM_ID" => "RSS_REQUEST",
				"DESCRIPTION" => $strError,
			));
		}
		
		if ($strData)
		{
			$rss_charset = "windows-1251";
			if (preg_match("/<"."\?XML[^>]{1,}encoding=[\"']([^>\"']{1,})[\"'][^>]{0,}\?".">/i", $strData, $matches))
			{
				$rss_charset = Trim($matches[1]);
			}

			$strData = preg_replace("/<"."\\?XML.*?\\?".">/i", "", $strData);
			$strData = $APPLICATION->ConvertCharset($strData, $rss_charset, SITE_CHARSET);
		}

		if (strlen($strData) > 0)
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/xml.php");
			$objXML = new CDataXML();
			$res = $objXML->LoadString($strData);
			if($res !== false)
			{
				$ar = $objXML->GetArray();
				if (
					is_array($ar) && isset($ar["rss"])
					&& is_array($ar["rss"]) && isset($ar["rss"]["#"])
					&& is_array($ar["rss"]["#"]) && isset($ar["rss"]["#"]["channel"])
					&& is_array($ar["rss"]["#"]["channel"]) && isset($ar["rss"]["#"]["channel"][0])
					&& is_array($ar["rss"]["#"]["channel"][0]) && isset($ar["rss"]["#"]["channel"][0]["#"])
				) // RSS 2.0
				{
					$arRSSResult = $ar["rss"]["#"]["channel"][0]["#"];
				}
				elseif (
					is_array($ar) && isset($ar["feed"])
					&& is_array($ar["feed"]) && isset($ar["feed"]["#"])
				) // Atom
				{
					return CXDILFSchemeRSSAtom::GetFeed($ar, $objXML);
				}

				$arRSSResult["rss_charset"] = strtolower(SITE_CHARSET);
			}
		}

		if (
			is_array($arRSSResult) 
			&& !empty($arRSSResult)
		)
		{
			$arRSSResult = self::FormatArray($arRSSResult);

			if (
				!empty($arRSSResult)
				&& array_key_exists("item", $arRSSResult)
				&& is_array($arRSSResult["item"])
				&& !empty($arRSSResult["item"])
			)
			{
				$arRSSResult["item"] = array_reverse($arRSSResult["item"]);
			}
		}

		return $arRSSResult;
	}

	private function FormatArray(&$arRes)
	{
		if(is_array($arRes["title"][0]["#"]))
			$arRes["title"][0]["#"] = $arRes["title"][0]["#"]["cdata-section"][0]["#"];
		if(is_array($arRes["link"][0]["#"]))
			$arRes["link"][0]["#"] = $arRes["link"][0]["#"]["cdata-section"][0]["#"];
		if(is_array($arRes["description"][0]["#"]))
			$arRes["description"][0]["#"] = $arRes["description"][0]["#"]["cdata-section"][0]["#"];

		$arResult = array(
			"title" => $arRes["title"][0]["#"],
			"link" => $arRes["link"][0]["#"],
			"description" => $arRes["description"][0]["#"],
			"lastBuildDate" => $arRes["lastBuildDate"][0]["#"],
			"ttl" => $arRes["ttl"][0]["#"],
		);

		if ($arRes["image"])
		{
			if(is_array($arRes["image"][0]["#"]))
			{
				$arResult["image"]["title"] = $arRes["image"][0]["#"]["title"][0]["#"];
				$arResult["image"]["url"] = $arRes["image"][0]["#"]["url"][0]["#"];
				$arResult["image"]["link"] = $arRes["image"][0]["#"]["link"][0]["#"];
				$arResult["image"]["width"] = $arRes["image"][0]["#"]["width"][0]["#"];
				$arResult["image"]["height"] = $arRes["image"][0]["#"]["height"][0]["#"];
			}
			elseif(is_array($arRes["image"][0]["@"]))
			{
				$arResult["image"]["title"] = $arRes["image"][0]["@"]["title"];
				$arResult["image"]["url"] = $arRes["image"][0]["@"]["url"];
				$arResult["image"]["link"] = $arRes["image"][0]["@"]["link"];
				$arResult["image"]["width"] = $arRes["image"][0]["@"]["width"];
				$arResult["image"]["height"] = $arRes["image"][0]["@"]["height"];
			}
		}

		foreach($arRes["item"] as $i => $arItem)
		{
			if(!is_array($arItem) || !is_array($arItem["#"]))
				continue;

			if(is_array($arItem["#"]["title"][0]["#"]))
				$arItem["#"]["title"][0]["#"] = $arItem["#"]["title"][0]["#"]["cdata-section"][0]["#"];

			if(is_array($arItem["#"]["description"][0]["#"]))
				$arItem["#"]["description"][0]["#"] = $arItem["#"]["description"][0]["#"]["cdata-section"][0]["#"];
			elseif(is_array($arItem["#"]["encoded"][0]["#"]))
				$arItem["#"]["description"][0]["#"] = $arItem["#"]["encoded"][0]["#"]["cdata-section"][0]["#"];
			$arResult["item"][$i]["description"] = $arItem["#"]["description"][0]["#"];

			if(is_array($arItem["#"]["title"][0]["#"]))
				$arItem["#"]["title"][0]["#"] = $arItem["#"]["title"][0]["#"]["cdata-section"][0]["#"];
			$arResult["item"][$i]["title"] = $arItem["#"]["title"][0]["#"];

			if(is_array($arItem["#"]["link"][0]["#"]))
				$arItem["#"]["link"][0]["#"] = $arItem["#"]["link"][0]["#"]["cdata-section"][0]["#"];
			$arResult["item"][$i]["link"] = $arItem["#"]["link"][0]["#"];

			if ($arItem["#"]["enclosure"])
			{
				$arResult["item"][$i]["enclosure"]["url"] = $arItem["#"]["enclosure"][0]["@"]["url"];
				$arResult["item"][$i]["enclosure"]["length"] = $arItem["#"]["enclosure"][0]["@"]["length"];
				$arResult["item"][$i]["enclosure"]["type"] = $arItem["#"]["enclosure"][0]["@"]["type"];
				if ($arItem["#"]["enclosure"][0]["@"]["width"])
				{
					$arResult["item"][$i]["enclosure"]["width"] = $arItem["#"]["enclosure"][0]["@"]["width"];
				}
				if ($arItem["#"]["enclosure"][0]["@"]["height"])
				{
					$arResult["item"][$i]["enclosure"]["height"] = $arItem["#"]["enclosure"][0]["@"]["height"];
				}
			}
			$arResult["item"][$i]["category"] = $arItem["#"]["category"][0]["#"];
			$arResult["item"][$i]["pubDate"] = $arItem["#"]["pubDate"][0]["#"];

			$arRes["item"][$i] = $arItem;
		}

		return $arResult;
	}

}
?>