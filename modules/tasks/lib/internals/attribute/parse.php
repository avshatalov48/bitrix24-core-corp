<?php

namespace Bitrix\Tasks\Internals\Attribute;

use Attribute;

#[Attribute]
class Parse
{
	public function __construct(
		private readonly ParserInterface $parser,
		public readonly string $sourceProperty
	)
	{
	}

	public function parse(mixed $value): mixed
	{
		return $this->parser->parse($value);
	}
}