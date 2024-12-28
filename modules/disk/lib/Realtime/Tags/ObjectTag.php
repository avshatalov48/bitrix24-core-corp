<?php
declare(strict_types=1);

namespace Bitrix\Disk\Realtime\Tags;

final class ObjectTag extends Tag
{
	public function __construct(private readonly int $objectId)
	{
	}

	public function getObjectId(): int
	{
		return $this->objectId;
	}

	public function getName(): string
	{
		return "object_{$this->getObjectId()}";
	}
}