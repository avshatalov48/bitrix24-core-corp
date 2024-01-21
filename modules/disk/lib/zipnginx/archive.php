<?php

namespace Bitrix\Disk\ZipNginx;


use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Type\ObjectCollection;
use Bitrix\Main\Engine\Response;
use Bitrix\Main\ModuleManager;

class Archive extends Response\Zip\Archive
{
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
}
