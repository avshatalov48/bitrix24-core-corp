<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Integration\Disk\Entity\DiskFile;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Entity\DiskFileCollection;

class DiskFileMapper
{
	public function mapToCollection(array $files): DiskFileCollection
	{
		$entities = [];
		foreach ($files as $file)
		{
			$entities[] = $this->mapToEntity($file);
		}

		return new DiskFileCollection(...$entities);
	}

	public function mapToEntity(array $file): DiskFile
	{
		$file['owner'] = ['id' => (int)($file['customData']['createdBy'] ?? null)];

		return DiskFile::mapFromArray($file);
	}
}
