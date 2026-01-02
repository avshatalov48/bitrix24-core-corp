<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action\Group;

use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ConfirmChildAction extends UserAccessChildAction
{
	public static function getActionType(): UserActionDictionary
	{
		return UserActionDictionary::CONFIRM;
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_USER_LIST_GROUP_ACTION_CONFIRM_TITLE') ?? '';
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return null;
	}
}