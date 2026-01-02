<?php
declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink;

use Bitrix\Disk\File;
use Bitrix\Disk\Version;

class FileResolver
{
	public static function resolve(File $file, ?Version $version = null): File
	{
		$objectFromVersion = $version?->getObject();

		return $objectFromVersion ?? $file;
	}
}
