<?php

namespace Bitrix\Disk\ZipNginx;


use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\Security\SecurityContext;

class Archive extends \Bitrix\Main\Engine\Response\Zip\Archive
{
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

	private function collectDescendants(Folder $folder, SecurityContext $securityContext, $currentPath = '')
	{
		foreach($folder->getChildren($securityContext) as $object)
		{
			if($object instanceof Folder)
			{
				$this->collectDescendants(
					$object,
					$securityContext,
					$currentPath . $object->getName() . '/'
				);

			}
			elseif($object instanceof File)
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
	public function send()
	{
		if ($this->isEmpty())
		{
			(new EmptyArchive($this->name))->send();
		}
		else
		{
			parent::send();
		}
	}
}
