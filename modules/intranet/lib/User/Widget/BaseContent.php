<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget;

use Bitrix\Intranet;

abstract class BaseContent implements \JsonSerializable
{
	public function __construct(protected Intranet\User $user)
	{
	}

	abstract public function getName(): string;

	abstract public function getConfiguration(): array;

	public static function isAvailable(): bool
	{
		return true;
	}

	public function jsonSerialize(): array
	{
		return $this->getConfiguration();
	}
}
