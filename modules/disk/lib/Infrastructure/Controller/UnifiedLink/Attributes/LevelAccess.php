<?php

declare(strict_types=1);

namespace Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Attributes;

use Attribute;
use Bitrix\Disk\Internal\Access\UnifiedLink\UnifiedLinkAccessLevel;

#[Attribute]
class LevelAccess
{

	public function __construct(
		public readonly UnifiedLinkAccessLevel $result
	)
	{
	}
}