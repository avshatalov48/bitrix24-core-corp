<?php

namespace Bitrix\Tasks\Provider\Query;

interface QueryInterface
{
	public function getSelect(): array;

	public function getDistinct(): bool;

	public function getWhere();

	public function getOrderBy(): array;

	public function getLimit(): int;

	public function getOffset(): int;

	public function getGroupBy(): array;
}
