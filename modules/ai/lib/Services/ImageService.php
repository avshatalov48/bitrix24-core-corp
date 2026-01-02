<?php declare(strict_types=1);

namespace Bitrix\AI\Services;

use Bitrix\AI\Controller\ImageController;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Engine\Response\BFile;
use Bitrix\Main\Engine\UrlManager;

class ImageService
{
	protected ImageController $imageController;

	public function getImg(int $id, string $hashId): ?BFile
	{
		$file = BFile::createByFileId($id)->showInline(true);
		if ($file)
		{
			$fileData = $file->getFile();
			if (isset($fileData['EXTERNAL_ID']) && $fileData['EXTERNAL_ID'] === $hashId)
			{
				return $file;
			}
		}

		return null;
	}

	/**
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public function getUrlOnImgFile(int $fileId, string $externalId): string
	{
		return (string)UrlManager::getInstance()
			->createByController(
				$this->getImageController(),
				'getImg', /**@see ImageController::getImgAction() */
				[
					'id' => $fileId,
					'hashId' => $externalId,
				],
				true
			)
		;
	}

	protected function getImageController(): ImageController
	{
		if (!isset($this->imageController))
		{
			$this->imageController = new ImageController();
		}

		return $this->imageController;
	}
}
