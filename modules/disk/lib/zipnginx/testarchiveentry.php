<?php

namespace Bitrix\Disk\ZipNginx;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;

class TestArchiveEntry extends ArchiveEntry
{
	const TEST_FILE = '/bitrix/images/disk/is.png';

	protected function __construct()
	{
		parent::__construct();

		$this->path = static::TEST_FILE;
		$this->name = 'is.png';
		$this->size = 0;
	}

	/**
	 * Creates test entry.
	 *
	 * @return static
	 */
	public static function create()
	{
		return new static;
	}

	/**
	 * Creates test entry.
	 *
	 * @param File $file File.
	 * @param null $name
	 *
	 * @return static
	 */
	public static function createFromFileModel(File $file, $name = null)
	{
		return new static;
	}

	/**
	 * Creates test entry.
	 *
	 * @param AttachedObject $attachedObject Attached object.
	 * @param null $name
	 *
	 * @return static
	 */
	public static function createFromAttachedObject(AttachedObject $attachedObject, $name = null)
	{
		return new static;
	}

	/**
	 * Creates test entry.
	 *
	 * @param array $fileArray Array of file from b_file.
	 * @param string $name Name of file.
	 * @return static
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function createFromFileArray(array $fileArray, $name)
	{
		return new static;
	}
}