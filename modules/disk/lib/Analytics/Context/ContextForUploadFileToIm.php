<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics\Context;

use Bitrix\Disk\Analytics\Enum\ImSection;

class ContextForUploadFileToIm implements SectionStrategyInterface
{

	public function __construct(
		private readonly ImSection $section,
	)
	{
	}

	public function getSection(): string
	{
		return $this->section->value;
	}

	public function getSubSection(): string
	{
		return '';
	}

}