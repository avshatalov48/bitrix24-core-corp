<?php

declare(strict_types=1);

namespace Bitrix\Intranet\User\ActionRule;

use Bitrix\Intranet\User\Access\UserActionDictionary;

class ActionRuleFactory
{
	public static function getActionRule(UserActionDictionary $action): ActionRule
	{
		return match ($action)
		{
			UserActionDictionary::DELETE => new DeleteActionRule(),
			UserActionDictionary::FIRE => new FireActionRule(),
			UserActionDictionary::RESTORE => new RestoreActionRule(),
			UserActionDictionary::CONFIRM, UserActionDictionary::DECLINE => new ConfirmActionRule(),
			default => new DefaultRule(),
		};
	}
}
