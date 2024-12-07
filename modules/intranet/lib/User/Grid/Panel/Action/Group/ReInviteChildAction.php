<?php

namespace Bitrix\Intranet\User\Grid\Panel\Action\Group;

use Bitrix\Main\Error;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Grid\Panel\Snippet\Onchange;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ReInviteChildAction extends UserGroupChildAction
{
	public static function getId(): string
	{
		return 'reInvite';
	}

	public function getName(): string
	{
		return Loc::getMessage('INTRANET_USER_LIST_GROUP_ACTION_REINVITE_TITLE') ?? '';
	}

	public function processRequest(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		return null;
	}
}