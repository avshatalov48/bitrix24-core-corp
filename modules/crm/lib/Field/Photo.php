<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Faceid\FaceTable;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

final class Photo extends Field
{
	protected function processLogic(Item $item, Context $context = null): Result
	{
		$faceId = $item->hasField(Item::FIELD_NAME_FACE_ID) ? $item->getFaceId() : null;

		if (
			$item->isNew()
			&& $this->isItemValueEmpty($item)
			&& $faceId > 0
			&& Loader::includeModule('faceid')
		)
		{
			$face = FaceTable::getRowById($faceId);
			if ($face)
			{
				$photoId = $this->createPhotoFromFace((int)$faceId, (int)($face['FILE_ID'] ?? 0));

				$item->set(
					$this->getName(),
					$photoId,
				);
			}
		}

		return new Result();
	}

	private function createPhotoFromFace(int $faceId, int $faceFileId): ?int
	{
		$fileUploader = Container::getInstance()->getFileUploader();

		$relativePath = $fileUploader->getFilePath($faceFileId);
		if (!$relativePath)
		{
			return null;
		}
		$absolutePath = Path::convertSiteRelativeToAbsolute($relativePath);

		$file = new File($absolutePath);
		if (!$file->isExists())
		{
			return null;
		}

		$photoFileArray = [
			'name' => 'face_' . $faceId . '.jpg',
			'type' => 'image/jpeg',
			'content' => $file->getContents(),
		];

		return $fileUploader->saveFileTemporary($this, $photoFileArray);
	}
}
