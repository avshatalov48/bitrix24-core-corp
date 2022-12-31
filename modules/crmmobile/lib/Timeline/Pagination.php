<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Timeline;

use Bitrix\Main\Type\DateTime;

final class Pagination implements \JsonSerializable
{
	public int $offsetId;
	public ?DateTime $offsetTime;

	public function __construct(int $offsetId = 0, ?DateTime $offsetTime = null)
	{
		$this->offsetId = $offsetId;
		$this->offsetTime = $offsetTime;
	}

	public function jsonSerialize(): array
	{
		return [
			'offsetId' => $this->offsetId,
			'offsetTime' => $this->offsetTime,
		];
	}
}