<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Access\UnifiedLink;

use Bitrix\Disk\File;

class PermissionSystemAccessCheckHandler extends ChainableAccessCheckHandler
{
	public function __construct(
		private readonly int $userId,
	)
	{
	}

	protected function doCheck(File $file): UnifiedLinkAccessLevel
	{
		$securityContext = $file->getStorage()?->getSecurityContext($this->userId);

		if (!$securityContext)
		{
			return UnifiedLinkAccessLevel::Denied;
		}

		if ($file->canUpdate($securityContext))
		{
			return UnifiedLinkAccessLevel::Edit;
		}

		if ($file->canRead($securityContext))
		{
			return UnifiedLinkAccessLevel::Read;
		}


		return UnifiedLinkAccessLevel::Denied;
	}

}