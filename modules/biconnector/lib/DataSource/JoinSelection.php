<?php

namespace Bitrix\BIConnector\DataSource;

class JoinSelection
{
	public function __construct(
		private readonly Dataset $parentDataset,
		private readonly string $alias,
		private readonly string $innerJoin = '',
		private readonly string $leftJoin = '',
	)
	{
	}

	public function getAlias(): string
	{
		return $this->alias;
	}

	public function getJoinFieldName(string $code): string
	{
		return $this->parentDataset->getSqlHelper()->quote("{$this->alias}.{$code}");
	}

	public function toArray(): array
	{
		return [
			'TABLE_ALIAS' => $this->alias,
			'JOIN' => $this->innerJoin,
			'LEFT_JOIN' => $this->leftJoin,
		];
	}
}
