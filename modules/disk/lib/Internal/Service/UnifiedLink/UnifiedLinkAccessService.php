<?php
declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\File;
use Bitrix\Disk\Internal\Access\UnifiedLink\AccessCheckHandlerFactory;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;

class UnifiedLinkAccessService
{
	public function __construct(
		private readonly AccessCheckHandlerFactory $accessCheckHandlerFactory,
	)
	{
	}

	public function check(File $file, ?AttachedObject $attachedObject = null, int $userId = 0): UnifiedLinkAccessLevel
	{
		return $this->accessCheckHandlerFactory->create($attachedObject, $userId)->check($file);
	}
}