<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Builder\Structure\Filter;

use Bitrix\HumanResources\Contract\Builder\Filter;
use Bitrix\Main\ORM\Query\Query;

abstract class BaseFilter implements Filter
{
	protected string $currentAlias = '';

	public function getCurrentAlias(): string
	{
		return $this->currentAlias;
	}

	public function setCurrentAlias(string $currentAlias): static
	{
		$this->currentAlias = $currentAlias;

		return $this;
	}

	protected function getFieldByQueryContext(string $fieldName): string
	{
		if (empty($this->getCurrentAlias()))
		{
			return $fieldName;
		}

		return $this->getCurrentAlias() . '.' . $fieldName;
	}
}