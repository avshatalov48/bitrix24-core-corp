<?php declare (strict_types = 1);

namespace Bitrix\AI\Chatbot\Message\Params;

abstract class Param
{
	protected string $name;
	protected mixed $value;

	public function __construct(string $name, mixed $value)
	{
		$this->name = $name;
		$this->value = $value;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}
}