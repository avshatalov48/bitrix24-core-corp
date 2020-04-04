<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CJSCore::Init(array('viewer'));
if (strpos($this->__page, "view") === 0)
{
	$arParams["THUMB_SIZE"] = (!!$arParams["THUMB_SIZE"] ? $arParams["THUMB_SIZE"] : array("width" => 69, "height" => 69));
	$arParams["MAX_SIZE"] = (!!$arParams["MAX_SIZE"] ? $arParams["MAX_SIZE"] : array("width" => 600, "height" => 600));
	$arParams["HTML_SIZE"] = (!!$arParams["HTML_SIZE"] ? $arParams["HTML_SIZE"] : array("width" => 600, "height" => 600));
	$arParams["SCREEN_SIZE"] = array("width" => 1024, "height" => 1024);
	$images = array(); $files = array();
	foreach ($arResult['FILES'] as $id => $arWDFile)
	{
		if (CFile::IsImage($arWDFile['NAME'], $arWDFile["FILE"]["CONTENT_TYPE"]))
		{
			$src = $arWDFile["PATH"].(strpos($arWDFile["PATH"], "?") === false ? "?" : "&")."cache_image=Y";
			$res = array(
				"content_type" => $arWDFile["FILE"]["CONTENT_TYPE"],
				"src" => $src,
				"width" => $arWDFile["FILE"]["WIDTH"],
				"height" => $arWDFile["FILE"]["HEIGHT"],
				"basic" => array(
					"src" => $src,
					"width" => $arWDFile["FILE"]["WIDTH"],
					"height" => $arWDFile["FILE"]["HEIGHT"]
				),
				"thumb" => array(
					"src" => $src."&".http_build_query(array_merge($arParams["THUMB_SIZE"], array("exact" => "Y"))),
					"width" => $arParams["THUMB_SIZE"]["width"],
					"height" => $arParams["THUMB_SIZE"]["height"]
				)
			);

			$arSize = is_array($arParams["SIZE"][$arWDFile["ID"]]) ? $arParams["SIZE"][$arWDFile["ID"]] : array();
			$arSize["width"] = intval(!!$arSize["width"] ? $arSize["width"] : $arSize["WIDTH"]);
			$arSize["height"] = intval(!!$arSize["height"] ? $arSize["height"] : $arSize["HEIGHT"]);
			$bExactly = ($arSize["width"] > 0 && $arSize["height"] > 0);

			if (!empty($arParams["MAX_SIZE"]) && ($arParams["MAX_SIZE"]["width"] > 0 || $arParams["MAX_SIZE"]["height"] > 0))
			{
				$circumscribed = array(
					"width" => ($arParams["MAX_SIZE"]["width"] > 0 ? $arParams["MAX_SIZE"]["width"] : $arWDFile["FILE"]["WIDTH"]),
					"height" => ($arParams["MAX_SIZE"]["height"] > 0 ? $arParams["MAX_SIZE"]["height"] : $arWDFile["FILE"]["HEIGHT"]));
				CFile::ScaleImage(
					$arWDFile["FILE"]["WIDTH"], $arWDFile["FILE"]["HEIGHT"],
					$circumscribed, BX_RESIZE_IMAGE_PROPORTIONAL,
					$bNeedCreatePicture,
					$arSourceSize, $arDestinationSize);
				if ($bNeedCreatePicture) {
					$res["src"] .= "&".http_build_query($arDestinationSize);
				}

				if ($arParams["HTML_SIZE"])
				{
					$circumscribed1 = array(
						"width" => ($arParams["HTML_SIZE"]["width"] > 0 ? $arParams["HTML_SIZE"]["width"] : $arWDFile["FILE"]["WIDTH"]),
						"height" => ($arParams["HTML_SIZE"]["height"] > 0 ? $arParams["HTML_SIZE"]["height"] : $arWDFile["FILE"]["HEIGHT"]));
					CFile::ScaleImage(
						$arWDFile["FILE"]["WIDTH"], $arWDFile["FILE"]["HEIGHT"],
						$circumscribed1, BX_RESIZE_IMAGE_PROPORTIONAL,
						$bNeedCreatePicture,
						$arSourceSize, $arDestinationSize1);

					if ($arDestinationSize1["width"] < $arDestinationSize["width"] ||
						$arDestinationSize1["height"] < $arDestinationSize["height"])
					{
						$arDestinationSize = $arDestinationSize1;
						$circumscribed = $circumscribed1;
					}
				}

				if ($bExactly && $circumscribed) {
					CFile::ScaleImage(
						$arSize["width"], $arSize["height"],
						$circumscribed, BX_RESIZE_IMAGE_PROPORTIONAL,
						$bNeedCreatePicture,
						$arSourceSize, $arSize);
				}

				$res["width"] = ($bExactly ? $arSize["width"] : $arDestinationSize["width"]);
				$res["height"] = ($bExactly ? $arSize["height"] : $arDestinationSize["height"]);
			}
			else if ($bExactly)
			{
				$res["width"] = $arSize["width"];
				$res["height"] = $arSize["height"];
			}


			if (!empty($arParams["SCREEN_SIZE"]))
			{
				CFile::ScaleImage(
					$arWDFile["FILE"]["WIDTH"], $arWDFile["FILE"]["HEIGHT"],
					$arParams["SCREEN_SIZE"], BX_RESIZE_IMAGE_PROPORTIONAL,
					$bNeedCreatePicture,
					$arSourceSize, $arDestinationSize);
				if ($bNeedCreatePicture)
				{
					$res["original"] = $res["basic"];
					$res["basic"] = array(
						"src" => $res["original"]["src"]."&".http_build_query($arParams["SCREEN_SIZE"]),
						"width" => $arDestinationSize["width"],
						"height" => $arDestinationSize["height"]
					);
				}
			}

			$arResult["FILES"][$id] = array_merge($arResult["FILES"][$id], $res);
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
else
{
	$arParams["THUMB_SIZE"] = 100;
	$http_query = array("cache_image" => "Y", "width" => $arParams["THUMB_SIZE"], "height" => $arParams["THUMB_SIZE"]);
	if (isset($arParams["THUMB_SIZE"]))
	{
		foreach ($arResult['ELEMENTS'] as $id => $arElement)
		{
			if (CFile::IsImage($arElement['NAME'], $arElement['FILE']["CONTENT_TYPE"]))
			{

				CFile::ScaleImage(
					$arElement["FILE"]["WIDTH"], $arElement["FILE"]["HEIGHT"],
					array("width" => $arParams["THUMB_SIZE"], "height" => $arParams["THUMB_SIZE"]),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					$bNeedCreatePicture,
					$arSourceSize, $arDestinationSize);
				if ($bNeedCreatePicture)
				{
					$arElement["original"] = array(
						"src" => $arElement["URL_GET"],
						"width" => $arElement["FILE"]["WIDTH"],
						"height" => $arElement["FILE"]["HEIGHT"]
					);
					$arElement["FILE"]["WIDTH"] = $arDestinationSize["width"];
					$arElement["FILE"]["HEIGHT"] = $arDestinationSize["height"];
					$arElement["URL_PREVIEW"] = $arElement["URL_GET"] .
						(strpos($arElement['URL_GET'], "?") === false ? "?" : "&") .
						http_build_query($http_query);
					$arResult['ELEMENTS'][$id] = $arElement;

				}
			}
		}
	}
}
?>