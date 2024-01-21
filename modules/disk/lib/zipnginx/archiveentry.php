<?php

namespace Bitrix\Disk\ZipNginx;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Main\Engine\Response\Zip;
use Bitrix\Main\Engine\Response\Zip\FileEntry;
use Bitrix\Main\NotImplementedException;

class ArchiveEntry extends Zip\ArchiveEntry
{
	public static function createFromFileModel(File $file, string $path = null): Zip\FileEntry
	{
		return (new Zip\EntryBuilder())->createFromFileArray($file->getFile(), $path ? : $file->getName());
	}

	/**
	 * Creates Entry from attached object.
	 *
	 * @param AttachedObject $attachedObject Attached object.
	 *
	 * @return FileEntry
	 * @throws NotImplementedException
	 */
	public static function createFromAttachedObject(AttachedObject $attachedObject): Zip\FileEntry
	{
		if ($attachedObject->isSpecificVersion())
		{
			$version = $attachedObject->getVersion();

			return (new Zip\EntryBuilder())->createFromFileArray($version->getFile(), $version->getName());
		}

		return static::createFromFileModel($attachedObject->getFile());
	}
}
