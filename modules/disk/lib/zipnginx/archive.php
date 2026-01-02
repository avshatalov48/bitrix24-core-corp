<?php

namespace Bitrix\Disk\ZipNginx;

use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Type\ObjectCollection;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\ModuleManager;

class Archive extends Response\Zip\Archive
{
	private const MAX_FILES_IN_ARCHIVE = 1000;

	public static function createByObjects(string $name, ObjectCollection $objectCollection, int $userId): static
	{
		$entryBuilder = new Response\Zip\EntryBuilder();

		$archive = new static($name . '.zip');
		foreach ($objectCollection as $object)
		{
			if ($object instanceof Folder)
			{
				$securityContext = $object->getStorage()?->getSecurityContext($userId);
				if (!$securityContext)
				{
					continue;
				}

				if ($archive->isPossibleUseEmptyDirectory())
				{
					$directory = $entryBuilder->createEmptyDirectory($object->getName());
					$archive->addEntry($directory);
				}

				$archive->collectDescendants($object, $securityContext, $object->getName() . '/');
			}
			if ($object instanceof File)
			{
				$archive->addEntry(ArchiveEntry::createFromFileModel($object));
			}
		}

		return $archive;
	}

	/**
	 * Creates archive which will be copy of folder.
	 * @param Folder          $folder Target folder.
	 * @param SecurityContext $securityContext Security context to getting items.
	 * @return static
	 */
	public static function createFromFolder(Folder $folder, SecurityContext $securityContext)
	{
		$archive = new static($folder->getName() . '.zip');
		$archive->collectDescendants($folder, $securityContext);

		return $archive;
	}

	private function collectDescendants(Folder $folder, SecurityContext $securityContext, string $currentPath = ''): void
	{
		$entryBuilder = new Response\Zip\EntryBuilder();

		foreach ($folder->getChildren($securityContext) as $object)
		{
			if ($object instanceof Folder)
			{
				if ($this->isPossibleUseEmptyDirectory())
				{
					$directory = $entryBuilder->createEmptyDirectory($currentPath . $object->getName());
					$this->addEntry($directory);
				}

				$this->collectDescendants(
					$object,
					$securityContext,
					$currentPath . $object->getName() . '/'
				);

			}
			elseif ($object instanceof File)
			{
				$this->addEntry(ArchiveEntry::createFromFileModel($object, $currentPath . $object->getName()));
			}
		}
	}

	private function isPossibleUseEmptyDirectory(): bool
	{
		//right now we can use empty directory only in bitrix24, because we know that version of mod_zip is 1.3.0
		//in future we can check version of mod_zip and use empty directory
		return ModuleManager::isModuleInstalled('bitrix24');
	}

	/**
	 * Sends content to output stream and sets necessary headers.
	 *
	 * @return void
	 */
	public function send(): void
	{
		if ($this->isEmpty())
		{
			$this->getHeaders()->delete('X-Archive-Files');

			$emptyArchive = new EmptyArchive($this->name);
			$emptyArchive->copyHeadersTo($this);
			$emptyArchive->send();
		}
		else
		{
			parent::send();
		}
	}

	/**
	 * Checks if the total number of files in the given object collection exceeds the maximum allowed.
	 *
	 * @param ObjectCollection $objectCollection Collection of files and folders to check.
	 * @param int $userId User ID for security context resolution.
	 *
	 * @return bool True if the file limit is exceeded, false otherwise.
	 */
	public static function isFileLimitExceededByObjects(ObjectCollection $objectCollection, int $userId): bool
	{
		$fileCount = 0;
		$maxFilesInArchive = self::getMaxFilesInArchive();

		foreach ($objectCollection as $object)
		{
			if ($object instanceof File)
			{
				$fileCount++;
			}

			if ($object instanceof Folder)
			{
				$securityContext = $object->getStorage()?->getSecurityContext($userId);
				if ($securityContext)
				{
					$fileCount += self::countFilesInFolder($object, $securityContext);
				}
			}

			if ($fileCount > $maxFilesInArchive)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if the number of files in the specified folder (including descendants) exceeds the maximum allowed.
	 *
	 * @param Folder $folder Target folder to check.
	 * @param SecurityContext $securityContext Security context for accessing folder contents.
	 *
	 * @return bool True if the file limit is exceeded, false otherwise.
	 */
	public static function isFileLimitExceededByFolder(Folder $folder, SecurityContext $securityContext): bool
	{
		$fileCount = self::countFilesInFolder($folder, $securityContext);

		return $fileCount > self::getMaxFilesInArchive();
	}

	/**
	 * Counts the number of files in the given folder and its descendants, using the provided security context.
	 * If the number of files exceeds the maximum allowed, the method may return a value greater than the real count.
	 *
	 * @param Folder $folder Target folder to count files in.
	 * @param SecurityContext $securityContext Security context for accessing folder contents.
	 *
	 * @return int Number of files found, or a value greater than the real count if the limit is exceeded.
	 */
	private static function countFilesInFolder(Folder $folder, SecurityContext $securityContext): int
	{
		$fileCount = 0;
		$maxFilesInArchive = self::getMaxFilesInArchive();

		foreach ($folder->getDescendants($securityContext) as $child)
		{
			if ($child instanceof File)
			{
				$fileCount++;
			}

			if ($fileCount > $maxFilesInArchive)
			{
				break;
			}
		}

		return $fileCount;
	}

	/**
	 * Returns the maximum number of files allowed in the archive.
	 * Gets value from option 'max_files_in_archive' or uses default from constant.
	 *
	 * @return int
	 */
	private static function getMaxFilesInArchive(): int
	{
		return (int)Option::get('disk', 'max_files_in_archive', self::MAX_FILES_IN_ARCHIVE);
	}
}
