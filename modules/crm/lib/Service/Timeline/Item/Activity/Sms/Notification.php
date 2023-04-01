<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Sms;

use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Notifications\Integration\Pull;
use Bitrix\Notifications\MessageStatus;

class Notification extends Base
{
	private ?array $messageInfo;

	public function __construct(Context $context, Model $model)
	{
		$this->messageInfo = $model->getAssociatedEntityModel()->get('MESSAGE_INFO');
		parent::__construct($context, $model);
	}

	protected function getActivityTypeId(): string
	{
		return 'Notification';
	}

	public function getTitle(): ?string
	{
		$notificationHistoryItem = $this->getNotificationHistoryItem();

		if (!isset($notificationHistoryItem['PROVIDER_DATA']['DESCRIPTION']))
		{
			return Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_DEFAULT_TITLE');
		}

		return Loc::getMessage(
			'CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_TITLE',
			[
				'#MESSENGER#' => $notificationHistoryItem['PROVIDER_DATA']['DESCRIPTION'],
			]
		);
	}

	public function getTags(): ?array
	{
		$notificationHistoryItem = $this->getNotificationHistoryItem();

		$statusSemantic = $notificationHistoryItem['STATUS_DATA']['SEMANTICS'] ?? null;
		$statusDescription = $notificationHistoryItem['STATUS_DATA']['DESCRIPTION'] ?? null;
		$errorMessage = $notificationHistoryItem['ERROR_MESSAGE'] ?? null;

		if (is_null($statusSemantic) || is_null($statusDescription))
		{
			return null;
		}

		$timelineStatusSemanticsMap = [
			MessageStatus::SEMANTIC_SUCCESS => Layout\Header\Tag::TYPE_SUCCESS,
			MessageStatus::SEMANTIC_PROCESS => Layout\Header\Tag::TYPE_WARNING,
			MessageStatus::SEMANTIC_FAILURE => Layout\Header\Tag::TYPE_FAILURE
		];
		$timelineStatusSemantic = $timelineStatusSemanticsMap[$statusSemantic] ?? null;
		if (is_null($timelineStatusSemantic))
		{
			return null;
		}

		$statusTag = new Layout\Header\Tag($statusDescription, $timelineStatusSemantic);
		if (!is_null($errorMessage))
		{
			$statusTag->setHint($errorMessage);
		}

		return [
			'status' => $statusTag
		];
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return (new Layout\Body\Logo('notification'))->setInCircle();
	}

	protected function getMessageId(): ?int
	{
		return !empty($this->messageInfo['MESSAGE']['ID']) ? (int)$this->messageInfo['MESSAGE']['ID'] : null;
	}

	protected function getMessageText(): ?string
	{
		if (!empty($this->messageInfo['MESSAGE']['TEXT']))
		{
			return $this->messageInfo['MESSAGE']['TEXT'];
		}

		return Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_DEFAULT_TEXT');
	}

	protected function getMessageSentViaContentBlock(): ?Layout\Body\ContentBlock
	{
		return
			(new Layout\Body\ContentBlock\Text())
				->setValue(
					Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_SENT_VIA_BITRIX24')
				)
				->setColor(Layout\Body\ContentBlock\Text::COLOR_BASE_60)
		;
	}

	protected function getPullModuleId(): string
	{
		return 'notifications';
	}

	protected function getPullCommand(): string
	{
		if (!Loader::includeModule('notifications'))
		{
			return '';
		}

		return Pull::COMMAND_MESSAGE_UPDATE;
	}

	protected function getPullTagName(): string
	{
		if (!Loader::includeModule('notifications'))
		{
			return '';
		}

		return NotificationsManager::getPullTagName();
	}

	private function getNotificationHistoryItem(): array
	{
		return $this->messageInfo['HISTORY_ITEMS'][0] ?? [];
	}
}
