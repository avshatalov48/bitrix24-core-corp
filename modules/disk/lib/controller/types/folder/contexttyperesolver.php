<?php
declare(strict_types=1);

namespace Bitrix\Disk\Controller\Types\Folder;

use Bitrix\Disk\Folder;
use Bitrix\Disk\Integration\Collab\CollabService;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * Class ContextTypeResolver.
 * Resolves context type for folder.
 * Used to resolve context type for folder when we use getChildren() method in mobile.
 * @see \Bitrix\Disk\Controller\Folder::getChildrenAction()
 */
final class ContextTypeResolver
{
	private \SplObjectStorage $unresolved;
	private \SplObjectStorage $resolved;

	public function __construct(
		private readonly int $initialStorageId
	)
	{
		$this->unresolved = new \SplObjectStorage();
		$this->resolved = new \SplObjectStorage();
	}

	/**
	 * Resolve all pending folders.
	 * @return void
	 * @throws ArgumentException
	 * @throws LoaderException
	 * @throws NotImplementedException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function resolveAllPending(): void
	{
		$basicFolders = [];
		$symlinkFolderIds = [];
		foreach ($this->unresolved as $folder)
		{
			if (!($folder instanceof Folder))
			{
				continue;
			}

			if (!$folder->isLink())
			{
				$basicFolders[] = $folder;
				continue;
			}

			$symlinkFolderIds[] = $folder->getRealObjectId();
		}

		$collabService = new CollabService();
		foreach ($basicFolders as $basicFolder)
		{
			$contextForBasicFolder = ContextType::Basic;
			$storageId = (int)$basicFolder->getStorageId();
			if ($storageId !== $this->initialStorageId)
			{
				$storage = $basicFolder->getStorage();
				if ($storage)
				{
					if ($collabService->isCollabStorage($storage))
					{
						$contextForBasicFolder = ContextType::Collab;
					}
					elseif ($collabService->isGroupStorage($storage))
					{
						$contextForBasicFolder = ContextType::Group;
					}
					else
					{
						$contextForBasicFolder = ContextType::Sharing;
					}
				}
			}

			$this->resolved->attach($basicFolder, $contextForBasicFolder);
		}

		if (empty($symlinkFolderIds))
		{
			$this->unresolved = new \SplObjectStorage();

			return;
		}

		$query = Folder::getList([
			'filter' => [
				'@ID' => $symlinkFolderIds,
			],
			'select' => [
				'ID',
				'STORAGE_ID',
				'PARENT_ID',
			],
		]);

		$mapById = [];
		foreach ($query as $folderRow)
		{
			$mapById[$folderRow['ID']] = $folderRow;
		}

		foreach ($this->unresolved as $folder)
		{
			if (!isset($mapById[$folder->getRealObjectId()]))
			{
				continue;
			}

			$realFolder = $mapById[$folder->getRealObjectId()];
			if ($this->initialStorageId === (int)$realFolder['STORAGE_ID'])
			{
				$this->resolved->attach($folder, ContextType::Basic);
				continue;
			}

			// if folder is not root, then it is not a group connection and it is a sharing
			if (!empty($realFolder['PARENT_ID']))
			{
				$this->resolved->attach($folder, ContextType::Sharing);
				continue;
			}

			if ($collabService->isCollabStorageById((int)$realFolder['STORAGE_ID']))
			{
				$this->resolved->attach($folder, ContextType::Collab);
				continue;
			}

			$this->resolved->attach($folder, ContextType::Group);
		}

		$this->unresolved = new \SplObjectStorage();
	}

	/**
	 * Resolve lazy context type for folder.
	 * Lazy context type is used to resolve context type only when it is needed.
	 * @param Folder $folder Folder.
	 * @return LazyContextType
	 */
	public function resolveByLazyContextType(Folder $folder): LazyContextType
	{
		if (!$this->unresolved->contains($folder))
		{
			$this->unresolved->attach($folder);
		}

		return (new LazyContextType($folder, $this));
	}

	/**
	 * Resolve context type for folder.
	 * @param Folder $folder Folder.
	 * @return ContextType
	 */
	public function resolveContextType(Folder $folder): ContextType
	{
		if ($this->resolved->contains($folder))
		{
			return $this->resolved[$folder];
		}

		return ContextType::Basic;
	}
}