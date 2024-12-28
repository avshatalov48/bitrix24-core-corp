<?php
declare(strict_types=1);

namespace Bitrix\AI\Engine\Enum;

/**
 * Class Category
 * Defines the category of the ENGINE.
 */
enum Category: string
{
	case TEXT = 'text';
	case IMAGE = 'image';
	case AUDIO = 'audio';
	case CALL = 'call';
}