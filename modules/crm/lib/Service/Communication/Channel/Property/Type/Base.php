<?php

namespace Bitrix\Crm\Service\Communication\Channel\Property\Type;

use Bitrix\Crm\Service\Communication\Search\EntityFinder;

abstract class Base
{
	public function __construct(protected readonly mixed $value)
	{

	}

	public function getValue(): array
	{
		return $this->value;
	}

	public function getPreparedValue(): ?array
	{
		return null;
	}

	public function canUsePreparedValue(): bool
	{
		return false;
	}

	public function appendSearchCriterion(EntityFinder $entityFinder): void
	{
		// can be implemented in child class
	}
}
