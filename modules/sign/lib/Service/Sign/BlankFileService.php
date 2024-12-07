<?php

namespace Bitrix\Sign\Service\Sign;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\SystemException;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Fs\File;
use Bitrix\Sign\Item\Fs\FileCollection;
use Bitrix\Sign\Repository\BlankRepository;
use Bitrix\Sign\Service\Container;

class BlankFileService
{
	private BlankRepository $blankRepository;

	private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'rtf', 'odt'];
	private const IMAGE_SIZE = [
		'width' => 1275,
		'height' => 1650,
	];

	/**
	 * @param BlankRepository|null $blankRepository
	 */
	public function __construct(?BlankRepository $blankRepository = null)
	{
		$this->blankRepository = $blankRepository ?? Container::instance()->getBlankRepository();
	}

	/**
	 * @param int $blankId
	 * @param File[] $files
	 *
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function addImagesToBlank(int $blankId, array $files): Result
	{
		$blank = $this->blankRepository->getById($blankId);

		if (!$blank)
		{
			return (new Result())->addError((new \Bitrix\Main\Error('Blank not exists')));
		}

		if (!$blank->fileCollection->first() || !$blank->fileCollection->first()->isImage)
		{
			return (new Result())->addError((new \Bitrix\Main\Error('File not exists')));
		}

		$maxTotalFilesSize = $this->getMaxTotalFilesSize();
		$totalSize = 0;

		foreach ($files as $file)
		{
			$totalSize += $file->size;
		}

		if ($totalSize > $maxTotalFilesSize)
		{
			return (new Result())->addError(
				(new \Bitrix\Main\Error(
					Loc::getMessage('SIGN_IMAGES_BLANK_ERROR_FILE_TOO_BIG', [
						'#SIZE#' => floor($maxTotalFilesSize / 1024 / 1024),
					]), 'FILE_TOO_BIG'
				))
			);
		}

		$totalCount = $blank->fileCollection->count() + count($files);

		$pagesLimit = $this->getPagesLimit();
		if ($totalCount > $pagesLimit)
		{
			return (new Result())->addError(
				(new \Bitrix\Main\Error(
					Loc::getMessage('SIGN_FILE_BLANK_ERROR_TOO_MANY_PAGES', [
						'#COUNT#' => $pagesLimit,
					]), 'FILE_TOO_MANY_PAGES'
				))
			);
		}

		foreach ($files as $itemFile)
		{
			$file = new \Bitrix\Sign\File($itemFile->id);
			if ($file->isImage())
			{
				$file->resizeProportional(self::IMAGE_SIZE);
				// resave resized file
				$file->setId(null);
				$file->save();
				$blank->fileCollection->addItem(
					new \Bitrix\Sign\Item\Fs\File(
						$file->getName(),
						$file->getPath(),
						$file->getExtension(),
						$file->getId(),
					)
				);
			}
		}

		return $this->blankRepository->update($blank);
	}

	public function prepareFile(File $file): Result
	{
		$result = new Result();

		if (!in_array($file->type, self::ALLOWED_EXTENSIONS, true))
		{
			$result->addError(
				new \Bitrix\Main\Error(
					Loc::getMessage('SIGN_CORE_BLANK_ERROR_NOT_ALLOWED_EXTENSIONS'), 'NOT_ALLOWED_EXTENSIONS'
				)
			);

			return $result;
		}

		if ($file->isImage && $file->size > $this->getMaxImageSize())
		{
			$result->addError(
				new \Bitrix\Main\Error(
					Loc::getMessage('SIGN_SERVICE_SIGN_BLANKFILE_IMAGE_FILE_TOO_BIG', [
						// size showed in MB
						'#SIZE#' => floor($this->getMaxImageSize() / 1024 / 1024),
					]), 'FILE_TOO_BIG'
				)
			);

			return $result;
		}
		if (!$file->isImage && $file->size > $this->getMaxFileSize())
		{
			$result->addError(
				new \Bitrix\Main\Error(
					Loc::getMessage('SIGN_SERVICE_SIGN_BLANKFILE_FILE_TOO_BIG', [
						// size showed in MB
						'#SIZE#' => floor($this->getMaxFileSize() / 1024 / 1024),
					]), 'FILE_TOO_BIG'
				)
			);

			return $result;
		}
		$signFile = new \Bitrix\Sign\File($file->id);

		if ($file->isImage)
		{
			$signFile->resizeProportional(self::IMAGE_SIZE);
			$signFile->setId(null);
		}
		$signFile->save();
		$result->setData(['file' => $signFile,]);

		return $result;
	}

	/**
	 * @return int  max file size in bytes
	 */
	private function getMaxTotalFilesSize(): int
	{
		static $maxFilesSize = null;

		if ($maxFilesSize === null)
		{
			$maxFilesSize = Storage::instance()->getUploadTotalMaxSize();
		}

		return $maxFilesSize;
	}

	/**
	 * @return int  max files size in bytes
	 */
	private function getMaxFileSize(): int
	{
		static $maxDocFileSize = null;

		if ($maxDocFileSize === null)
		{
			$maxDocFileSize = Storage::instance()->getUploadDocumentMaxSize();
		}

		return $maxDocFileSize;
	}

	/**
	 * @return int  max image size in bytes
	 */
	private function getMaxImageSize(): int
	{
		return Storage::instance()->getUploadImagesMaxSize();
	}

	/**
	 * @return int
	 */
	private function getPagesLimit(): int
	{
		return Storage::instance()->getImagesCountLimitForBlankUpload();
	}
}