<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!\Bitrix\Main\Loader::includeModule('mobileapp'))
{
	return;
}

if (strpos($this->__page, "show") !== 0)
{
	return;
}

$arParams["THUMB_SIZE"] = array("width" => 70, "height" => 70); // thumb
$arParams["MAX_SIZE"] = array("width" => 550, "height" => 832); // inline
$arParams["SMALL_SIZE"] = array("width" => 75, "height" => 100); // inline rough

$arParams["HTML_SIZE"] = (!!$arParams["HTML_SIZE"] ? $arParams["HTML_SIZE"] : false); // inline from parser

$images = $files = $deletedFiles = array();

$max_dimension = max(array(intval(CMobile::getInstance()->getDevicewidth()), intval(CMobile::getInstance()->getDeviceheight())));
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

foreach ($arResult['FILES'] as $id => $file)
{
	if (
		!empty($arParams['arUserField'])
		&& !empty($arParams['arUserField']['VALUE_INLINE'])
		&& is_array($arParams['arUserField']['VALUE_INLINE'])
		&& in_array($file['ID'], $arParams['arUserField']['VALUE_INLINE'])
	)
	{
		$file['HIDDEN'] = 'Y';
	}

	if (isset($file["PREVIEW_URL"]))
	{
		$file["PREVIEW_URL"] = str_replace("/bitrix/tools/disk/uf.php", SITE_DIR."mobile/ajax.php", $file["PREVIEW_URL"]);
	}

	if (isset($file["DOWNLOAD_URL"]))
	{
		$file["DOWNLOAD_URL"] = str_replace("/bitrix/tools/disk/uf.php", SITE_DIR."mobile/ajax.php", $file["DOWNLOAD_URL"]);
		$file["DOWNLOAD_URL"] = $file["DOWNLOAD_URL"].(strpos($file["DOWNLOAD_URL"], "?") === false ? "?" : "&")."mobile_action=disk_uf_view&filename={$file['NAME']}";
	}

	if (isset($file["PATH"]))
	{
		$file["PATH"] = str_replace("/bitrix/tools/disk/uf.php", SITE_DIR."mobile/ajax.php", $file["PATH"]);
		$file["PATH"] = $file["PATH"].(strpos($file["PATH"], "?") === false ? "?" : "&")."mobile_action=disk_uf_view";
	}

	if($file['IS_MARK_DELETED'])
	{
		$arResult["FILES"][$id] = $deletedFiles[$id] = $file;
	}
	elseif (array_key_exists("IMAGE", $file))
	{
		$src = $file["PREVIEW_URL"].(strpos($file["PREVIEW_URL"], "?") === false ? "?" : "&")."cache_image=Y&mobile_action=disk_uf_view";
		$arParams["THUMB_SIZE"]["signature"] = \Bitrix\Disk\Security\ParameterSigner::getImageSignature($file["ID"], $arParams["THUMB_SIZE"]["width"], $arParams["THUMB_SIZE"]["height"]);

		$file["THUMB"] = array(
			"src" => $src."&".http_build_query(array_merge($arParams["THUMB_SIZE"], array("exact" => "Y"))),
			"width" => $arParams["THUMB_SIZE"]["width"],
			"height" => $arParams["THUMB_SIZE"]["height"]
		);

		$file["INLINE"] = array(
			"src" => $src,
			"width" => $file["IMAGE"]["WIDTH"],
			"height" => $file["IMAGE"]["HEIGHT"]
		);


		$file["SMALL"] = array(
			"src" => $src
		);

		$file["BASIC"] = array(
			"src" => $src,
			"width" => $file["IMAGE"]["WIDTH"],
			"height" => $file["IMAGE"]["HEIGHT"]
		);

		$arSize = is_array($arParams["SIZE"][$file["ID"]]) ? $arParams["SIZE"][$file["ID"]] : array();
		$arSize = array(
			"width" => intval(array_key_exists("width", $arSize) ? $arSize["width"] : $arSize["WIDTH"]),
			"height" => intval(array_key_exists("height", $arSize) ? $arSize["height"] : $arSize["HEIGHT"])
		);

		$bExactly = ($arSize["width"] > 0 && $arSize["height"] > 0);

		// inline rough

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

		$arSmallSize["signature"] = \Bitrix\Disk\Security\ParameterSigner::getImageSignature($file["ID"], $arSmallSize["width"], $arSmallSize["height"]);
		$arResult["FILES"][$id]["SMALL"]["src"] .= "&width=".$arSmallSize["width"]."&height=".$arSmallSize["height"]."&signature=".$arSmallSize["signature"];

		// inline

		$circumscribed = array(
			"width" => ($arParams["MAX_SIZE"]["width"] > 0 ? $arParams["MAX_SIZE"]["width"] : $file["IMAGE"]["WIDTH"]),
			"height" => ($arParams["MAX_SIZE"]["height"] > 0 ? $arParams["MAX_SIZE"]["height"] : $file["IMAGE"]["HEIGHT"])
		);

		CFile::ScaleImage(
			$file["IMAGE"]["WIDTH"], $file["IMAGE"]["HEIGHT"],
			$circumscribed,
			BX_RESIZE_IMAGE_PROPORTIONAL,
			$bNeedCreatePicture,
			$arSourceSize,
			$arDestinationSize
		);

		if ($bNeedCreatePicture)
		{
			$arDestinationSize["signature"] = \Bitrix\Disk\Security\ParameterSigner::getImageSignature($file["ID"], $arDestinationSize["width"], $arDestinationSize["height"]);
			$file["INLINE"]["src"] .= "&".http_build_query($arDestinationSize);
		}

		if ($arParams["HTML_SIZE"])
		{
			$circumscribed1 = array(
				"width" => ($arParams["HTML_SIZE"]["width"] > 0 ? $arParams["HTML_SIZE"]["width"] : $file["IMAGE"]["WIDTH"]),
				"height" => ($arParams["HTML_SIZE"]["height"] > 0 ? $arParams["HTML_SIZE"]["height"] : $file["IMAGE"]["HEIGHT"])
			);

			CFile::ScaleImage(
				$file["IMAGE"]["WIDTH"], $file["IMAGE"]["HEIGHT"],
				$circumscribed1,
				BX_RESIZE_IMAGE_PROPORTIONAL,
				$bNeedCreatePicture,
				$arSourceSize,
				$arDestinationSize1
			);

			if (
				$arDestinationSize1["width"] < $arDestinationSize["width"]
				|| $arDestinationSize1["height"] < $arDestinationSize["height"]
			)
			{
				$arDestinationSize = $arDestinationSize1;
				$circumscribed = $circumscribed1;
			}
		}

		if ($bExactly && $circumscribed)
		{
			CFile::ScaleImage(
				$arSize["width"], $arSize["height"],
				$circumscribed, BX_RESIZE_IMAGE_PROPORTIONAL,
				$bNeedCreatePicture,
				$arSourceSize,
				$arSize
			);
		}

		$file["INLINE"]["width"] = ($bExactly ? $arSize["width"] : $arDestinationSize["width"]);
		$file["INLINE"]["height"] = ($bExactly ? $arSize["height"] : $arDestinationSize["height"]);

		// gallery

		$max_real_dimension = max(array(intval($file["IMAGE"]["WIDTH"]), intval($file["IMAGE"]["HEIGHT"])));
		$arParams["SCREEN_SIZE"] = (
			$max_real_dimension > $max_dimension
				? array("width" => $max_dimension, "height" => $max_dimension)
				: array("width" => $max_real_dimension, "height" => $max_real_dimension)
		);

		CFile::ScaleImage(
			$file["IMAGE"]["WIDTH"], $file["IMAGE"]["HEIGHT"],
			$arParams["SCREEN_SIZE"],
			BX_RESIZE_IMAGE_PROPORTIONAL,
			$bNeedCreatePicture,
			$arSourceSize,
			$arDestinationSize
		);

		if ($bNeedCreatePicture)
		{
			$arParams["SCREEN_SIZE"]["signature"] = \Bitrix\Disk\Security\ParameterSigner::getImageSignature($file["ID"], $arParams["SCREEN_SIZE"]["width"], $arParams["SCREEN_SIZE"]["height"]);

			$file["BASIC"]["src"] .= "&".http_build_query($arParams["SCREEN_SIZE"]);
			$file["BASIC"]["width"] = $arDestinationSize["width"];
			$file["BASIC"]["height"] = $arDestinationSize["height"];
		}

		// preview
		$file["PREVIEW"] = array(
			"src" => $src,
			"width" => $file["IMAGE"]["WIDTH"],
			"height" => $file["IMAGE"]["HEIGHT"]
		);

		$arParams["PREVIEW_SIZE"] = array("width" => 250, "height" => 250);
		CFile::ScaleImage(
			$file["IMAGE"]["WIDTH"], $file["IMAGE"]["HEIGHT"],
			$arParams["PREVIEW_SIZE"],
			BX_RESIZE_IMAGE_PROPORTIONAL,
			$bNeedCreatePicture,
			$arSourceSize,
			$arDestinationSize
		);

		if ($bNeedCreatePicture)
		{
			$arParams["PREVIEW_SIZE"]["signature"] = \Bitrix\Disk\Security\ParameterSigner::getImageSignature($file["ID"], $arParams["PREVIEW_SIZE"]["width"], $arParams["PREVIEW_SIZE"]["height"]);
			$file["PREVIEW"]["src"] .= "&".http_build_query($arParams["PREVIEW_SIZE"]);
			$file["PREVIEW"]["width"] = $arDestinationSize["width"];
			$file["PREVIEW"]["height"] = $arDestinationSize["height"];
		}

		$arResult["FILES"][$id] = $images[$id] = $file;
	}
	else
	{
		$arResult["FILES"][$id] = $files[$id] = $file;
	}
}

if ($this->__page == "show")
{
	$arResult['IMAGES'] = $images;
	$arResult['FILES'] = $files;
	$arResult['DELETED_FILES'] = $deletedFiles;
}