<?php

namespace Bitrix\Tasks\Provider;

interface TaskQueryInterface extends QueryInterface
{
	public const SORT_ASC = 'ASC';
	public const SORT_DESC = 'DESC';

	public function getId(): string;

	public function needAccessCheck(): bool;
	public function getUserId(): int;
	public function getCountTotal(): int;
}