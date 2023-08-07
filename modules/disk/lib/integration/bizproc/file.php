<?php

namespace Bitrix\Disk\Integration\Bizproc;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Uf\FileUserType;

class File
{
	private \Bitrix\Disk\File $file;

	private function __construct(\Bitrix\Disk\File $file)
	{
		$this->file = $file;
	}

	public static function openById(int $fileId): Result
	{
		$file = \Bitrix\Disk\File::loadById($fileId);
		if (!$file)
		{
			return Result::createFromErrorCode(Error::FILE_NOT_FOUND);
		}

		return Result::createOk(['file' => new static($file)]);
	}

	public static function uploadUserFile(\Bitrix\Bizproc\File $file, int $userId): Result
	{
		if ($userId <= 0)
		{
			return Result::createFromErrorCode(Error::USER_NOT_FOUND);
		}

		$storage = Driver::getInstance()->getStorageByUserId($userId);
		if (!$storage)
		{
			return Result::createFromErrorCode(Error::OBTAINING_STORAGE);
		}

		$folder = $storage->getFolderForUploadedFiles();
		if (!$folder)
		{
			return Result::createFromErrorCode(Error::FOLDER_ERROR);
		}

		$securityContext = $storage->getSecurityContext($userId);
		if (!$folder->canAdd($securityContext))
		{
			return Result::createFromErrorCode(Error::ACCESS_DENIED);
		}

		$diskFile = $folder->uploadFile($file->getFileArray(), ['CREATED_BY' => $userId], [], true);
		if (!$diskFile)
		{
			$error = $folder->getErrors()[0];

			return Result::createFromErrorCode(Error::FILE_NOT_ADDED, ['reason' => $error->getMessage()]);
		}

		return Result::createOk([
			'file' => new static($diskFile),
			'diskFile' => $diskFile,
			'attachmentId' => FileUserType::NEW_FILE_PREFIX . $diskFile->getId(),
		]);
	}

	public function copy(): Result
	{
		$folder = $this->file->getParent();
		if (!$folder)
		{
			return Result::createFromErrorCode(Error::FOLDER_ERROR);
		}

		$newFile = $this->file->copyTo($folder, $this->file->getCreatedBy(), true);
		if (!$newFile)
		{
			return Result::createFromErrorCode(Error::FILE_NOT_ADDED);
		}

		return Result::createOk([
			'file' => new static($newFile),
			'diskFile' => $newFile,
			'attachmentId' => FileUserType::NEW_FILE_PREFIX . $newFile->getId(),
		]);
	}
}