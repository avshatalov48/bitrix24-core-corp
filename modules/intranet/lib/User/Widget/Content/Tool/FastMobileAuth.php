<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet;
use Bitrix\Intranet\User;
use Bitrix\Main\Localization\Loc;

class FastMobileAuth extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		return (new Intranet\Internal\Service\Platform\UsageChecker())->isMobileUsedByUserId($user->getId());
	}

	public function getConfiguration(): array
	{
		return [
			'title' =>  $this->getTitle(),
		];
	}

	public function getName(): string
	{
		return 'fastMobileAuth';
	}

	protected function getTitle(): ?string
	{
		return Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_FAST_MOBILE_AUTH_TITLE');
	}
}
