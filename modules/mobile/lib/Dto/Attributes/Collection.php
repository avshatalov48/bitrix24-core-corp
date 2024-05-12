<?php

namespace Bitrix\Mobile\Dto\Attributes;

use Attribute;
use Bitrix\Mobile\Dto\InvalidDtoException;

#[Attribute]
class Collection
{
	public function __construct(
		public string $className,
	)
	{}

	public function getElementsType(): string
	{
		if ($this->className === '')
		{
			throw new InvalidDtoException('Type of collection is not declared in attribute');
		}

		return $this->className;
	}
}