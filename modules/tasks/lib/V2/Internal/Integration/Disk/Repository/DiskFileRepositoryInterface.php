<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Repository;

use Bitrix\Tasks\V2\Internal\Integration\Disk\Entity\DiskFileCollection;

interface DiskFileRepositoryInterface
{
	public function getByIds(array $ids): DiskFileCollection;
}
