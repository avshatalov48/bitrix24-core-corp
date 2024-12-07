<?php

namespace Bitrix\Tasks\Flow\Option;

class Option
{
	public function __construct(private int $flowId, private string $name, private string $value)
	{}

	public function getFlowId(): int
	{
		return $this->flowId;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValue(): string
	{
		return $this->value;
	}
}