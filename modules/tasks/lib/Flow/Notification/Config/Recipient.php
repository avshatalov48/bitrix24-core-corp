<?php

namespace Bitrix\Tasks\Flow\Notification\Config;

use Bitrix\Tasks\Access\Role\RoleDictionary;
use Bitrix\Tasks\Flow\Notification\Exception\InvalidPayload;
use Bitrix\Tasks\ValueObjectInterface;

class Recipient implements ValueObjectInterface
{
	public const FLOW_OWNER = 'flowOwner';
	public const TASK_FLOW_OWNER = 'taskFlowOwner';

	private string $type;
	private int|null $value;

	public function __construct(string $type, int $value = null)
	{
		if (!$this->isTypeAllowed($type))
		{
			throw new InvalidPayload('Recipient:type must be one of the: ' . json_encode($this->getAllowedTypes()));
		}

		$this->type = $type;
		$this->value = $value;
	}

	public function getValue(): array
	{
		return ['type' => $this->type];
	}

	public function getType(): string
	{
		return $this->type;
	}

	private function isTypeAllowed(string $when): bool
	{
		return in_array($when, $this->getAllowedTypes(), true);
	}

	private function getAllowedTypes(): array
	{
		return [
			RoleDictionary::ROLE_RESPONSIBLE,
			self::FLOW_OWNER,
			self::TASK_FLOW_OWNER,
		];
	}
}