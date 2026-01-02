<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Disk\Uf\Integration\DiskUploaderController;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\FileMapper;
use CFile;

class FileRepository implements FileRepositoryInterface
{
	public function __construct(
		private readonly FileMapper $fileMapper
	)
	{

	}

	public function getById(int $id): ?Entity\File
	{
		$file = CFile::GetFileArray($id);

		if (!is_array($file))
		{
			return null;
		}

		return $this->fileMapper->mapToEntity($file);
	}

	public function getByIds(array $ids): Entity\FileCollection
	{
		Collection::normalizeArrayValuesByInt($ids, false);
		if (empty($ids))
		{
			return new Entity\FileCollection();
		}

		$files = [];
		$rows = CFile::GetList(arFilter: ['@ID' => $ids]);
		while ($row = $rows->Fetch())
		{
			$row['SRC'] = CFile::GetFileSRC($row);
			$files[] = $row;
		}

		return $this->fileMapper->mapToCollection($files);
	}
}
