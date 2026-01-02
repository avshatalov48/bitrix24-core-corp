<?php declare(strict_types=1);

namespace Bitrix\AI\Payload\JsonSchema\Enum;

enum SchemaType: string
{
	case String = 'string';
	case Number = 'number';
	case Boolean = 'boolean';
	case Integer = 'integer';
	case Object = 'object';

	case Array = 'array';
	case AnyOf = 'anyOf';
}