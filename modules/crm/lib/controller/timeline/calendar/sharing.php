<?php

namespace Bitrix\Crm\Controller\Timeline\Calendar;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;
use Bitrix\Crm\Integration\Calendar\EventData;
use Bitrix\Crm\Integration\Calendar\Helper;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;

class Sharing extends Controller
{
	public function sendLinkAction(
		int $contactId,
		int $contactTypeId,
		int $ownerId,
		int $ownerTypeId,
		string $channelId,
		string $senderId
	): bool
	{
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return false;
		}

		$sendingResult = Helper::getInstance()->sendLinkToClient($ownerId, $contactId, $contactTypeId, $channelId, $senderId);
		if ($sendingResult->getErrors())
		{
			$this->addErrors($sendingResult->getErrors());

			return false;
		}

		$linkHash = $sendingResult->getData()['linkHash'];
		$timelineResult = Helper::getInstance()->addTimelineEntry($linkHash, EventData::SHARING_ON_INVITATION_SENT);
		if (!$timelineResult)
		{
			$this->addError(new Error('Timeline entry not created'));

			return false;
		}

		return true;
	}

	public function onLinkCopiedAction(string $linkHash, int $ownerId, int $ownerTypeId): bool
	{
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return false;
		}

		$timelineResult = Helper::getInstance()->addTimelineEntry($linkHash, EventData::SHARING_ON_LINK_COPIED);
		if (!$timelineResult)
		{
			$this->addError(new Error('Timeline entry not created'));
		}

		return true;
	}

	public function getConferenceChatIdAction(int $eventId, int $ownerId, int $ownerTypeId)
	{
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return false;
		}

		$result = Helper::getInstance()->getConferenceChatId($eventId);
		if (!$result)
		{
			$this->addError(new Error('Error while trying to get chat id'));
		}

		return $result;
	}

	public function completeWithStatusAction(int $activityId, int $ownerTypeId, int $ownerId, string $status)
	{
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return false;
		}

		$result = Helper::getInstance()->completeActivityWithStatus($activityId, $ownerTypeId, $ownerId, $status);
		if (!$result)
		{
			$this->addError(new Error('Error while trying to complete activity'));
		}

		return $result;
	}

	public function cancelMeetingAction(int $eventId, int $ownerId, int $ownerTypeId)
	{
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return false;
		}

		$result = Helper::getInstance()->cancelMeeting($eventId);
		if (!$result)
		{
			$this->addError(new Error('Error while trying to cancel meeting'));
		}

		return $result;
	}

	public function disableOptionPayAttentionToNewCrmSharingFeatureAction(): void
	{
		$userOptionName = TimelineMenuBar\Item\Sharing::PAY_ATTENTION_TO_NEW_FEATURE_OPTION_NAME;
		\CUserOptions::setOption("crm", $userOptionName, 'N');
	}
}