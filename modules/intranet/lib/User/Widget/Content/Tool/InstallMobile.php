<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet\User;
use Bitrix\Intranet;
use Bitrix\Main\Localization\Loc;

class InstallMobile extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		return !(new Intranet\Internal\Service\Platform\UsageChecker())->isMobileUsedByUserId($user->getId());
	}

	public function getConfiguration(): array
	{
		return [
			'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_INSTALL_MOBILE_TITLE_MSGVER_1'),
		];
	}

	public function getName(): string
	{
		return 'installMobile';
	}
}
