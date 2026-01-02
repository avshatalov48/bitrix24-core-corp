<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\User\MiniProfile\Structure;

class TeamDto implements \JsonSerializable
{
	public function __construct(
		public int $id,
		public string $title,
	) {}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
		];
	}
}
