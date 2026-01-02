<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Access;

/**
 * @uses \Bitrix\Intranet\User\Access\Rule\DeleteRule
 * @uses \Bitrix\Intranet\User\Access\Rule\FireRule
 * @uses \Bitrix\Intranet\User\Access\Rule\RestoreRule
 * @uses \Bitrix\Intranet\User\Access\Rule\ConfirmRule
 * @uses \Bitrix\Intranet\User\Access\Rule\DeclineRule
 */
enum UserActionDictionary: string
{
	case DELETE = 'delete';
	case FIRE = 'fire';
	case RESTORE = 'restore';
	case CONFIRM = 'confirm';
	case DECLINE = 'decline';
	case VIEW = 'view';
	case UPDATE = 'update';

	public static function values(?array $actions = null): array
	{
		return array_map(fn ($v): string => $v->value, $actions ?? self::cases());
	}

	public static function valuesForBatchCheck(?array $actions = null): array
	{
		return array_fill_keys(self::values($actions), null);
	}

	public static function has(string $value): bool
	{
		return in_array($value, self::values(), true);
	}
}
