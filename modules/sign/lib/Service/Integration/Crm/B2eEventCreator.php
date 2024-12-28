<?php

namespace Bitrix\Sign\Service\Integration\Crm;

use Bitrix\Crm\Automation\Trigger\Sign\B2e\CompletedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CoordinationAndFillingTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\CoordinationTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\FillingTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningDoneTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningStartedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningStoppedTrigger;
use Bitrix\Crm\Automation\Trigger\Sign\B2e\SigningTrigger;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\SignB2eDocument\B2eController;
use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Main\Loader;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Document\InitiatedByType;

final class B2eEventCreator
{
	public function createEvent(
		string $eventType,
		ItemIdentifier $itemIdentifier,
		EventData $eventData,
		DocumentData $documentData,
		?MessageData $messageData,
	)
	{
		if (!Loader::includeModule('crm'))
		{
			return;
		}
		$crmController = B2eController::getInstance();
		switch ($eventType)
		{
			case EventData::TYPE_ON_REGISTER:
			case EventData::TYPE_ON_CREATE:
				$crmController->onCreate($itemIdentifier, $documentData);
				break;
			case EventData::TYPE_ON_SEND:
				$crmController->onMessageSent($itemIdentifier, $documentData, $messageData);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_STOPPED:
				$crmController->onSignStopped($itemIdentifier, $documentData);
				SigningStoppedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				CompletedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				$this->completeActivity($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_CANCELED_BY_RESPONSIBILITY_PERSON:
				$crmController->onCancelByResponsiblePerson($itemIdentifier, $documentData);
				SigningStoppedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				CompletedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				$this->completeActivity($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_CANCELED_BY_EMPLOYEE:
				$crmController->onCancelByEmployee($itemIdentifier, $documentData);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_CANCELED_BY_REVIEWER:
				$crmController->onCancelByReviewer($itemIdentifier, $documentData);
				SigningStoppedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				CompletedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				$this->completeActivity($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_CANCELED_BY_EDITOR:
				$crmController->onCancelByEditor($itemIdentifier, $documentData);
				SigningStoppedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				CompletedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				$this->completeActivity($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_SIGNED_BY_EMPLOYEE:
				$crmController->onSignedByEmployee($itemIdentifier, $documentData, $messageData);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				$this->executeTriggerOnSignedByEmployee($documentData, $itemIdentifier);
				break;
			case EventData::TYPE_ON_SIGNED_BY_RESPONSIBILITY_PERSON:
				$crmController->onSignedByResponsiblePerson($itemIdentifier, $documentData, $messageData);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_SIGNED_BY_REVIEWER:
				$crmController->onSignedByReviewer($itemIdentifier, $documentData, $messageData);
				$this->executeTriggerOnSignedByReviewer($documentData, $itemIdentifier);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_SIGNED_BY_EDITOR:
				$crmController->onSignedByEditor($itemIdentifier, $documentData, $messageData);
				SigningTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_DONE:
				$crmController->onSignCompleted($itemIdentifier, $documentData);
				SigningDoneTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				CompletedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				$this->completeActivity($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_STARTED:
				$crmController->onSignStarted($itemIdentifier, $documentData);
				SigningStartedTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				$this->executeTriggerOnStart($documentData, $itemIdentifier);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_READY_BY_REVIEWER_OR_EDITOR:
				$crmController->onSignStarted($itemIdentifier, $documentData);
				CoordinationAndFillingTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				break;
			case EventData::TYPE_ON_READY_BY_REVIEWER:
				$crmController->onSignStarted($itemIdentifier, $documentData);
				CoordinationTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				break;
			case EventData::TYPE_ON_READY_BY_EDITOR:
				$crmController->onSignStarted($itemIdentifier, $documentData);
				FillingTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
				break;
			case EventData::TYPE_ON_DELIVERED:
				$crmController->onMessageDelivered($itemIdentifier, $documentData, $messageData);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_DELIVERY_ERROR:
				$crmController->onDeliveryError($itemIdentifier, $documentData, $messageData);
				break;
			case EventData::TYPE_ON_ERROR_SIGNING_EXPIRED:
				$crmController->onMemberSigningExpiredError($itemIdentifier, $documentData, $messageData);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_ERROR_SNILS_NOT_FOUND:
				$crmController->onMemberSnilsError($itemIdentifier, $documentData, $messageData);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_ERROR_REQUEST_ERROR:
				$crmController->onMemberSigningError($itemIdentifier, $documentData, $messageData);
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_MEMBER_STOPPED_BY_ASSIGNEE:
				if (method_exists($crmController, 'onMemberStoppedByAssignee'))
				{
					$crmController->onMemberStoppedByAssignee($itemIdentifier, $documentData, $messageData);
				}
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_MEMBER_STOPPED_BY_REVIEWER:
				if (method_exists($crmController, 'onMemberStoppedByReviewer'))
				{
					$crmController->onMemberStoppedByReviewer($itemIdentifier, $documentData, $messageData);
				}
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_MEMBER_STOPPED_BY_EDITOR:
				if (method_exists($crmController, 'onMemberStoppedByEditor'))
				{
					$crmController->onMemberStoppedByEditor($itemIdentifier, $documentData, $messageData);
				}
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
			case EventData::TYPE_ON_MEMBER_SIGNED_DELIVERED:
				if (method_exists($crmController, 'onSignedDocumentDelivered'))
				{
					$crmController->onSignedDocumentDelivered($itemIdentifier, $documentData);
				}
				break;
			case EventData::TYPE_ON_CONFIGURATION_ERROR:
				if (method_exists($crmController, 'onSignConfigureError'))
				{
					$crmController->onSignConfigureError($itemIdentifier, $documentData, $messageData);
				}
				break;
			case EventData::TYPE_ON_SENDING:
			case EventData::TYPE_ON_ASSIGNEE_DONE:
				$this->notifyActivityChange($crmController, $itemIdentifier);
				break;
		}
	}

	private function executeTriggerOnSignedByReviewer(DocumentData $documentData, ItemIdentifier $itemIdentifier): void
	{
		$hasEditor = Container::instance()->getMemberRepository()->isDocumentHasEditor($documentData->getDocumentId());

		if ($hasEditor === false)
		{
			SigningTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
		}
	}

	private function executeTriggerOnStart(DocumentData $documentData, ItemIdentifier $itemIdentifier): void
	{
		$initiatedByType = InitiatedByType::tryFrom($documentData->getInitiatedByType());
		if ($initiatedByType !== InitiatedByType::COMPANY)
		{
			return;
		}

		$hasReviewer = Container::instance()
			->getMemberRepository()
			->isDocumentHasReviewer($documentData->getDocumentId())
		;

		$hasEditor = Container::instance()
			->getMemberRepository()
			->isDocumentHasEditor($documentData->getDocumentId())
		;

		if (!$hasReviewer && !$hasEditor)
		{
			SigningTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
		}
	}

	private function executeTriggerOnSignedByEmployee(DocumentData $documentData, ItemIdentifier $itemIdentifier): void
	{
		$initiatedByType = InitiatedByType::tryFrom($documentData->getInitiatedByType());
		if ($initiatedByType !== InitiatedByType::EMPLOYEE)
		{
			return;
		}

		$hasReviewer = Container::instance()
			->getMemberRepository()
			->isDocumentHasReviewer($documentData->getDocumentId())
		;

		if (!$hasReviewer)
		{
			SigningTrigger::executeBySmartDocumentId($itemIdentifier->getEntityId());
		}
	}

	private function completeActivity(B2eController $b2eController, ItemIdentifier $itemIdentifier): void
	{
		if (method_exists($b2eController, 'completeActivity'))
		{
			$b2eController->completeActivity($itemIdentifier);
		}
	}

	private function notifyActivityChange(B2eController $b2eController, ItemIdentifier $itemIdentifier): void
	{
		if (method_exists($b2eController, 'notifyAboutActivityChange'))
		{
			$b2eController->notifyAboutActivityChange($itemIdentifier);
		}
	}
}
