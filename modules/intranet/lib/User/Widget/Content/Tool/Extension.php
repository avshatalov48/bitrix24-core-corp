<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet;
use Bitrix\Intranet\User;
use Bitrix\Main\Localization\Loc;

class Extension extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		return $user->isIntranet();
	}

	public function getConfiguration(): array
	{
		return [
			'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_EXTENSION_TITLE'),
			...Intranet\Binding\Menu::getMenuItems(
				'top_panel',
				'user_menu',
				[
					'inline' => true,
					'context' => ['USER_ID' => $this->user->getId()],
				],
			),
		];
	}

	public function getName(): string
	{
		return 'extension';
	}
}
