<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity;

class HistoryLogCollection extends AbstractEntityCollection
{
	protected static function getEntityClass(): string
	{
		return HistoryLog::class;
	}
}