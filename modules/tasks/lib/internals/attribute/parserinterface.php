<?php

namespace Bitrix\Tasks\Internals\Attribute;

interface ParserInterface
{
	public function parse(mixed $value);
}