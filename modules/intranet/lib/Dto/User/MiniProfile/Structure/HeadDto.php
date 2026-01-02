<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Dto\User\MiniProfile\Structure;

class HeadDto implements \JsonSerializable
{
	public function __construct(
		public int $id,
		public string $name,
		public string $workPosition,
		public string $avatar,
		public string $url,
	) {}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'workPosition' => $this->workPosition,
			'avatar' => $this->avatar,
			'url' => $this->url,
		];
	}
}
