<?php

declare(strict_types=1);

namespace Bitrix\Disk\Analytics\Context;

interface ElementStrategyInterface
{
	public function getElement(): string;
}