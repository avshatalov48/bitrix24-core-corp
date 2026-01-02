<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Disk\Uf\Integration\DiskUploaderController;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\DiskFileMapper;

class DiskFileRepository implements DiskFileRepositoryInterface
{
	public function __construct(
		private readonly DiskFileMapper $diskFileMapper
	)
	{

	}

	public function getByIds(array $ids): Entity\DiskFileCollection
	{
		if (!Loader::includeModule('disk'))
		{
			return new Entity\DiskFileCollection();
		}

		$files = DiskUploaderController::getFileInfo($ids);

		return $this->diskFileMapper->mapToCollection($files);
	}
}