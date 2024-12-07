<?php

namespace Bitrix\Tasks\Flow\User;

use Bitrix\Main\Type\Contract\Arrayable;

final class User implements Arrayable
{
	public function __construct(
		public readonly int    $id,
		public readonly string $name,
		public readonly array  $photo,
		public readonly string $pathToProfile,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'photo' => $this->photo,
			'pathToProfile' => $this->pathToProfile,
		];
	}
}
