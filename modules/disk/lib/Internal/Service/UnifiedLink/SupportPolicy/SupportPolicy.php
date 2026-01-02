<?php
declare(strict_types=1);

namespace Bitrix\Disk\Internal\Service\UnifiedLink\SupportPolicy;

use Bitrix\Disk\File;

interface SupportPolicy
{
	public function supports(File $file): bool;
}
