<?php

namespace Bitrix\Tasks\Provider;

interface TaskQueryInterface
{
	public const SORT_ASC = 'ASC';
	public const SORT_DESC = 'DESC';

	public function getId(): string;
	public function getSelect(): array;
	public function getWhere(): array;
	public function getOrderBy(): array;
	public function getLimit(): int;
	public function getOffset(): int;
}