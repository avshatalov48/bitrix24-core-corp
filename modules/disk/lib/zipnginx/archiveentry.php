<?php

namespace Bitrix\Disk\ZipNginx;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Main\Engine\Response\Zip;

class ArchiveEntry extends Zip\ArchiveEntry
{
	/**
	 * Creates Entry from File.
	 *
	 * @param File $file File.
	 * @param null|string $name Name.
	 * @return ArchiveEntry
	 */
	public static function createFromFileModel(File $file, $name = null)
	{
		return static::createFromFile($file->getFile(), $name?: $file->getName());
	}

	/**
	 * Creates Entry from attached object.
	 *
	 * @param AttachedObject $attachedObject Attached object.
	 * @param null|string $name Name.
	 * @return ArchiveEntry
	 */
	public static function createFromAttachedObject(AttachedObject $attachedObject, $name = null)
	{
		if($attachedObject->isSpecificVersion())
		{
			$version = $attachedObject->getVersion();

			return static::createFromFile($version->getFile(), $name?: $version->getName());
		}

		return static::createFromFileModel($attachedObject->getFile(), $name);
	}
}
