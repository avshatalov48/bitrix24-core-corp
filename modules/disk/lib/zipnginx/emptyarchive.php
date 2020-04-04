<?php

namespace Bitrix\Disk\ZipNginx;

use Bitrix\Main\Engine\Response\File;

final class EmptyArchive extends File
{
	const EMPTY_ARCHIVE_PATH = '/bitrix/images/disk/empty.zip';

	/** @var string */
	protected $name;

	public function __construct($name)
	{
		parent::__construct(self::EMPTY_ARCHIVE_PATH, $name, 'application/zip');
	}
}
