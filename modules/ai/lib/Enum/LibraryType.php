<?php declare(strict_types=1);

namespace Bitrix\AI\Enum;

enum LibraryType: string
{
	case PromptLibrary = 'bitrix:ai.prompt.library.grid';
	case RoleLibrary = 'bitrix:ai.role.library.grid';
}
