<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

global $CACHE_MANAGER;

foreach ($arResult["ELEMENTS_LIST"] as $key => $arItem)
{
	if (intval($arParams["THUMBNAIL_SIZE"]) > 0)
	{
		$image_resize = CFile::ResizeImageGet(
			($arParams["LIVEFEED_EVENT_ID"] == "photo_photo" ? $arItem["PROPERTIES"]["REAL_PICTURE"]["VALUE"] : $arItem["~PREVIEW_PICTURE"]),
			array(
				"width" => $arParams["THUMBNAIL_SIZE"], 
				"height" => $arParams["THUMBNAIL_SIZE"]
			),
			($arParams["THUMBNAIL_RESIZE_METHOD"] == "EXACT" ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL),
			($arParams["LIVEFEED_EVENT_ID"] == "photo_photo"),
			false,
			true
		);

		$arResult["ELEMENTS_LIST"][$key]["PREVIEW_PICTURE"]["SRC"] = $image_resize["src"];
		if ($arParams["LIVEFEED_EVENT_ID"] == "photo_photo")
		{
			$arResult["ELEMENTS_LIST"][$key]["PREVIEW_PICTURE"]["WIDTH"] = intval($image_resize["width"] / 2);
			$arResult["ELEMENTS_LIST"][$key]["PREVIEW_PICTURE"]["HEIGHT"] = intval($image_resize["height"] / 2);
		}
	}
}

$arResult["SECTION_ELEMENTS_SRC"] = array();

if (
	is_array($arResult["SECTION"])
	&& intval($arResult["SECTION"]["ID"]) > 0
)
{
	if (intval($arParams["LIVEFEED_ID"]) > 0)
	{
		$cache = new CPHPCache;
		$cache_time = 31536000;
		$cache_id = "log_post_mobile_photoalbum1";
		$cache_path = "/sonet/log/".intval(intval($arParams["LIVEFEED_ID"]) / 1000)."/".$arParams["LIVEFEED_ID"]."/entry/";
	}

	if (
		is_object($cache)
		&& $cache->InitCache($cache_time, $cache_id, $cache_path)
	)
	{
		$arCacheVars = $cache->GetVars();
		$arResult["SECTION_ELEMENTS_SRC"] = $arCacheVars["SECTION_ELEMENTS_SRC"];
	}
	else
	{
		if (is_object($cache))
		{
			$cache->StartDataCache($cache_time, $cache_id, $cache_path);

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->startTagCache($cache_path);
				$CACHE_MANAGER->registerTag("SONET_LOG_".intval($arParams["LIVEFEED_ID"]));
			}
		}

		$arFileID = array();

		$arFilter = array(
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			"CHECK_PERMISSIONS" => "Y",
			"SECTION_ID" => $arResult["SECTION"]["ID"], 
			"INCLUDE_SUBSECTIONS" => "N",
			"ACTIVE" => "Y"
		);

		$rsElement = CIBlockElement::getList(array("ID" => "DESC"), $arFilter, false, false, array("ID", "DETAIL_PICTURE", "PROPERTY_REAL_PICTURE"));
		while ($arElement = $rsElement->Fetch())
		{
			if (intval($arElement["PROPERTY_REAL_PICTURE_VALUE"]) > 0)
			{
				$arFileID[] = $arElement["PROPERTY_REAL_PICTURE_VALUE"];
			}
		}

		if (count($arFileID) > 0)
		{
			$strFileID = implode(",", $arFileID);
			$rsFile = CFile::getList(array("ID"=>"DESC"), array("@ID"=>$strFileID));
			while ($arFile = $rsFile->fetch())
			{
				$src = $previewSrc = CFile::getFileSRC($arFile, false, ($arFile["HANDLER_ID"] > 0));

				// preview
				CFile::ScaleImage(
					$arFile["WIDTH"], $arFile["HEIGHT"],
					array("width" => 250, "height" => 250),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					$needCreatePicture,
					$arSourceSize,
					$arDestinationSize
				);

				if ($needCreatePicture)
				{
					$preview = CFile::resizeImageGet(
						$arFile['ID'],
						array('width' => 250, 'height' => 250),
						BX_RESIZE_IMAGE_PROPORTIONAL
					);
					$previewSrc = $preview['src'];
				}

				$arResult["SECTION_ELEMENTS_SRC"][$arFile["ID"]] = array(
					'SRC' => $src,
					'PREVIEW_SRC' => $previewSrc
				);
			}
		}

		if (is_object($cache))
		{
			$arCacheData = Array(
				"SECTION_ELEMENTS_SRC" => $arResult["SECTION_ELEMENTS_SRC"]
			);
			$cache->endDataCache($arCacheData);
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->endTagCache();
			}
		}
	}
}
?>