<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\EmailActivityStatuses;

use Bitrix\Crm\Service\Timeline\Item\Mixin\MailMessage;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;

class NonDelivered extends LogMessage
{
	use MailMessage;

	public function getType(): string
	{
		return 'EmailActivityNonDelivered';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_EMAIL_ACTIVITY_NON_DELIVERED_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Icon::ATTENTION;
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

	public function getTags(): ?array
	{
		return [
			'errorStatus' => new Tag(Loc::getMessage('CRM_TIMELINE_LOG_EMAIL_ACTIVITY_NON_DELIVERED_TAG_TEXT'), Tag::TYPE_FAILURE)
		];
	}
}