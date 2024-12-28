<?php

namespace Bitrix\Tasks\Flow\Option\FlowUserOption;

class FlowUserOption
{
	public function __construct(private int $flowId, private int $userId, private string $name, private string $value)
	{}

	public function getFlowId(): int
	{
		return $this->flowId;
	}

	public function getUserId(): int
	{
		return $this->userId;
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