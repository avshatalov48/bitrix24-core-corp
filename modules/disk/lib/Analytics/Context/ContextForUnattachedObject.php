<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics\Context;

use Bitrix\Disk\Analytics\DiskObject;
use Bitrix\Disk\BaseObject;

class ContextForUnattachedObject implements SectionStrategyInterface
{
	private readonly DiskObject $diskObject;

	public function __construct(BaseObject $baseDiskObject)
	{
		$this->diskObject = new DiskObject($baseDiskObject);
	}

	public function getSection(): string
	{
		return match (true) {
			$this->diskObject->isInProject() => 'project',
			$this->diskObject->isInCollab() => 'collab',
			default => 'files'
		};
	}

	public function getSubSection(): string
	{
		return match (true) {
			$this->diskObject->isPersonal() => 'my_files',
			$this->diskObject->isInProject() => 'project_files',
			$this->diskObject->isInCollab() => 'collab_files',
			default => 'bitrix24_files'
		};
	}
}