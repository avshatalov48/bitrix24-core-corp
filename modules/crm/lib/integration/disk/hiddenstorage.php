<?php

namespace Bitrix\Crm\Integration\Disk;

use Bitrix\Crm\Security\DiskSecurityContext;
use Bitrix\Crm\Service\Container;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Internals\FolderTable;
use Bitrix\Disk\Storage;
use Bitrix\Disk\SystemUser;
use Bitrix\Disk\Ui\Text;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Connection;
use Bitrix\Main\Loader;
use Bitrix\Main\NotSupportedException;
use CFile;

class HiddenStorage
{
	public const USE_DISK_OBJ_ID_AS_KEY = 1;
	public const FOLDER_CODE_ACTIVITY = 'FOR_ACTIVITY_FILES';

	private const NAME = 'CRM_HIDDEN_STORAGE_DISK';
	private const MODULE = 'crm';
	private const LOCK_LIMIT = 10;

	private ?int $userId;
	private Storage $storage;
	private Connection $connection;
	private array $errorCollection;

	public function __construct()
	{
		if (!Loader::includeModule('disk'))
		{
			throw new NotSupportedException('"disk" module is required');
		}

		$this->storage = Driver::getInstance()->addStorageIfNotExist([
			'NAME' => static::NAME,
			'MODULE_ID' => static::MODULE,
			'ENTITY_TYPE' => ProxyType::class,
			'ENTITY_ID' => static::MODULE,
		]);

		if ($this->storage && $this->storage->isEnabledTransformation())
		{
			$this->storage->disableTransformation();
		}

		$this->userId = Container::getInstance()->getContext()->getUserId();
		$this->connection = Application::getConnection();
		$this->errorCollection = [];
	}

	public function setUserId(int $userId): self
	{
		$this->userId = $userId;

		return $this;
	}

	public function setSecurityContextOptions(array $options): self
	{
		if (isset($this->storage))
		{
			/** @var DiskSecurityContext $securityContext */
			$securityContext = $this->storage->getSecurityContext($this->userId);
			if ($securityContext instanceof DiskSecurityContext)
			{
				$securityContext->setOptions($options);
			}
		}

		return $this;
	}

	public function addFilesToRoot(array $fileIds): array
	{
		if (!$this->isAvailableAddFiles($fileIds))
		{
			return [];
		}

		$rootFolder = $this->storage->getRootObject();

		return isset($rootFolder)
			? $this->addFiles($fileIds, $rootFolder)
			: [];
	}

	public function addFilesToFolder(array $fileIds, string $folderCode): array
	{
		if (!$this->isAvailableAddFiles($fileIds))
		{
			return [];
		}

		$folder = $this->findOrCreateFolder($folderCode);

		return isset($folder)
			? $this->addFiles($fileIds, $folder)
			: [];
	}

	public function copyFilesToFolder(array $fileIds, string $folderCode): array
	{
		if (!$this->isAvailableAddFiles($fileIds))
		{
			return [];
		}

		$folder = $this->findOrCreateFolder($folderCode);

		return isset($folder)
			? $this->copyFiles($fileIds, $folder)
			: [];
	}

	public function deleteFiles(array $fileIds): void
	{
		if (empty($fileIds))
		{
			return;
		}

		foreach ($fileIds as $fileId)
		{
			$file = File::getById($fileId);
			if (is_null($file))
			{
				continue; // already removed
			}

			if ($file->getStorage()->getProxyType() instanceof ProxyType)
			{
				$securityContext = $file->getStorage()->getSecurityContext($this->userId);
				if ($file->canDelete($securityContext))
				{
					$file->delete($this->userId);
				}
			}
		}
	}

	public function fetchFileIdsByStorageFileIds(array $storageFileIds, int $options = 0): array
	{
		if (empty($storageFileIds))
		{
			return [];
		}

		$rows = File::getList([
			'select' => ['ID', 'FILE_ID'],
			'filter' => ['=ID' => $storageFileIds]
		]);

		return ($options & self::USE_DISK_OBJ_ID_AS_KEY)
			? array_column($rows->fetchAll(), 'FILE_ID', 'ID')
			: array_values(array_column($rows->fetchAll(), 'FILE_ID'));
	}

	private function copyFiles(array $fileIds, Folder $folder): array
	{
		$result = [];
		foreach ($fileIds as $fileId)
		{
			$sourceFile = (\Bitrix\Disk\File::load(['FILE_ID' => $fileId], ['STORAGE']));

			if (!$sourceFile)
			{
				continue;
			}

			$file = $sourceFile->copyTo($folder, $this->userId, true);

			if ($file instanceof File)
			{
				$result[] = $file;
			}
		}

		return $result;
	}

	private function addFiles(array $fileIds, Folder $folder): array
	{
		$result = [];
		foreach ($fileIds as $fileId)
		{
			$fileData = CFile::getFileArray($fileId);
			if (!is_array($fileData))
			{
				continue;
			}

			$file = $folder->addFile([
				'NAME' => Text::correctFilename($fileData['ORIGINAL_NAME']),
				'FILE_ID' => (int)$fileData['ID'],
				'SIZE' => (int)$fileData['FILE_SIZE'],
				'CREATED_BY' => $this->userId,
			], [], true);
			if ($file instanceof File)
			{
				$result[] = $file;
			}
		}

		return $result;
	}

	private function isAvailableAddFiles(array $fileIds): bool
	{
		if (empty($fileIds))
		{
			return false;
		}

		if (!isset($this->storage))
		{
			return false;
		}

		if (!$this->storage->canAdd($this->storage->getSecurityContext($this->userId)))
		{
			return false;
		}

		return true;
	}

	private function lock(string $lockKey): bool
	{
		return $this->connection->lock($lockKey, self::LOCK_LIMIT);
	}

	private function unlock(string $lockKey): void
	{
		$this->connection->unlock($lockKey);
	}

	private function findOrCreateFolder(string $code): ?Folder
	{
		$parentFolder = Folder::load(['=CODE' => $code, 'STORAGE_ID' => $this->storage->getId()]);
		if (!$parentFolder)
		{
			$parentFolder = $this->storage->addFolder([
				'NAME' => $code,
				'CODE' => $code,
				'CREATED_BY' => SystemUser::SYSTEM_USER_ID
			], [], true);
		}

		if ($parentFolder instanceof Folder)
		{
			$subFolderName = date('Y-m-d');
			$subFolder = $parentFolder->getChild(['=NAME' => $subFolderName, '=TYPE' => FolderTable::TYPE_FOLDER]);
			if ($subFolder)
			{
				return $subFolder;
			}

			$lockKey = sprintf('%s|%s', $code, $subFolderName);
			$this->lock($lockKey);
			$subFolder = $parentFolder->addSubFolder([
				'NAME' => $subFolderName,
				'CREATED_BY' => SystemUser::SYSTEM_USER_ID
			]);

			$this->unlock($lockKey);

			if ($subFolder instanceof Folder)
			{
				return $subFolder;
			}

			$this->errorCollection = array_merge($this->errorCollection, $subFolder->getErrors());

			return null;
		}

		$this->errorCollection = array_merge($this->errorCollection, $parentFolder->getErrors());

		return null;
	}
}
