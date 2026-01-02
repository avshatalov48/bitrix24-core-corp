<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\Widget\Content\Tool;

use Bitrix\Intranet\User;
use Bitrix\Main\Localization\Loc;
use Bitrix\Intranet\Internal\Integration;

class SalaryVacation extends BaseTool
{
	public static function isAvailable(User $user): bool
	{
		return (new Integration\Humanresources\HcmLinkSalaryAndVacationFacade())->isAvailableByUserId($user->getId());
	}

	public function getConfiguration(): array
	{
		return [
			'title' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_SALARY_VACATION_TITLE'),
			'disabledHint' => Loc::getMessage('INTRANET_USER_WIDGET_CONTENT_TOOL_SALARY_VACATION_DISABLED_MESSAGE', [
				'[LINK]' => "<a target='_self' onclick='(() => {BX.Helper.show(`redirect=detail&code=23343028`);})()' style='cursor:pointer;'>",
				'[/LINK]' => '</a>',
			]),
		];
	}

	public function getName(): string
	{
		return 'salaryVacation';
	}
}
