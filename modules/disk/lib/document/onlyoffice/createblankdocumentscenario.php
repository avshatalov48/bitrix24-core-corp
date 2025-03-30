<?php

namespace Bitrix\Disk\Document\OnlyOffice;

use Bitrix\Disk\Analytics\DiskAnalytics;
use Bitrix\Disk\Analytics\Enum\DocumentHandlerType;
use Bitrix\Disk\Document\Flipchart\BoardService;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Disk\User;
use Bitrix\Main\Application;
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

		$storage = $targetFolder->getStorage();
		if (!$targetFolder->canAdd($storage->getSecurityContext($this->userId)))
		{
			$result->addError(new Error('Bad rights. Could not add file to the folder.'));

			return $result;
		}

		if ($typeFile === 'board')
		{
			return BoardService::createNewDocument(User::loadById($this->userId), $targetFolder);
		}

		$fileData = new BlankFileData($typeFile, $this->language);

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

		Application::getInstance()->addBackgroundJob(function () use ($newFile) {
			DiskAnalytics::sendCreationFileEvent($newFile, DocumentHandlerType::Bitrix24);
		});

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