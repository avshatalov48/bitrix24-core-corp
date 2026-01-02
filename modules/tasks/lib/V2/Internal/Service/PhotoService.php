<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service;

use Bitrix\Tasks\V2\Internal\Entity;
use CFile;

class PhotoService
{
	public function resize(Entity\File $photo, int $width = 102, int $height = 100): ?Entity\File
	{
		$photoData = [
			'SRC' => $photo->src,
			'FILE_NAME' => $photo->name,
			'WIDTH' => $photo->width,
			'HEIGHT' => $photo->height,
			'SUBDIR' => $photo->subDir,
		];

		$resizedPhoto = CFile::resizeImageGet(
			$photoData,
			['width' => $width, 'height' => $height],
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);

		if (!isset($resizedPhoto['src']))
		{
			return null;
		}

		$photoData = array_change_key_case($photoData);

		$newPhotoData = array_merge($photoData, $resizedPhoto);

		return Entity\File::mapFromArray($newPhotoData);
	}
}