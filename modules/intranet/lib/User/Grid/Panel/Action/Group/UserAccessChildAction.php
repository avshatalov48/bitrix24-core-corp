<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action\Group;

use Bitrix\Intranet\User\Access\UserActionDictionary;

abstract class UserAccessChildAction extends UserGroupChildAction
{
	abstract public static function getActionType(): UserActionDictionary;

	final public static function getId(): string
	{
		return static::getActionType()->value;
	}
}
