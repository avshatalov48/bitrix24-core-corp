<?php

namespace Bitrix\Sign\Service\Integration\Crm;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\SignDocument\Controller;
use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Crm\Automation\Trigger;
use Bitrix\Sign\Type\Member\Role;

final class B2bEventCreator
{
	public function createEvent(
		string $eventType,
		ItemIdentifier $itemIdentifier,
		EventData $eventData,
		DocumentData $documentData,
		?MessageData $messageData,
	)
	{
		$crmController = Controller::getInstance();
		switch ($eventType)
		{
			case EventData::TYPE_ON_CREATE:
				$crmController->onCreate($itemIdentifier, $documentData);
				break;
			case EventData::TYPE_ON_SEND:
				$crmController->onSend($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_REGISTER:
				$crmController->onRegister($itemIdentifier, $documentData);
				break;
			case EventData::TYPE_ON_FILL:
				$crmController->onFill($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_COMPLETE:
				$crmController->onComplete($itemIdentifier, $documentData);
				break;
			case EventData::TYPE_ON_REQUEST_RESULT:
				$crmController->onRequested($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_VIEW:
				$crmController->onView($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_SIGN:
				$crmController->onSigned($itemIdentifier, $documentData, $messageData);
				if ($eventData->getMemberItem()->role === Role::ASSIGNEE)
				{
					Trigger\Sign\InitiatorSignedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				}
				else
				{
					Trigger\Sign\OtherMemberSignedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				}
				break;
			case EventData::TYPE_ON_SIGN_COMPLETED:
				$crmController->onSignCompleted($itemIdentifier, $documentData);
				Trigger\Sign\AllMembersSignedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				break;
			case EventData::TYPE_ON_INTEGRITY_SUCCESS:
				$crmController->onIntegritySuccess($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_PREPARE_TO_FILL:
				$crmController->onPrepareToFill($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_SEND_REPEATEDLY:
				$crmController->onSendRepeatedly($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_SEND_FINAL:
				$crmController->onSendFinal($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_SEND_INTEGRITY_FAILURE_NOTICE:
				$crmController->onSendIntegrityFailureNotice($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_PIN_SEND_LIMIT_REACHED:
				$crmController->onPinSendLimitReached($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::NOTIFICATION_DELIVERED:
				$crmController->onNotificationDelivered($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::NOTIFICATION_ERROR:
				$crmController->onNotificationError($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::NOTIFICATION_READ:
				$crmController->onNotificationRead($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_CONFIGURATION_ERROR:
				$crmController->onSignConfigureError($itemIdentifier, $documentData, $messageData);
				break;
		}
	}
}
