<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity;

class DiskFileMapper
{
	public function mapToCollection(array $files): Entity\DiskFileCollection
	{
		$entities = [];
		foreach ($files as $file)
		{
			$entities[] = $this->mapToEntity($file);
		}

		return new Entity\DiskFileCollection(...$entities);
	}

	public function mapToEntity(array $file): Entity\DiskFile
	{
		return Entity\DiskFile::mapFromArray($file);
	}
}