<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics\Context;

use Bitrix\Disk\File;

class ContextForCreateFile extends ContextForUnattachedObject implements ElementStrategyInterface
{
	public function __construct(
		File $file,
		private readonly string $service,
	)
	{
		parent::__construct($file);
	}

	public function getElement(): string
	{
		return $this->service;
	}
}