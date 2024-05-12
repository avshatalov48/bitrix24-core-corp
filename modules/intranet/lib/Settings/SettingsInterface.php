<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Intranet\User;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Result;

interface SettingsInterface
{
	public function validate(): ErrorCollection;
	public function getType(): string;
	public function save(): Result;
	public function get(): self;
	public function toArray(): array;
	public function set(array $data): self;
	public function find(string $query): array;
}