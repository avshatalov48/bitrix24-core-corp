<?php

namespace Bitrix\Crm\Service\Converter;

interface CaseCache
{
	public function add(string $camelCase, string $upperCase): self;

	public function clear(): self;

	public function getUpperCase(string $camelCase): ?string;

	public function getCamelCase(string $upperCase): ?string;
}