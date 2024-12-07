<?php

namespace Bitrix\Tasks\Flow\Access;

/**
 *  @uses \Bitrix\Tasks\Flow\Access\Rule\FlowDeleteRule
 *  @uses \Bitrix\Tasks\Flow\Access\Rule\FlowReadRule
 *  @uses \Bitrix\Tasks\Flow\Access\Rule\FlowCreateRule
 *  @uses \Bitrix\Tasks\Flow\Access\Rule\FlowUpdateRule
 *  @uses \Bitrix\Tasks\Flow\Access\Rule\FlowTaskSaveRule
 */
enum FlowAction: string
{
	case CREATE = 'flow_create';
	case READ = 'flow_read';
	case UPDATE = 'flow_update';
	case DELETE = 'flow_delete';
	case SAVE = 'flow_save';

	public static function values(): array
	{
		return array_map(static fn (self $v): string => $v->value, self::cases());
	}

	public static function has(string $value): bool
	{
		return in_array($value, self::values(), true);
	}
}