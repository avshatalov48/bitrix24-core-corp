<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics\Context;

interface SectionStrategyInterface
{
	public function getSection(): string;

	public function getSubSection(): string;
}