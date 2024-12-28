<?php
declare(strict_types=1);

namespace Bitrix\Disk\Realtime\Tags;

abstract class Tag
{
	abstract public function getName(): string;
}