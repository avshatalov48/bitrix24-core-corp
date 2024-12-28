<?php

namespace Bitrix\TasksMobile\UserField;

enum Type: string
{
	case String = 'string';
	case Double = 'double';
	case DateTime = 'datetime';
	case Boolean = 'boolean';
}
