<?php

namespace Bitrix\Tasks\Grid;

use Bitrix\Tasks\Grid;

interface ScopeInterface
{
	public function __construct(Grid $grid);
	public function getHeaders(): array;
	public function getScope(): string;
	public function apply(): array;
}
