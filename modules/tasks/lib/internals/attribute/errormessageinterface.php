<?php

namespace Bitrix\Tasks\Internals\Attribute;

interface ErrorMessageInterface
{
	public function getError(string $field): string;
}