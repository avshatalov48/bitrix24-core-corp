<?php

namespace Bitrix\Crm\Service\WebForm\DefaultValue;

interface Rule
{
	public function getTargetFieldType(): string;
	public function getValueKey(): string;
	public function isApplicable(array $field): bool;
	public function getValue(array $field): string;
}