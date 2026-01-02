<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Disk\Repository;

use Bitrix\Disk\Uf\Integration\DiskUploaderController;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Entity\DiskFileCollection;
use Bitrix\Tasks\V2\Internal\Integration\Disk\Repository\Mapper\DiskFileMapper;

class DiskFileRepository implements DiskFileRepositoryInterface
{
	public function __construct(
		private readonly DiskFileMapper $diskFileMapper
	)
	{

	}

	public function getByIds(array $ids): DiskFileCollection
	{
		if (!Loader::includeModule('disk'))
		{
			return new DiskFileCollection();
		}

		$files = DiskUploaderController::getFileInfo($ids);

		return $this->diskFileMapper->mapToCollection($files);
	}
}
