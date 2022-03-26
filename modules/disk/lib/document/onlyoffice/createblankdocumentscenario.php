<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class CreateBlankDocumentScenario
{
	/**
	 * @var int
	 */
	private $userId;
	/**
	 * @var string
	 */
	private $language;

	public function __construct(int $userId, string $language)
	{
		$this->userId = $userId;
		$this->language = $language;
	}

	protected function getDefaultFolderForUser(): Folder
	{
		$userStorage = Driver::getInstance()->getStorageByUserId($this->userId);

		return $userStorage->getFolderForCreatedFiles();
	}

	public function createBlank(string $typeFile, Folder $targetFolder): Result
	{
		$result = new Result();
		$fileData = new BlankFileData($typeFile, $this->language);

		$storage = $targetFolder->getStorage();
		if (!$targetFolder->canAdd($storage->getSecurityContext($this->userId)))
		{
			$result->addError(new Error('Bad rights. Could not add file to the folder.'));

			return $result;
		}

		$newFile = $targetFolder->uploadFile(
			\CFile::makeFileArray($fileData->getSrc()),
			[
				'NAME' => $fileData->getName(),
				'CREATED_BY' => $this->userId,
			],
			[],
true
		);

		if (!$newFile)
		{
			$result->addErrors($targetFolder->getErrors());

			return $result;
		}

		$result->setData([
			'file' => $newFile,
		]);

		return $result;
	}

	public function createBlankInDefaultFolder(string $typeFile): Result
	{
		return $this->createBlank($typeFile, $this->getDefaultFolderForUser());
	}
}