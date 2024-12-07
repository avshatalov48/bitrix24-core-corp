<?php

namespace Bitrix\Crm\Service\Display\Field\Sign\B2e;

final class UserListField extends BaseUserListField
{
	public const TYPE = 'sign_b2e_user_list';

	protected function prepareResultHtml(string $userListBlock, string $counterBlock, string $url): string
	{
		return "<div class=\"sign-b2e-user-list\"><a href=\"{$url}\">{$userListBlock}{$counterBlock}</a>{$this->prepareGroupChatHtml()}</div>";
	}
}