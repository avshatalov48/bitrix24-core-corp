<?php

namespace Bitrix\Stafftrack\Integration\Pull;

enum PushCommand: string
{
	case SHIFT_ADD = 'shift_add';
	case SHIFT_UPDATE = 'shift_update';
	case SHIFT_DELETE = 'shift_delete';
}