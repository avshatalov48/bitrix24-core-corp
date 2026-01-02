<?php

namespace Bitrix\Im\V2\Chat\Fields;

use Bitrix\Im\V2\Chat;
use Bitrix\Main\Engine\Response\Converter;

abstract class BaseField
{
	public function __construct(protected int $chatId)
	{}

	abstract public function get(): mixed;

	abstract public function set(mixed $value): self;

	abstract public function toRestFormat(array $option = []): array;

	abstract protected function getFieldName(): string;
}
