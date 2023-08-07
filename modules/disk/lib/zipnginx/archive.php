<?php

namespace Bitrix\Disk\ZipNginx;


use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Security\SecurityContext;
use Bitrix\Disk\Type\ObjectCollection;
use Bitrix\Main\Engine\Response;

class Archive extends Response\Zip\Archive
{
	public static function createByObjects(string $name, ObjectCollection $objectCollection, int $userId): static
	{
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
		foreach ($folder->getChildren($securityContext) as $object)
		{
			if ($object instanceof Folder)
			{
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
