<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet\User;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

class Administration extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		return $user->isAdmin() && !ModuleManager::isModuleInstalled('bitrix24');
	}

	public function getConfiguration(): array
	{
		return [
			'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_ADMINISTRATION_TITLE'),
			'path' => SITE_DIR . 'bitrix/admin/',
		];
	}

	public function getName(): string
	{
		return 'admin';
	}
}
