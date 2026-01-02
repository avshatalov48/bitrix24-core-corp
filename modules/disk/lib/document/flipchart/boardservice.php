<?php

declare(strict_types=1);

namespace Bitrix\Disk\Document\Flipchart;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Document\Models\DocumentSession;
use Bitrix\Disk\Document\Models\DocumentSessionContext;
use Bitrix\Disk\Document\Models\DocumentSessionTable;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;
use Bitrix\Disk\Internal\Service\UnifiedLink\UnifiedLinkAccessService;
use Bitrix\Disk\Internal\Service\UnifiedLink\UnifiedLinkSupportService;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\User;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\DI\ServiceLocator;

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

	public static function convertDocumentIdToExternal(int | string $documentId, int | string | null $versionId = null): string
	{
		if ($versionId)
		{
			$documentId .= '.' . $versionId;
		}

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
		return self::getDocumentIdAndVersionFromExternal($documentId)[0];
	}

	public static function getDocumentIdAndVersionFromExternal($documentId): array
	{
		$documentId = explode('-', $documentId);
		$documentId = array_pop($documentId);
		return [$documentId, $versionId] = explode('.', $documentId);
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
			'file' => $file,
		]);

		return $result;
	}

	public static function kickUsers(File $file, array $userIds): void
	{
		if ($file->getTypeFile() != TypeFile::FLIPCHART)
		{
			return;
		}

		$apiService = new BoardApiService();
		$apiService->kickUsers(static::convertDocumentIdToExternal($file->getId()), $userIds);
	}

	/**
	 * @param File|AttachedObject $object
	 * @return DocumentSession[]
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function findUnallowedSessions(File|AttachedObject $object)
	{
		$manager = new SessionManager();
		$unifiedLinkAccessService = ServiceLocator::getInstance()->get(UnifiedLinkAccessService::class);
		$unifiedLinkSupportService = ServiceLocator::getInstance()->get(UnifiedLinkSupportService::class);

		if ($object instanceof File)
		{
			$manager->setFile($object);
			$sessionContext = new DocumentSessionContext(
				(int)$object->getId(),
				null,
				null,
			);
		}
		else
		{
			$manager->setFile($object);
			$sessionContext = new DocumentSessionContext(
				(int)$object->getFileId(),
				(int)$object->getId(),
				null,
			);
		}
		
		$manager->setSessionContext($sessionContext);
		$sessions = $manager->findAllSessions();
		$unallowed = [];

		foreach ($sessions as $session)
		{
			if ($session->getUserId() < 0)
			{
				continue;
			}

			$userId = $session->getUserId();
			if ($object instanceof File)
			{
				$file = $session->getFile();
				$context = $file->getStorage()?->getSecurityContext($session->getUserId());
				$canUpdate = $file->canUpdate($context);
				$canRead = $canUpdate || $file->canRead($context);

				$canUpdateByLink = false;
				$canReadByLink = false;

				if (!$canUpdate && $object->supportsUnifiedLink())
				{
					$unifiedLinkAccessLevel = $unifiedLinkAccessService->check($object, null, $userId);
					$canUpdateByLink = $unifiedLinkAccessLevel === UnifiedLinkAccessLevel::Edit;
					$canReadByLink = $canUpdateByLink || $unifiedLinkAccessLevel === UnifiedLinkAccessLevel::Read;
				}

				if (
					(
						$session->getType() == DocumentSessionTable::TYPE_EDIT
						&& !$canUpdate
						&& !$canUpdateByLink
					)
					|| (
						$session->getType() == DocumentSessionTable::TYPE_VIEW
						&& !$canRead
						&& !$canReadByLink
					)
				)
				{
					$unallowed[] = $session;
				}
			}
			else
			{
				/** @var AttachedObject $object */
				$canUpdate = $object->canUpdate($userId);
				$canRead = $canUpdate || $object->canRead($userId);
				$canUpdateByLink = false;
				$canReadByLink = false;

				if (!$canUpdate && $object->getFile()->supportsUnifiedLink())
				{
					$unifiedLinkAccessLevel = $unifiedLinkAccessService->check($object->getFile(), $object, $userId);
					$canUpdateByLink = $unifiedLinkAccessLevel === UnifiedLinkAccessLevel::Edit;
					$canReadByLink = $canUpdateByLink || $unifiedLinkAccessLevel === UnifiedLinkAccessLevel::Read;
				}

				if (
					(
						$session->getType() == DocumentSessionTable::TYPE_EDIT
						&& !$canUpdate
						&& !$canUpdateByLink
					)
					|| (
						$session->getType() == DocumentSessionTable::TYPE_VIEW
						&& !$canRead
						&& !$canReadByLink
					)
				)
				{
					$unallowed[] = $session;
				}
			}
		}
		
		return $unallowed;
	}

	public static function kickUnallowedUsers(File|AttachedObject $object)
	{
		$sessions = self::findUnallowedSessions($object);
		$userIds = [];
		$needToKickGuests = false;
		foreach ($sessions as $session)
		{
			$userId = $session->getUserId();
			$session->setAsNonActive();
			if ($userId < 0)
			{
				$needToKickGuests = true;
				continue;
			}

			$userIds[] = $userId;
		}
		if ($object instanceof AttachedObject)
		{
			$object = $object->getFile();
		}
		static::kickUsers($object, $userIds);

		if ($needToKickGuests)
		{
			static::kickGuestsUsers($object);
		}
	}

	public static function kickGuestsUsers(File|AttachedObject $object)
	{
		if ($object instanceof AttachedObject)
		{
			$object = $object->getFile();
		}
		$documentId = static::convertDocumentIdToExternal($object->getId());
		$userIds = (new BoardApiService())->getActiveUsersByDocumentId($documentId);

		if (!$userIds)
		{
			return;
		}

		$userIds = array_values(
			array_filter($userIds, static function ($userId) {
				return str_starts_with($userId, '~');
			})
		);

		static::kickUsers($object, $userIds);
	}
}