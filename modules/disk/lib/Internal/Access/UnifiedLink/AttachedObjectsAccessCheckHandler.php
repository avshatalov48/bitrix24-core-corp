<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Access\UnifiedLink;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;

class AttachedObjectsAccessCheckHandler extends ChainableAccessCheckHandler
{
	public function __construct(
		private readonly int $userId,
		private readonly ?AttachedObject $attachedObject,
	)
	{
	}

	protected function doCheck(File $file): UnifiedLinkAccessLevel
	{
		if ($this->attachedObject !== null)
		{
			if ($this->attachedObject->canUpdate($this->userId))
			{
				return UnifiedLinkAccessLevel::Edit;
			}

			if ($this->attachedObject->canRead($this->userId))
			{
				return UnifiedLinkAccessLevel::Read;
			}
		}

		return UnifiedLinkAccessLevel::Denied;
	}
}