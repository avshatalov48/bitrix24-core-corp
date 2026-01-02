<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Access\UnifiedLink;

use Bitrix\Disk\File;

class ExternalLinkAccessCheckHandler extends ChainableAccessCheckHandler
{

	protected function doCheck(File $file): UnifiedLinkAccessLevel
	{
		return UnifiedLinkAccessLevel::Denied;
	}
}