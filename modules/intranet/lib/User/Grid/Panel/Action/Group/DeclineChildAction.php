<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action\Group;

use Bitrix\Intranet\User\Access\UserActionDictionary;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class DeclineChildAction extends UserAccessChildAction
{
	public static function getActionType(): UserActionDictionary
	{
		return UserActionDictionary::DECLINE;
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_USER_LIST_GROUP_ACTION_DECLINE_TITLE') ?? '';
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return null;
	}
}