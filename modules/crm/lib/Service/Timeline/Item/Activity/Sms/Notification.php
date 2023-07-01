<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Sms;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Main\Localization\Loc;

class Notification extends Base
{
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

	public function getLogo(): ?Logo
	{
		return Common\Logo::getInstance(Common\Logo::NOTIFICATION)->createLogo();
	}

	protected function getMessageText(): ?string
	{
		$messageInfo = $this->getAssociatedEntityModel()->get('MESSAGE_INFO') ?? [];
		if (!empty($messageInfo['MESSAGE']['TEXT']))
		{
			return $messageInfo['MESSAGE']['TEXT'];
		}

		return Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_DEFAULT_TEXT');
	}

	protected function getMessageSentViaContentBlock(): ?ContentBlock
	{
		return (new Text())
			->setValue(Loc::getMessage('CRM_TIMELINE_TITLE_ACTIVITY_NOTIFICATION_SENT_VIA_BITRIX24'))
			->setColor(Text::COLOR_BASE_60)
		;
	}

	private function getNotificationHistoryItem(): array
	{
		$messageInfo = $this->getAssociatedEntityModel()->get('MESSAGE_INFO') ?? [];

		return $messageInfo['HISTORY_ITEMS'][0] ?? [];
	}
}
