<?php

namespace Bitrix\Crm\Service\Display\Field\Sign\B2e;

use Bitrix\Main\Localization\Loc;

final class UserNameListField extends BaseUserListField
{
	public const TYPE = 'sign_b2e_user_name_list';
	protected int $visibleCount = 2;
	protected string $elementSeparator = ', ';

	protected function prepareElementHtml(string $title, string $photoUrl): string
	{
		return sprintf(
			'<span title="%s">%s</span>',
			$title,
			$title
		);
	}

	protected function prepareResultHtml(string $userListBlock, string $counterBlock, string $url): string
	{
		return sprintf(
			'<a class="crm-kanban-item-fields-item-value-name" href="%s">%s%s</a>%s',
			$url,
			$userListBlock,
			$counterBlock,
			$this->prepareGroupChatHtml(),
		);
	}

	protected function prepareCounterHtml(int $count): string
	{
		$text = Loc::getMessage('CRM_FIELD_SIGN_B2E_USER_NAME_LIST_MORE', ['#COUNT#' => $count]) ?? '';

		return sprintf(' %s', $text);
	}
}
