<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Disk\Security\ParameterSigner;

if (!\Bitrix\Main\Loader::includeModule('mobileapp'))
{
	return;
}

if (!function_exists('__mobileGridFormatImage'))
{
	function __mobileGridFormatImage($fileId, $url, $imageParams, $exact = false)
	{
		$result = [
			'src' => '',
			'width' => 0,
			'height' => 0
		];

		if ($url <> '')
		{
			$imageParams['signature'] = ParameterSigner::getImageSignature($fileId, $imageParams['width'], $imageParams['height']);

			if ($exact)
			{
				$imageParams = array_merge($imageParams, [ 'exact' => 'Y' ]);
			}

			$result = [
				'src' => $url.'&'.http_build_query($imageParams),
				'width' => $imageParams['width'],
				'height' => $imageParams['height']
			];
		}

		return $result;
	}
}

$gridLimit = 4;

$thumbSize = [
	'width' => 70,
	'height' => 70
]; // thumb
$maxSize = [
	'width' => 550,
	'height' => 832
]; // inline
$previewSize = [
	'width' => 250,
	'height' => 250
];

$images = [];
$inlineImages = [];
$files = [];
$deletedFiles = [];

$arResult['deviceWidth'] = (CMobile::getInstance()->getDevice() ? (int)CMobile::getInstance()->getDevicewidth() : 1336);
$arResult['deviceHeight'] = (CMobile::getInstance()->getDevice() ? (int)CMobile::getInstance()->getDeviceheight() : 768);
$arResult['devicePixelRatio'] = (CMobile::getInstance()->getDevice() ? (float)CMobile::getInstance()->getPixelRatio() : 1);

$deviceDimension = max([
	$arResult['deviceWidth'],
	$arResult['deviceHeight']
]);

if ($deviceDimension < 650)
{
	$deviceDimension = 650;
}
elseif ($deviceDimension < 1300)
{
	$deviceDimension = 1300;
}
else
{
	$deviceDimension = 2050;
}

$imageCounter = 0;

foreach ($arResult['FILES'] as $id => $file)
{
	if ($file['IS_MARK_DELETED'])
	{
		$deletedFiles[$id] = $file;
	}
	elseif (isset($file['IMAGE']))
	{
		$imageCounter++;

		if ($imageCounter > $gridLimit)
		{
			$file['HIDDEN'] = 'Y';
		}

		$src = '';

		if (isset($file['PREVIEW_URL']))
		{
			$file['PREVIEW_URL'] = str_replace('/bitrix/tools/disk/uf.php', SITE_DIR.'mobile/ajax.php', $file['PREVIEW_URL']);
			$src = $file['PREVIEW_URL'].(mb_strpos($file['PREVIEW_URL'], '?') === false ? '?' : '&').'cache_image=Y&mobile_action=disk_uf_view';
		}

		$file['THUMB'] = __mobileGridFormatImage($file['ID'], $src, $thumbSize, true);

		$file['BASIC'] = $file['PREVIEW'] = [
			'src' => $src,
			'width' => $file['IMAGE']['WIDTH'],
			'height' => $file['IMAGE']['HEIGHT']
		];

		// gallery

		$sourceDimension = max([
			(int)$file['IMAGE']['WIDTH'],
			(int)$file['IMAGE']['HEIGHT']
		]);
		$screenSize = [
			'width' => $sourceDimension,
			'height' => $sourceDimension
		];
		if ($deviceDimension < $sourceDimension)
		{
			$screenSize = [
				'width' => $deviceDimension,
				'height' => $deviceDimension
			];
		}

		CFile::ScaleImage(
			$file['IMAGE']['WIDTH'], $file['IMAGE']['HEIGHT'],
			$screenSize,
			BX_RESIZE_IMAGE_PROPORTIONAL,
			$createPicture,
			$dummy,
			$calculatedSize
		);

		if ($createPicture)
		{
			$screenSize['width'] = $calculatedSize['width'];
			$screenSize['height'] = $calculatedSize['height'];
			$file['BASIC'] = __mobileGridFormatImage($file['ID'], $src, $screenSize, false);
		}

		// preview

		CFile::ScaleImage(
			$file['IMAGE']['WIDTH'], $file['IMAGE']['HEIGHT'],
			$previewSize,
			BX_RESIZE_IMAGE_PROPORTIONAL,
			$createPicture,
			$dummy,
			$calculatedSize
		);

		if ($createPicture)
		{
			$previewSize['width'] = $calculatedSize['width'];
			$previewSize['height'] = $calculatedSize['height'];
			$file['PREVIEW'] = __mobileGridFormatImage($file['ID'], $src, $previewSize, false);
		}

		if (
			!empty($arParams['arUserField'])
			&& !empty($arParams['arUserField']['VALUE_INLINE'])
			&& is_array($arParams['arUserField']['VALUE_INLINE'])
			&& in_array($file['ID'], $arParams['arUserField']['VALUE_INLINE'])
		)
		{
			$inlineImages[] = $file;
		}
		else
		{
			$images[$id] = $file;
		}
	}
	else
	{
		if (isset($file['DOWNLOAD_URL']))
		{
			$file['DOWNLOAD_URL'] = str_replace('/bitrix/tools/disk/uf.php', SITE_DIR.'mobile/ajax.php', $file['DOWNLOAD_URL']);
			$file['DOWNLOAD_URL'] = $file['DOWNLOAD_URL'].(mb_strpos($file['DOWNLOAD_URL'], '?') === false ? '?' : '&').'mobile_action=disk_uf_view&filename='.$file["NAME"];
		}

		$file['NAME_WO_EXTENSION'] = mb_substr($file['NAME'], 0, (mb_strlen($file['NAME']) - mb_strlen($file['EXTENSION']) - 1));

		$files[$id] = $file;
	}
}

$arResult['IMAGES'] = array_values($images);
$arResult['INLINE_IMAGES'] = array_values($inlineImages);
$arResult['FILES'] = $files;
$arResult['FILES_LIMIT'] = 3;
$arResult['DELETED_FILES'] = $deletedFiles;
