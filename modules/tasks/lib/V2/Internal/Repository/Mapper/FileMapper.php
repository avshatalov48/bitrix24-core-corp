<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Entity;

class FileMapper
{
	public function mapToCollection(array $files): Entity\FileCollection
	{
		$entities = [];
		foreach ($files as $file)
		{
			$entities[] = $this->mapToEntity($file);
		}

		return new Entity\FileCollection(...$entities);
	}

	public function mapToEntity(array $file): Entity\File
	{
		return new Entity\File(
			id: isset($file['ID']) ? (int)$file['ID'] : null,
			src: $file['SRC'] ?? null,
			name: $file['FILE_NAME'] ?? null,
			width: $file['WIDTH'] ? (int)$file['WIDTH'] : null,
			height: $file['HEIGHT'] ? (int)$file['HEIGHT'] : null,
			subDir: $file['SUBDIR'] ?? null,
			contentType: $file['CONTENT_TYPE'] ?? null,
		);
	}
}