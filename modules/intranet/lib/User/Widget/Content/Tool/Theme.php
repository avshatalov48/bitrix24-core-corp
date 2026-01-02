<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet\User;
use Bitrix\Main\Localization\Loc;

class Theme extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		return true;
	}

	public function getConfiguration(): array
	{
		return [
			'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_THEME_TITLE'),
		];
	}

	public function getName(): string
	{
		return 'theme';
	}
}
