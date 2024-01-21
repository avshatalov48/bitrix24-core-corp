<?php

namespace Bitrix\Crm\FieldContext;

use Bitrix\Crm\FieldContext\Context\Ai;
use Bitrix\Crm\FieldContext\Context\Base;
use Bitrix\Crm\Service\Context;

class ContextManager
{
	final public function getData(): array
	{
		$contextIds = $this->getAvailableContextIds();

		$result = [];
		foreach ($contextIds as $contextId)
		{
			$result[] = $this->getContextById($contextId)?->toArray();
		}

		return $result;
	}

	public function getContextById(int $id): ?Base
	{
		return match ($id)
		{
			Ai::getId() => new Ai(),
			default => null,
		};
	}

	public function getAvailableContextIds(): array
	{
		return [
			Ai::getId(),
		];
	}

	public function getContextMap(): array
	{
		return [
			Context::SCOPE_AI => Ai::getId(),
		];
	}
}
