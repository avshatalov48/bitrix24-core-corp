<?php
namespace Bitrix\Recyclebin\Internals;

class UI
{
	public static function getAvatar($fileId, $width = 50, $height = 50)
	{
		$fileId = intval($fileId);
		if ($fileId < 1) {
			return "";
		}

		$file = \CFile::GetFileArray($fileId);
		if ($file !== false)
		{
			$fileInfo = \CFile::ResizeImageGet(
				$file,
				array("width" => $width, "height" => $height),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			return $fileInfo["src"];
		}

		return "";
	}
}