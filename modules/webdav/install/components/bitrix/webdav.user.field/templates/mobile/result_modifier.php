<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (strpos($this->__page, "view") === 0)
{
	CModule::IncludeModule("mobileapp");

	$arParams["MAX_SIZE"] = array("width" => 550, "height" => 832);
	$arParams["SMALL_SIZE"] = array("width" => 75, "height" => 100);
	$arParams["THUMB_SIZE"] = 70;

	$images = array(); $files = array();
	foreach ($arResult['FILES'] as $id => $arWDFile)
	{
		if (CFile::IsImage($arWDFile['NAME'])) // we don`t use content_type for this checking because revision 0fa6437117d2
		{
			$arSize = is_array($arParams["SIZE"][$arWDFile["ID"]]) ? $arParams["SIZE"][$arWDFile["ID"]] : array();
			if (!empty($arSize))
			{
				$arSize["width"] = intval(!!$arSize["width"] ? $arSize["width"] : $arSize["WIDTH"]);
				$arSize["height"] = intval(!!$arSize["height"] ? $arSize["height"] : $arSize["HEIGHT"]);
			}
			if ($arSize["width"] <= 0)
				$arSize["width"] = $arWDFile["FILE"]["WIDTH"];
			if ($arSize["height"] <= 0)
				$arSize["height"] = $arWDFile["FILE"]["HEIGHT"];
			$coeff = max(
				($arParams["MAX_SIZE"]["width"] > 0 ? $arSize["width"]/$arParams["MAX_SIZE"]["width"] : 0),
				($arParams["MAX_SIZE"]["height"] > 0 ? $arSize["height"]/$arParams["MAX_SIZE"]["height"] : 0)
			);
			if ($coeff > 1)
			{
				$arSize["width"] = round($arSize["width"]/$coeff);
				$arSize["height"] = round($arSize["height"]/$coeff);
			}
			$arSmallSize = $arParams["SMALL_SIZE"];
			$coeff = max(
				($arParams["SMALL_SIZE"]["width"] > 0 ? $arSize["width"]/$arParams["SMALL_SIZE"]["width"] : 0),
				($arParams["SMALL_SIZE"]["height"] > 0 ? $arSize["height"]/$arParams["SMALL_SIZE"]["height"] : 0)
			);
			if ($coeff > 1)
			{
				$arSmallSize["width"] = round($arSize["width"]/$coeff);
				$arSmallSize["height"] = round($arSize["height"]/$coeff);
			}

			$arResult["FILES"][$id]["CONTENT_TYPE"] = $arWDFile["FILE"]["CONTENT_TYPE"];
			$arResult["FILES"][$id]["WIDTH"] = $arSize["width"];
			$arResult["FILES"][$id]["HEIGHT"] = $arSize["height"];

			$arResult["FILES"][$id]["SRC"] =
			$arResult["FILES"][$id]["SMALL_SRC"] =
			$arResult["FILES"][$id]["THUMB_SRC"] =
				$arWDFile["PATH"].(strpos($arWDFile["PATH"], "?") === false ? "?" : "&")."cache_image=Y";

			if ($arSize["height"] != $arWDFile["FILE"]["HEIGHT"] || $arSize["width"] != $arWDFile["FILE"]["WIDTH"])
				$arResult["FILES"][$id]["SRC"] .= "&width=".$arSize["width"]."&height=".$arSize["height"];
			$arResult["FILES"][$id]["SMALL_SRC"] .= "&width=".$arSmallSize["width"]."&height=".$arSmallSize["height"];
			$arResult["FILES"][$id]["THUMB_SRC"] .= "&exact=Y&width=".$arParams["THUMB_SIZE"]."&height=".$arParams["THUMB_SIZE"];

			$max_dimension = max(array(intval(CMobile::getInstance()->getDevicewidth()), intval(CMobile::getInstance()->getDeviceheight())));
			$max_real_dimension = max(array(intval($arWDFile["FILE"]["WIDTH"]), intval($arWDFile["FILE"]["HEIGHT"])));

			if ($max_dimension < 650)
			{
				$max_dimension = 650;
			}
			elseif ($max_dimension < 1300)
			{
				$max_dimension = 1300;
			}
			else
			{
				$max_dimension = 2050;
			}
			
			if ($max_real_dimension > $max_dimension)
			{
				$arResult["FILES"][$id]["PATH"] .= (strpos($arResult["FILES"][$id]["PATH"], "?") === false ? "?" : "&")."cache_image=Y&width=".$max_dimension."&height=".$max_dimension;
			}

			$images[$id] = $arResult["FILES"][$id];

		}
		else
		{
			$files[$id] = $arWDFile;
		}
	}
	if ($this->__page == "view")
	{
		$arResult['IMAGES'] = $images;
		$arResult['FILES'] = $files;
	}
}
?>