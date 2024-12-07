<?php

namespace Bitrix\Crm\Activity\Mail;

use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main;
use Bitrix\Main\Loader;

final class MailEntitiesDiskHelper
{
	/**
	 * @var array<int, int>
	 */
	private array $fileIdsArray = [];
	/**
	 * @var array<int, int>
	 */
	private array $attachToFileIds = [];
	/**
	 * @var array<int, int>
	 */
	private array $templateArFileIds = [];
	/**
	 * @var array<int, int>
	 */
	private array $templateAttachToFileIds = [];

	/**
	 * @param array<array-key, int|string> $diskFiles
	 * @param bool $saveAsTemplate
	 * @param array<array-key, int|string> $filesWithoutPermissionCheck
	 */
	public function __construct(
		private readonly array $diskFiles,
		private readonly bool $saveAsTemplate = false,
		private readonly array $filesWithoutPermissionCheck = [],
	)
	{}

	/**
	 * prepares arFileIds and attachToFileIds for provided mail crm entities files
	 * @return void
	 */
	public function prepareCrmEntitiesFiles(): void
	{
		if (!Loader::includeModule('disk'))
		{
			return;
		}

		$userId = Main\Engine\CurrentUser::get()->getId();

		if (is_null($userId))
		{
			return;
		}

		$driver = Driver::getInstance();

		$folder =  $driver->getStorageByUserId($userId)?->getFolderForUploadedFiles();
		if (!$folder)
		{
			return;
		}

		[
			$shouldForkNewFiles,
			$shouldForkOldFiles,
			$fileToAttachObjectIds,
		] = $this->prepareForkFiles($driver, $userId);

		[
			$forkedArFileIds,
			$this->attachToFileIds,
		] = $this->copyFiles($shouldForkOldFiles, $fileToAttachObjectIds, $folder, $userId);
		$this->fileIdsArray = array_replace($this->fileIdsArray, $forkedArFileIds);

		if ($this->saveAsTemplate)
		[
			$this->templateArFileIds,
			$this->templateAttachToFileIds,
		] = $this->copyFiles(array_replace($shouldForkOldFiles, $shouldForkNewFiles), $fileToAttachObjectIds, $folder, $userId);
	}

	/**
	 * prepares the files that will need to be copied
	 * @param Driver $driver
	 * @param int $userId
	 * @return array{array<int,File>, array<int,File>, array<int,int>} - [$shouldForkNewFiles, $shouldForkOldFiles, $fileToAttachObjectIds]
	 */
	private function prepareForkFiles(Driver $driver,int $userId): array
	{
		$attachedObjectIds = [];

		$shouldForkNewFiles = [];
		$shouldForkOldFiles = [];
		$fileToAttachObjectIds = [];

		foreach ($this->diskFiles as $value)
		{
			if (!$value)
			{
				continue;
			}

			[$type, $realValue] = FileUserType::detectType($value);
			$realValue = (int)$realValue;
			if ($type === FileUserType::TYPE_NEW_OBJECT)
			{
				$file = File::getById($realValue);
				if (!$file || !$file->getStorage())
				{
					continue;
				}

				$securityContext = $file->getStorage()->getSecurityContext($userId);
				$moduleId = $file->getStorage()->getModuleId();

				if (in_array($value, $this->filesWithoutPermissionCheck, true))
				{
					$shouldSkipReadPermissionsCheck = true;
				}
				//todo remove this if-branch when documentgenerator update is out
				elseif ($moduleId === 'documentgenerator')
				{
					$canReadWorksCorrectlyOnDocuments =
						Loader::includeModule('documentgenerator')
						&& class_exists('\Bitrix\DocumentGenerator\Integration\Disk\SecurityContext')
					;

					$shouldSkipReadPermissionsCheck = !$canReadWorksCorrectlyOnDocuments;
				}
				else
				{
					$shouldSkipReadPermissionsCheck = false;
				}

				if (!$shouldSkipReadPermissionsCheck && !$file->canRead($securityContext))
				{
					continue;
				}

				$fileId = (int)$file->getId();
				$this->fileIdsArray[$fileId] = $fileId;

				if (!$this->saveAsTemplate)
				{
					continue;
				}

				$shouldForkNewFiles[$fileId] = $file;
				$fileToAttachObjectIds[$fileId] = $fileId;
			}
			elseif ($type === FileUserType::TYPE_ALREADY_ATTACHED)
			{
				$attachedObjectIds[] = $realValue;
			}
		}

		if ($attachedObjectIds)
		{
			$userFieldManager = $driver->getUserFieldManager();
			$userFieldManager->loadBatchAttachedObject($attachedObjectIds);
			foreach ($attachedObjectIds as $attachedObjectId)
			{
				$attachedObject = $userFieldManager->getAttachedObjectById($attachedObjectId);
				if (!$attachedObject)
				{
					continue;
				}

				if (
					!in_array($attachedObjectId, $this->filesWithoutPermissionCheck, true)
					&& !$attachedObject->canRead($userId)
				)
				{
					continue;
				}

				$file = $attachedObject->getFile();
				if (!$file)
				{
					continue;
				}

				$fileId = (int)$file->getId();
				$shouldForkOldFiles[$fileId] = $file;
				$fileToAttachObjectIds[$fileId] = $attachedObjectId;
			}
		}

		return [$shouldForkNewFiles, $shouldForkOldFiles, $fileToAttachObjectIds];
	}

	/**
	 * @param array<int, File> $shouldForkFiles
	 * @param array<int, int> $fileToAttachObjectIds
	 * @param Folder $folder
	 * @param int $userId
	 * @return array{array<int,int>, array<int,int>} - [$arFileIds, $attachToFileIds]
	 */
	private function copyFiles(array $shouldForkFiles, array $fileToAttachObjectIds, Folder $folder, int $userId): array
	{
		$fileIdsArray = [];
		$attachToFileIds = [];

		foreach ($shouldForkFiles as $file)
		{
			$forkedFile = $file->copyTo($folder, $userId, true);
			if (!$forkedFile)
			{
				continue;
			}

			$fileId = (int)$file->getId();
			$forkedFileId = (int)$forkedFile->getId();
			$fileIdsArray[$fileId] = $forkedFileId;
			if (isset($fileToAttachObjectIds[$fileId]))
			{
				$attachToFileIds[$forkedFileId] = $fileToAttachObjectIds[$fileId];
			}
		}

		return [$fileIdsArray, $attachToFileIds];
	}


	/**
	 * returns an array of id for mail activity
	 * @return array<int, int> - original file id => copied file id
	 */
	public function getArFileIds(): array
	{
		return $this->fileIdsArray;
	}

	/**
	 * returns an array of mail activity attached files ids that need to be replaced in the body of the letter
	 * @return array<int, int> - copied file id => original file id
	 */
	public function getAttachToFileIds(): array
	{
		return $this->attachToFileIds;
	}

	/**
	 * returns an array of id for mail template
	 * @return array<int, int> - original file id => copied file id
	 */
	public function getTemplateArFileIds(): array
	{
		return $this->templateArFileIds;
	}

	/**
	 * returns an array of mail activity attached files ids that need to be replaced in the body of the letter
	 * @return array<int, int> - copied file id => original file id
	 */
	public function getTemplateAttachToFileIds(): array
	{
		return $this->templateAttachToFileIds;
	}
}
