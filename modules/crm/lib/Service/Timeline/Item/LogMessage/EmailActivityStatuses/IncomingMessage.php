<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\EmailActivityStatuses;

use Bitrix\Crm\Service\Timeline\Item\Mixin\MailMessage;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action;

final class IncomingMessage extends LogMessage
{
	use MailMessage;

	public function getType(): string
	{
		return 'EmailLogIncomingMessage';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_EMAIL_ACTIVITY_INCOMING_MESSAGE_TITLE');
	}

	public function getIconCode(): string
	{
		return 'email';
	}

	private function getActivityId(): int
	{
		return (int)$this->getModel()->getAssociatedEntityId();
	}

	public function getTitleAction(): ?Action
	{
		return $this->getOpenMessageAction($this->getActivityId());
	}

	public function getContentBlocks(): ?array
	{
		return [
			'activityInfo' => $this->getSubjectContentBlock($this->getActivityId()),
		];
	}
}