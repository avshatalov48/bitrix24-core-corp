<?php

declare(strict_types=1);

namespace Bitrix\Disk\Controller\Response;

use Bitrix\Disk\File;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Engine\Response\ResizedImage;
use Bitrix\Main\Response;

final class PreviewResponseBuilder
{
	public function createByFile(File $file, int $width = 0, int $height = 0, string $exact = null): ?Response
	{
		$isImage = TypeFile::isImage($file);
		if ($isImage)
		{
			$fileName = $file->getName();
			$fileData = $file->getFile();

			return $this->createResizedImageResponse($fileData, $fileName, $file->getName(), $width, $height, $exact);
		}

		if (!$file->getView()->getPreviewData())
		{
			return null;
		}

		$fileName = $file->getView()->getPreviewName();
		$fileData = $file->getView()->getPreviewData();

		return $this->createResizedImageResponse($fileData, $fileName, $file->getName(), $width, $height, $exact);
	}

	private function createResizedImageResponse(array $fileData, string $fileName, string $displayName, int $width, int $height, ?string $exact): ?Response
	{
		if (empty($fileName) || empty($fileData) || !\is_array($fileData))
		{
			return null;
		}

		$response = ResizedImage::createByImageData(
			$fileData,
			$width,
			$height
		);

		$response
			->setResizeType($exact === 'Y' ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL)
			->setName($displayName)
			->setCacheTime(86400)
		;

		return $response;
	}
}
