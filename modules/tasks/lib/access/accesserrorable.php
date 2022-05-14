<?php

namespace Bitrix\Tasks\Access;

interface AccessErrorable
{
	public function getErrors(): array;
	public function addError(string $class, string $message): void;
}