<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet\User;
use Bitrix\Intranet\UStat\UStat;
use Bitrix\Main\Localization\Loc;

class Pulse extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		return UStat::checkAvailableCompanyPulse() && $user->isIntranet();
	}

	public function getConfiguration(): array
	{
		return [
			'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_PULSE_TITLE'),
		];
	}

	public function getName(): string
	{
		return 'pulse';
	}
}
