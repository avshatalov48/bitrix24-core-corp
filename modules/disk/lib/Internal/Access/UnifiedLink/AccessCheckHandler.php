<?php

declare(strict_types=1);

namespace Bitrix\Disk\Internal\Access\UnifiedLink;

use Bitrix\Disk\File;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;

interface AccessCheckHandler
{
	public function check(File $file): UnifiedLinkAccessLevel;

	public function setNext(AccessCheckHandler $handler): AccessCheckHandler;
}