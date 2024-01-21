<?php

namespace Bitrix\Crm\Controller\Timeline\Calendar;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar;
use Bitrix\Crm\Integration\Calendar\EventData;
use Bitrix\Crm\Integration\Calendar\Helper;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\MessageSender\Channel;
use Bitrix\Crm\MessageSender\Channel\ChannelRepository;
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
		string $senderId,
		array $ruleArray
	): bool
	{
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return false;
		}

		$sendingResult = Helper::getInstance()->sendLinkToClient($ownerId, $contactId, $contactTypeId, $channelId, $senderId, $ruleArray);
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

	public function onRuleUpdatedAction(string $linkHash, int $ownerId, int $ownerTypeId): bool
	{
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions($ownerTypeId, $ownerId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());

			return false;
		}

		$timelineResult = Helper::getInstance()->addTimelineEntry($linkHash, EventData::SHARING_ON_RULE_UPDATED);
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
	
	public function getConfigAction(int $entityTypeId, int $entityId): array
	{
		$result = [];
		if (!\Bitrix\Crm\Service\Container::getInstance()->getUserPermissions()->checkUpdatePermissions($entityTypeId, $entityId))
		{
			$this->addError(\Bitrix\Crm\Controller\ErrorCode::getAccessDeniedError());
			
			return $result;
		}
		
		if (!\Bitrix\Main\Loader::includeModule('calendar'))
		{
			$this->addError(new Error('Calendar module not found'));
			
			return $result;
		}
		
		$context = new TimelineMenuBar\Context($entityTypeId, $entityId);
		
		$sharing = new TimelineMenuBar\Item\Sharing($context);
		$result = $sharing->getSettings();
		$result['smsConfig'] = $this->getSmsConfig($sharing, $context);
		
		return $result;
	}
	
	private function getSmsConfig(TimelineMenuBar\Item\Sharing $sharing, TimelineMenuBar\Context $context): array
	{
		return [
			'communications' => $this->getCommunications($sharing),
			'contactCenterUrl' => \Bitrix\Crm\Service\Container::getInstance()->getRouter()->getContactCenterUrl(),
			'senders' => $this->getSmsSenders($context),
		];
	}
	
	private function getCommunications(TimelineMenuBar\Item\Sharing $sharing): array
	{
		$communications = $sharing->getCommunications();
		
		foreach ($communications as $key => $communication)
		{
			$phone = current($communication['phones']);
			$communications[$key]['phones'] = [$phone];
		}
		
		return $communications;
	}
	
	private function getSmsSenders(TimelineMenuBar\Context $context): array
	{
		$senders = SmsManager::getSenderInfoList(true);
		
		$itemIdentifier = new ItemIdentifier($context->getEntityTypeId(), $context->getEntityId());
		$repo = ChannelRepository::create($itemIdentifier);
		$notificationChannel = $repo->getDefaultForSender(NotificationsManager::getSenderCode());
		
		if ($notificationChannel)
		{
			$senders[] = [
				'id' => $notificationChannel->getId(),
				'fromList' => $this->getFromList($notificationChannel),
				'name' => $notificationChannel->getName(),
				'shortName' => $notificationChannel->getShortName(),
				'canUse' => $notificationChannel->getSender()::canUse(),
			];
		}
		
		return $senders;
	}
	
	private function getFromList(Channel $channel): array
	{
		$fromList = $channel->getFromList();
		
		$result = [];
		foreach ($fromList as $item)
		{
			$result[] = [
				'id' => $item->getId(),
				'name' => $item->getName(),
				'description' => $item->getDescription(),
				'default' => $item->isDefault(),
			];
		}
		
		return $result;
	}

	public function disableOptionPayAttentionToNewCrmSharingFeatureAction(): void
	{
		$userOptionName = TimelineMenuBar\Item\Sharing::PAY_ATTENTION_TO_NEW_FEATURE_OPTION_NAME;
		\CUserOptions::setOption("crm", $userOptionName, 'N');
	}
}
