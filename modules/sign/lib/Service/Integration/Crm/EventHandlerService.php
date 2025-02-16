<?php

namespace Bitrix\Sign\Service\Integration\Crm;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Relation\RelationManager;
use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Crm\Timeline\SignDocument\Signer;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Callback\Messages\Member\MemberStatusChanged;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Crm\Activity\Provider\SignDocument;
use \Bitrix\Crm;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Member\ChannelType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign;

class EventHandlerService
{
	private Sign\Service\Sign\MemberService $memberService;

	public function __construct(?Sign\Service\Sign\MemberService $memberService = null)
	{
		$this->memberService = $memberService ?? Container::instance()->getMemberService();
	}

	private function isAvailable():bool
	{
		return Loader::includeModule('crm') === true;
	}

	/**
	 * Handle calling from proxy and create timeline event.
	 *
	 * @param array $data
	 * @param string $secCode
	 *
	 * @throws ArgumentOutOfRangeException
	 */
	public function handleTimelineEvent(array $data, string $secCode)
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$documentHash = $data['documentHash'] ?? null;
		$memberHash = $data['memberHash'] ?? null;
		$eventType = $data['eventType'] ?? null;

		$document = Container::instance()->getDocumentRepository()->getByUid($documentHash);
		if ($document)
		{
			$member = null;
			if ($memberHash)
			{
				$member = Container::instance()->getMemberRepository()->getByUid($memberHash);
			}
			$eventData = new EventData();
			$eventData->setEventType($eventType)
				->setDocumentItem($document)
				->setMemberItem($member);

			foreach ($data as $key => $value)
			{
				if ($key !== 'documentHash' && $key !== 'memberHash' && $key !== 'eventType')
				{
					$eventData->addDataValue($key, $value);
				}
			}

			$this->createTimelineEvent(
				$eventData,
			);
		}
	}

	public function handleCurrentDocumentStatus(
		Sign\Item\Document $document,
		?Sign\Item\Member  $initiatorMember = null,
	): void
	{
		$eventType = match ($document->status)
		{
			Sign\Type\DocumentStatus::STOPPED => EventData::TYPE_ON_STOPPED,
			Sign\Type\DocumentStatus::DONE => EventData::TYPE_ON_DONE,
			default => null,
		};

		if (!isset($eventType))
		{
			return;
		}

		$initiatorUserId = $this->getStoppedByUserId($document, $initiatorMember);

		$eventData = new EventData();
		$eventData->setEventType($eventType)
			->setDocumentItem($document)
			->addDataValue(EventData::DATA_KEY_INITIATOR, $initiatorUserId)
		;

		$this->createTimelineEvent($eventData);
	}

	public function handleCurrentMemberStatus(
		Sign\Item\Document $document,
		Sign\Item\Member $member,
		MemberStatusChanged $message,
	): void
	{
		$eventTypes = $this->isByEmployee($document)
			? $this->getByEmployeeEventTypeForMember($document, $member, $message->getInitiatorUid(), $message->getErrorCode())
			: $this->getEventTypeForMember($document, $member, $message->getInitiatorUid(), $message->getErrorCode())
		;

		if (!$eventTypes)
		{
			return;
		}

		$userId = $this->getInitiatorUserIdByUid($document, $message->getInitiatorUid());

		foreach ($eventTypes as $eventType)
		{
			$eventData = new EventData();
			$eventData
				->setEventType($eventType)
				->setDocumentItem($document)
				->setMemberItem($this->getMemberForEvent($eventType, $document, $member))
				->addDataValue(EventData::DATA_KEY_INITIATOR, $userId)
				->addDataValue(EventData::DATA_KEY_PROVIDER_NAME, $message->getProvider())
				->addDataValue(EventData::DATA_KEY_SES_SIGN, $message->getSesSign())
				->addDataValue(EventData::DATA_KEY_SES_USERNAME, $message->getSesUsername())
			;

			$this->createTimelineEvent($eventData);
		}
	}

	private function getMemberForEvent(string $eventType, Sign\Item\Document $document, Sign\Item\Member $eventMember): Sign\Item\Member
	{
		return match ($eventType)
		{
			EventData::TYPE_ON_SIGNED_BY_RESPONSIBILITY_PERSON => $document->isInitiatedByEmployee()
				? $this->memberService->getSigner($document) // signer, on assignee done (by employee)
				: $eventMember // signer, on signer done (by company)
			,
			default => $eventMember,
		};
	}

	private function getStoppedByUserId(Sign\Item\Document $document, ?Sign\Item\Member $member): ?int
	{
		if ($member)
		{
			return $this->memberService->getUserIdForMember($member, $document);
		}

		return $document->stoppedById;
	}

	/**
	 * Create row of the Document changing in timeline of the (deal/document/any related object)
	 *
	 * @param EventData $eventData
	 *
	 * @return void
	 * @throws ArgumentOutOfRangeException
	 */
	public function createTimelineEvent(EventData $eventData): void
	{
		if (!$this->isAvailable())
		{
			return;
		}

		$eventType = $eventData->getEventType();

		$item = Crm\Service\Container::getInstance()
			->getFactory($eventData->getDocumentItem()->entityTypeId)
			->getItem($eventData->getDocumentItem()->entityId)
		;

		if (!$item)
		{
			return;
		}

		$documentData = $this->prepareDocumentData($item, $eventData);
		$messageData = $this->prepareMessageData($eventData);
		$itemIdentifier = ItemIdentifier::createByItem($item);

		$this->getCreator($eventData->getDocumentItem()->entityTypeId)->createEvent(
			$eventType,
			$itemIdentifier,
			$eventData,
			$documentData,
			$messageData,
		);

		SignDocument::onDocumentUpdate(
			$item->getId(),
		);
	}

	private function getDealRelation(int $smartDocumentId): ?Item
	{
		$itemId = new \Bitrix\Crm\ItemIdentifier(
			\CCrmOwnerType::SmartDocument,
			$smartDocumentId,
		);

		$itemId = (new RelationManager)->getParentElements($itemId)[0] ?? null;
		if (
			!$itemId
			|| !$itemId->getEntityId()
			|| $itemId->getEntityTypeId() !== \CCrmOwnerType::Deal
		)
		{
			return null;
		}

		return Crm\Service\Container::getInstance()
			->getFactory(\CCrmOwnerType::Deal)
			->getItem($itemId->getEntityId())
		;
	}

	private function prepareDocumentData(Item $item, EventData $eventData): DocumentData
	{
		$document = $eventData->getDocumentItem();
		$member = $eventData->getMemberItem();

		$deal = $this->getDealRelation($item->getId());

		$bindings = [];

		if ($deal)
		{
			$bindings[] = [
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Deal,
				'ENTITY_ID'=> $deal->getId(),
			];
		}

		$documentData = DocumentData::createFromArray([
			'documentId' => $document->id,
			'documentHash' => $document->uid,
			'memberHash' => $member?->uid,
			'item' => $item,
			'bindings' => $bindings,
			'initiatorUserId' => $eventData->getData()[EventData::DATA_KEY_INITIATOR] ?? null,
			'initiatedByType' => $document->initiatedByType->value,
		]);

		if ($member?->channelValue)
		{
			$documentData->addSigner(Signer::createFromArray([
					'title' => $member->channelValue,
					'hash' => $member->uid,
				],
			));
		}

		return $documentData;
	}

	/**
	 * @param EventData $eventData
	 * @return MessageData|null
	 */
	private function prepareMessageData(EventData $eventData): ?MessageData
	{
		$member = $eventData->getMemberItem();
		$document = $eventData->getDocumentItem();
		if (!$member)
		{
			return null;
		}

		$type = match ($member->channelType) {
			ChannelType::PHONE => Crm\Timeline\SignDocument\Channel::TYPE_SMS,
			ChannelType::IDLE => $this->memberService->skipChatInvitationForMember($member, $document)
				? Crm\Timeline\SignDocument\Channel::TYPE_B24
				: Crm\Timeline\SignDocument\Channel::TYPE_CHAT
			,
			default => Crm\Timeline\SignDocument\Channel::TYPE_EMAIL,
		};

		$emptyName = Loc::getMessage('SIGN_CONTROLLER_INTEGRATION_TITLE_EMPTY');
		$emptyName = $emptyName ?: 'Recipient';

		$title = $member->role === Role::ASSIGNEE
			? ($document->initiator ?? $member->channelValue ?? $emptyName)
			: ($member->name ?? $member->channelValue)
		;

		return MessageData::createFromArray([
			'recipient' => [
				'title' => $title ?: $emptyName,
				'hash' => $member->uid,
			],
			'channel' => [
				'type' => $type,
				'identifier' => $member->channelValue,
			],
			'subject' => $eventData->getData()['subject'] ?? '',
			'author' => $eventData->getData()['author'] ?? '',
			'integrityState' => $eventData->getData()['integrityState'] ?? '',
			'error' => $eventData?->getError(),
			'provider' => [
				'name' =>  $eventData->getData()[$eventData::DATA_KEY_PROVIDER_NAME] ?? null,
				'goskey' => [
					'orderId' => $eventData->getData()[$eventData::DATA_KEY_GOSKEY_ORDER_ID] ?? null,
				],
				'ses' => [
					'username' => $eventData->getData()[$eventData::DATA_KEY_SES_USERNAME] ?? null,
					'sign' => $eventData->getData()[$eventData::DATA_KEY_SES_SIGN] ?? null,
				],
			],
		]);
	}

	private function getCreator(int $entityTypeId): B2bEventCreator|B2eEventCreator
	{
		return match ($entityTypeId) {
			\CCrmOwnerType::SmartDocument => new B2bEventCreator(),
			\CCrmOwnerType::SmartB2eDocument => new B2eEventCreator(),
		};
	}

	/**
	 * @return array<string>
	 */
	private function getEventTypeForMember(
		Sign\Item\Document $document,
		Sign\Item\Member $member,
		string $initiatorUid = '',
		string $errorCode = '',
	): array
	{
		$event = match ($member->role)
		{
			Role::SIGNER => $this->getEventTypeForSigner($member, $initiatorUid, $errorCode),
			Role::ASSIGNEE => $this->getEventTypeForAssignee($member),
			Role::REVIEWER => $this->getEventTypeForReviewer($document, $member),
			Role::EDITOR => $this->getEventTypeForEditor($member),
			default => null,
		};
		return $event !== null ? [$event] : [];
	}

	/**
	 * @return array<string>
	 */
	private function getByEmployeeEventTypeForMember(
		Sign\Item\Document $document,
		Sign\Item\Member $member,
		string $initiatorUid = '',
		string $errorCode = '',
	): array
	{
		// in byCompany scenario assignee->done fired TYPE_ON_ASSIGNEE_DONE
		if ($member->role === Role::ASSIGNEE && $member->status === Sign\Type\MemberStatus::DONE)
		{
			return [
				EventData::TYPE_ON_SIGNED_BY_RESPONSIBILITY_PERSON,
				EventData::TYPE_ON_ASSIGNEE_DONE,
			];
		}

		// in byCompany scenario signer->ready fired TYPE_ON_SIGNED_BY_RESPONSIBILITY_PERSON event
		if (
			$member->role === Role::SIGNER
			&& in_array($member->status, [Sign\Type\MemberStatus::READY, Sign\Type\MemberStatus::STOPPABLE_READY], true)
		)
		{
			return [];
		}

		return $this->getEventTypeForMember($document, $member, $initiatorUid, $errorCode);
	}

	private function getEventTypeForAssignee(Sign\Item\Member $member): ?string
	{
		return match ($member->status)
		{
			Sign\Type\MemberStatus::STOPPED => EventData::TYPE_ON_CANCELED_BY_RESPONSIBILITY_PERSON,
			Sign\Type\MemberStatus::DONE => EventData::TYPE_ON_ASSIGNEE_DONE,
			default => null,
		};
	}

	private function getEventTypeForSigner(Sign\Item\Member $member, string $initiatorUid = '', string $errorCode = ''): ?string
	{
		return match ($member->status)
		{
			Sign\Type\MemberStatus::REFUSED => EventData::TYPE_ON_CANCELED_BY_EMPLOYEE,
			Sign\Type\MemberStatus::STOPPABLE_READY,
			Sign\Type\MemberStatus::READY => EventData::TYPE_ON_SIGNED_BY_RESPONSIBILITY_PERSON,
			Sign\Type\MemberStatus::DONE => EventData::TYPE_ON_SIGNED_BY_EMPLOYEE,
			/** Wait status now only for delayed goskey provider errors  */
			Sign\Type\MemberStatus::WAIT => $this->getSignerWaitEvent($errorCode),
			Sign\Type\MemberStatus::STOPPED => $this->getSignerStoppedEvent($member, $initiatorUid),
			default => null,
		};
	}

	private function getSignerStoppedEvent(Sign\Item\Member $member, string $initiatorUid): ?string
	{
		$initiator = $initiatorUid ? $this->memberService->getByUid($initiatorUid) : null;

		if (!$initiator || $initiator->documentId !== $member->documentId)
		{
			return null;
		}

		return match ($initiator->role)
		{
			Role::ASSIGNEE => EventData::TYPE_ON_MEMBER_STOPPED_BY_ASSIGNEE,
			Role::REVIEWER => EventData::TYPE_ON_MEMBER_STOPPED_BY_REVIEWER,
			Role::EDITOR => EventData::TYPE_ON_MEMBER_STOPPED_BY_EDITOR,
			default => null,
		};
	}

	private function getSignerWaitEvent(string $errorCode): string
	{
		return match ($errorCode)
		{
			Sign\Type\B2eErrorCode::EXPIRED => EventData::TYPE_ON_ERROR_SIGNING_EXPIRED,
			Sign\Type\B2eErrorCode::SNILS_NOT_FOUND => EventData::TYPE_ON_ERROR_SNILS_NOT_FOUND,
			default => EventData::TYPE_ON_ERROR_REQUEST_ERROR,
		};
	}

	private function getEventTypeForEditor(Sign\Item\Member $member): ?string
	{
		if (
			$member->status === Sign\Type\MemberStatus::DONE
			&& $this->isDocumentMustBeStopped($member->documentId)
		)
		{
			return null;
		}

		return match ($member->status)
		{
			Sign\Type\MemberStatus::REFUSED => EventData::TYPE_ON_CANCELED_BY_EDITOR,
			Sign\Type\MemberStatus::DONE => EventData::TYPE_ON_SIGNED_BY_EDITOR,
			default => null,
		};
	}

	private function getEventTypeForReviewer(Sign\Item\Document $document, Sign\Item\Member $member): ?string
	{
		if ($member->documentId === null)
		{
			return null;
		}

		if ($document->id === null)
		{
			return null;
		}

		if ($document->id !== $member->documentId)
		{
			return null;
		}

		if (
			$member->status === Sign\Type\MemberStatus::DONE
			&& $this->isDocumentMustBeStopped($member->documentId)
			&& !$document?->isInitiatedByEmployee()
		)
		{
			return null;
		}

		return match ($member->status)
		{
			Sign\Type\MemberStatus::REFUSED => EventData::TYPE_ON_CANCELED_BY_REVIEWER,
			Sign\Type\MemberStatus::DONE => EventData::TYPE_ON_SIGNED_BY_REVIEWER,
			default => null,
		};
	}

	private function getInitiatorUserIdByUid(Sign\Item\Document $document, string $memberUid): ?int
	{
		$member = $this->memberService->getMemberOfDocument($document, $memberUid);
		if (!$member)
		{
			return null;
		}

		return $this->memberService->getUserIdForMember($member, $document);
	}

	private function isDocumentMustBeStopped(int $documentId): bool
	{
		return $this->memberService->countUnfinishedSigners($documentId) === 0;
	}

	private function isByEmployee(Sign\Item\Document $document): bool
	{
		return $document->initiatedByType === Sign\Type\Document\InitiatedByType::EMPLOYEE;
	}
}
