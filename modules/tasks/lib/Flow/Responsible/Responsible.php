<?php

namespace Bitrix\Tasks\Flow\Responsible;

use Bitrix\Main\Type\Contract\Arrayable;

final class Responsible implements Arrayable
{
	public function __construct(
		private readonly int $id,
		private readonly int $flowId,
	)
	{}

	public function getId(): int
	{
		return $this->id;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'flowId' => $this->flowId,
		];
	}
}
