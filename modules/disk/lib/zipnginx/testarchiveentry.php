<?php

namespace Bitrix\Disk\ZipNginx;

use Bitrix\Main\Engine\Response\Zip\FileEntry;

final class TestArchiveEntry extends FileEntry
{
	public const TEST_FILE = '/bitrix/images/disk/is.png';

	protected function __construct()
	{
		parent::__construct('is.png', self::TEST_FILE, 0);
	}

	public static function create(): self
	{
		return new self();
	}
}