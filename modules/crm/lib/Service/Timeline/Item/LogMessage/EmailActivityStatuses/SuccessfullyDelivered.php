<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\EmailActivityStatuses;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Timeline\Item\Mixin\MailMessage;

class SuccessfullyDelivered extends LogMessage
{
	use MailMessage;
	public function getIconCode(): ?string
	{
		return Icon::COMPLETE;
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_LOG_EMAIL_ACTIVITY_SUCCESSFULLY_DELIVERED');
	}

	public function getType(): string
	{
		return 'EmailActivitySuccessfullyDelivered';
	}

	private function getActivityId(): int
	{
		return (int) $this->getModel()->getAssociatedEntityId();
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