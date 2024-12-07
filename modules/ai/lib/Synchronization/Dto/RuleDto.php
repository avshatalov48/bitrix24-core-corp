<?php declare(strict_types=1);

namespace Bitrix\AI\Synchronization\Dto;

use Bitrix\AI\Enum\RuleName;

class RuleDto
{
	public function __construct(
		protected readonly bool $isCheckInvert,
		protected readonly RuleName $ruleName,
		protected readonly string $value,
	)
	{
	}

	public function getIsCheckInvertInt(): int
	{
		return (int)$this->isCheckInvert;
	}

	public function isCheckInvert(): bool
	{
		return $this->isCheckInvert;
	}

	public function getRuleNameString(): string
	{
		return $this->ruleName->value;
	}

	public function getRuleName(): RuleName
	{
		return $this->ruleName;
	}

	public function getValue(): string
	{
		return $this->value;
	}
}
