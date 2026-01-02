<?php

namespace Bitrix\Crm\ItemMiniCard\Provider\EntityProvider\Trait;

use Bitrix\Crm\ItemMiniCard\Layout\Avatar\ImageAvatar;
use CFile;

trait HasFileImageAvatar
{
	abstract protected function getImageAvatarFileId(): ?int;

	protected function getImageAvatar(): ?ImageAvatar
	{
		$fileId = $this->getImageAvatarFileId();
		if ($fileId === null || $fileId <= 0)
		{
			return null;
		}

		$imageFile = CFile::GetFileArray($fileId);
		if (!is_array($imageFile))
		{
			return null;
		}

		$resizedImageFile = CFile::ResizeImageGet(
			$imageFile,
			[
				'width' => 48,
				'height' => 48,
			],
		);

		$imageSrc = $resizedImageFile['src'] ?? null;
		if (!is_string($imageSrc) || empty($imageSrc))
		{
			return null;
		}

		return new ImageAvatar($imageSrc);
	}
}
