<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\Flipchart;

use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\OnlyOffice\BlankFileData;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Disk\User;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;

class BoardService
{
	protected $session;

	public function __construct(DocumentSession $session)
	{
		$this->session = $session;
	}

	public function closeSession(): bool
	{
		return $this->session->setAsNonActive();
	}

	public static function convertDocumentIdToExternal(int | string $documentId): string
	{
		$id = [
			Configuration::getDocumentIdSalt(),
			SITE_ID,
			$documentId,
		];
		$id = array_filter($id);

		return implode('-', $id);
	}

	public static function getDocumentIdFromExternal($documentId): string
	{
		$documentId = explode('-', $documentId);
		return array_pop($documentId);
	}

	public static function getSiteIdFromExternal($documentId): string
	{
		$documentId = explode('-', $documentId);
		array_pop($documentId);
		return array_pop($documentId);
	}

	public function saveDocument(): Error|bool
	{
		if (!$this->session->getObject())
		{
			return new Error('Could not find the file.');
		}

		$boardId = $this->session->getObject()->getId();
		$boardId = self::convertDocumentIdToExternal($boardId);
		$downloadResult = (new BoardApiService())->downloadBoard("/api/v1/flip/{$boardId}/download");
		if (!$downloadResult->isSuccess())
		{
			return new Error('Could not download the file.');
		}

		$tmpFile = $downloadResult->getData()['file'];
		$tmpFileArray = \CFile::makeFileArray($tmpFile);

		// Dunno what is it
		$options = ['commentAttachedObjects' => false];
		if (!$this->session->getObject()->uploadVersion($tmpFileArray, $this->session->getUserId(), $options))
		{
			return new Error('Could not upload new version of the file.');
		}

		// $this->sendEventToParticipants('saved');
		return true;
	}

	public static function createNewDocument(User $user, Folder $folder, ?string $filename = null): Result
	{
		if (!$filename)
		{
			$filename = Loc::getMessage('DISK_BLANK_FILE_DATA_NEW_FILE_BOARD') . '.board';
		}

		$result = new Result();

		$downloadResult = (new BoardApiService())->downloadBlank();
		if (!$downloadResult->isSuccess())
		{
			$result->addErrors($downloadResult->getErrors());

			return $result;
		}

		$tmpFile = $downloadResult->getData()['file'];
		$fileArray = \CFile::makeFileArray($tmpFile);
		if (!$fileArray)
		{
			$result->addError(new Error('Cannot create file'));

			return $result;
		}

		$fileArray['type'] = 'application/board';
		$fileArray['name'] = $filename;
		$file = $folder->uploadFile(
			$fileArray,
			[
				'NAME' => $filename,
				'CREATED_BY' => $user->getId(),
			],
			[],
			true,
		);

		if (!$file)
		{
			$result->addError(new Error('Cannot save file'));

			return $result;
		}

		$result->setData([
			'file' => $file
		]);

		return $result;
	}
}