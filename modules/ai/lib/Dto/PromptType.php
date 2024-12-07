<?php

namespace Bitrix\AI\Dto;

enum PromptType: string
{
	case DEFAULT = 'default';
	case SIMPLE_TEMPLATE = 'simpleTemplate';

	public static function fromName(string $name): string
	{
		foreach (self::cases() as $status) {
			if ($name === $status->name)
			{
				return $status->value;
			}
		}
		throw new \ValueError("$name is not a valid backing value for enum " . self::class);
	}
}