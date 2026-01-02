<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet\User;
use Bitrix\Intranet;
use Bitrix\Main\Localization\Loc;

class FastMobileAuthFull extends FastMobileAuth
{
	public static function isAvailable(User $user): bool
	{
		return true;
	}

	public function getConfiguration(): array
	{
		return [
			'title' => $this->getTitle(),
			'warning' => $this->getWarning(),
		];
	}

	protected function getTitle(): ?string
	{
		if ((new Intranet\Internal\Service\Platform\UsageChecker())->isMobileUsedByUserId($this->user->getId()))
		{
			return parent::getTitle();
		}

		return Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_FAST_MOBILE_AUTH_TITLE_WITHOUT_APP');
	}

	private function getWarning(): string
	{
		return Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_FAST_MOBILE_AUTH_WARNING');
	}
}
