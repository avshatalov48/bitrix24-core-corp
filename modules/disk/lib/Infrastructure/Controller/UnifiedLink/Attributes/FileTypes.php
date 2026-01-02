<?php

declare(strict_types=1);

namespace Bitrix\Disk\Infrastructure\Controller\UnifiedLink\Attributes;

use Attribute;

#[Attribute]
class FileTypes
{
	/**
	 * @var int[]
	 */
	public array $fileTypes;

	public function __construct(int ...$fileTypes)
	{
		$this->fileTypes = $fileTypes;
	}
}