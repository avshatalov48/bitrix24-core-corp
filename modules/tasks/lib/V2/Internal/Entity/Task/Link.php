<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Task;

use Bitrix\Tasks\ValueObjectInterface;

class Link implements ValueObjectInterface
{
	public function __construct(
		private readonly string $link
	)
	{

	}

	public static function mapFromValue(string $link): static
	{
		return new static(link: $link);
	}

	public function getValue(): string
	{
		return $this->link;
	}
}