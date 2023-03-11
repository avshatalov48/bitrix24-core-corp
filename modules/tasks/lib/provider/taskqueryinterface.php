<?php

namespace Bitrix\Tasks\Provider;

interface TaskQueryInterface
{
	public function getId(): string;
	public function getSelect(): array;
	public function getWhere(): array;
	public function getOrder(): array;
	public function getLimit(): int;
	public function getOffset(): int;
}