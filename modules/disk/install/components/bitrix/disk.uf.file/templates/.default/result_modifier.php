<?php

use Bitrix\Disk\Security\ParameterSigner;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (mb_strpos($this->__page, "show") === 0)
{
	$rebuildCaseKeyParams = function (string $key, array $default) use($arParams) {
		$newArray = $default;
		if (isset($arParams[$key]) && is_array($arParams[$key]))
		{
			$newArray = $arParams[$key];
		}

		return array_change_key_case($newArray, CASE_LOWER);
	};

	$arParams["THUMB_SIZE"] = $rebuildCaseKeyParams("THUMB_SIZE", ["width" => 69, "height" => 69]);
	$arParams["MAX_SIZE"] = $rebuildCaseKeyParams("MAX_SIZE", ["width" => 800, "height" => 800]);
	$arParams["HTML_SIZE"] = $rebuildCaseKeyParams("HTML_SIZE", ["width" => 800, "height" => 800]);
	$arParams["SCREEN_SIZE"] = ["width" => 1024, "height" => 1024];

	$images = $files = $deletedFiles = array();
	foreach ($arResult['FILES'] as $id => $file)
	{
		if($file['IS_MARK_DELETED'])
		{
			$deletedFiles[] = $file;
		}
		elseif (array_key_exists("IMAGE", $file))
		{
			$src = $file["PREVIEW_URL"].(mb_strpos($file["PREVIEW_URL"], "?") === false ? "?" : "&");
			$file["THUMB"] = array(
				"src" => $src.http_build_query(array_merge($arParams["THUMB_SIZE"], array("exact" => "Y", 'signature' => ParameterSigner::getImageSignature($file['ID'], $arParams["THUMB_SIZE"]["width"], $arParams["THUMB_SIZE"]["height"])))),
				"width" => $arParams["THUMB_SIZE"]["width"],
				"height" => $arParams["THUMB_SIZE"]["height"]);
			$file["INLINE"] = array(
				"src" => $src,
				"width" => $file["IMAGE"]["WIDTH"],
				"height" => $file["IMAGE"]["HEIGHT"]);
			$file["BASIC"] = array(
				"src" => $src,
				"width" => $file["IMAGE"]["WIDTH"],
				"height" => $file["IMAGE"]["HEIGHT"]);

			$arSize = $arParams["SIZE"][$file["ID"]] ?? [];
			$arSize = array_change_key_case($arSize, CASE_LOWER);
			$bExactly = (isset($arSize["width"]) && isset($arSize["height"])) && ($arSize["width"] > 0 && $arSize["height"] > 0);

			if (!empty($arParams["MAX_SIZE"]) && ($arParams["MAX_SIZE"]["width"] > 0 || $arParams["MAX_SIZE"]["height"] > 0))
			{
				$circumscribed = array(
					"width" => ($arParams["MAX_SIZE"]["width"] ?: $file["IMAGE"]["WIDTH"]),
					"height" => ($arParams["MAX_SIZE"]["height"] ?: $file["IMAGE"]["HEIGHT"]));
				CFile::ScaleImage(
					$file["IMAGE"]["WIDTH"], $file["IMAGE"]["HEIGHT"],
					$circumscribed, BX_RESIZE_IMAGE_PROPORTIONAL,
					$bNeedCreatePicture,
					$arSourceSize, $arDestinationSize);
				if ($bNeedCreatePicture) {
					$file["INLINE"]["src"] .= http_build_query(array_merge($arDestinationSize, array('signature' => ParameterSigner::getImageSignature($file['ID'], $arDestinationSize["width"], $arDestinationSize["height"]))));
				}

				if ($arParams["HTML_SIZE"])
				{
					$circumscribed1 = array(
						"width" => ($arParams["HTML_SIZE"]["width"] ?: $file["IMAGE"]["WIDTH"]),
						"height" => ($arParams["HTML_SIZE"]["height"] ?: $file["IMAGE"]["HEIGHT"]));
					CFile::ScaleImage(
						$file["IMAGE"]["WIDTH"], $file["IMAGE"]["HEIGHT"],
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

				$file["INLINE"]["width"] = ($bExactly ? $arSize["width"] : $arDestinationSize["width"]);
				$file["INLINE"]["height"] = ($bExactly ? $arSize["height"] : $arDestinationSize["height"]);
			}
			else if ($bExactly)
			{
				$file["INLINE"]["width"] = $arSize["width"];
				$file["INLINE"]["height"] = $arSize["height"];
			}


			if (!empty($arParams["SCREEN_SIZE"]))
			{
				CFile::ScaleImage(
					$file["IMAGE"]["WIDTH"], $file["IMAGE"]["HEIGHT"],
					$arParams["SCREEN_SIZE"], BX_RESIZE_IMAGE_PROPORTIONAL,
					$bNeedCreatePicture,
					$arSourceSize, $arDestinationSize);
				if ($bNeedCreatePicture)
				{
					$file["ORIGINAL"] = $file["BASIC"];
					$file["BASIC"] = array(
						"src" => $file["ORIGINAL"]["src"]."&".http_build_query(array_merge($arParams["SCREEN_SIZE"], array('signature' => ParameterSigner::getImageSignature($file['ID'], $arParams["SCREEN_SIZE"]["width"], $arParams["SCREEN_SIZE"]["height"])))),
						"width" => $arDestinationSize["width"],
						"height" => $arDestinationSize["height"]
					);
				}
			}
			$arResult["FILES"][$id] = $images[$id] = $file;
		}
		else
		{
			$files[$id] = $file;
		}
	}
}
elseif(mb_strpos($this->__page, "edit") === 0)
{
	$http_query = ['width' => 115, 'height' => 115, 'cache_image' => 'Y'];
	foreach ($arResult['FILES'] as $id => $arElement)
	{
		if (
			is_array($arElement)
			&& array_key_exists("IMAGE", $arElement)
		)
		{
			$elementId = ltrim($arElement['ID'], 'n');
			$http_query['signature'] = ParameterSigner::getImageSignature($elementId, $http_query['width'], $http_query['height']);
			CFile::ScaleImage(
				$arElement["IMAGE"]["WIDTH"], $arElement["IMAGE"]["HEIGHT"],
				array("width" => $http_query["width"], "height" => $http_query["height"]),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				$bNeedCreatePicture,
				$arSourceSize, $arDestinationSize);
			if ($bNeedCreatePicture)
			{
				$arElement["original"] = array(
					"src" => $arElement["URL_GET"],
					"width" => $arElement["IMAGE"]["WIDTH"],
					"height" => $arElement["IMAGE"]["HEIGHT"]
				);
				$arElement["IMAGE"]["WIDTH"] = $arDestinationSize["width"];
				$arElement["IMAGE"]["HEIGHT"] = $arDestinationSize["height"];
				if (array_key_exists("PREVIEW_URL", $arElement))
				{
					$arElement["PREVIEW_URL"] .= (mb_strpos($arElement["PREVIEW_URL"], "?") === false ? "?" : "&") . http_build_query($http_query);
				}
				$arResult['FILES'][$id] = $arElement;
			}
		}
	}
}

if ($this->__page == "show")
{
	$arResult['IMAGES'] = $images;
	$arResult['FILES'] = $files;
	$arResult['DELETED_FILES'] = $deletedFiles;
	$arResult['IMAGES_COUNT'] = count($images);
	$arResult['IMAGES_LIMIT'] = 11; // Blocks with preview images. Including a block with a counter.
}
?>