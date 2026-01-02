<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet;
use Bitrix\Intranet\User;

abstract class BaseTool implements \JsonSerializable
{
	public function __construct(protected readonly Intranet\User $user)
	{
	}

	abstract public static function isAvailable(User $user): bool;

	abstract public function getConfiguration(): array;

	abstract public function getName(): string;

	public function jsonSerialize(): array
	{
		return $this->getConfiguration();
	}
}
