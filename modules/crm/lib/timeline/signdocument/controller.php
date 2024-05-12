<?php

namespace Bitrix\Crm\Timeline\SignDocument;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\InvalidOperationException;

final class Controller extends Timeline\Controller
{
	public const ADD_EVENT_NAME = 'timeline_signdocument_add';

	/**
	 * @internal for development phase only
	 * @todo delete it later
	 *
	 * @param int $typeCategoryId
	 * @param int $documentId
	 * @param ItemIdentifier $identifier
	 * @return void
	 */
	public static function emulateEmittingEvent(
		int $typeCategoryId,
		array $documentData,
		ItemIdentifier $identifier,
		?MessageData $messageData = null
	): array
	{
		$controller = new self();

		$item = $controller->getItem($identifier);
		if (!$item)
		{
			throw new ArgumentException('Could not find item for ' . $identifier);
		}

		$document = DocumentData::createFromArray($documentData);
		$controller->enrichDocumentDataByItem($item, $document);

		return $controller->handleSignEvent($typeCategoryId, $identifier, $document, $messageData);
	}

	/**
	 * @param Item $item
	 * @return DocumentData
	 *@internal for development phase only
	 * @todo delete it later
	 */
	private function enrichDocumentDataByItem(Item $item, DocumentData $eventData): void
	{
		if ($item->hasField(Item::FIELD_NAME_CONTACT_BINDINGS))
		{
			foreach ($item->getContactBindings() as $binding)
			{
				$contactId = $binding['CONTACT_ID'];
				$contact = \CCrmContact::GetById($contactId, false);
				if ($contact)
				{
					$eventData->addBinding(new ItemIdentifier(\CCrmOwnerType::Contact, $binding['CONTACT_ID']));
					$eventData->addSigner(
						Signer::createFromArray([
							'title' => \CCrmContact::PrepareFormattedName($contact),
						])
					);
				}
			}
		}

		$eventData->setItem($item);
		$myCompanyId = $item->getMycompanyId();
		if ($myCompanyId > 0)
		{
			$company = CompanyTable::getById($myCompanyId)->fetchObject();
			if ($company)
			{
				$eventData->setMySigner(
					Signer::createFromArray([
						'title' => $company->getTitle(),
					])
				);
			}
		}
	}

	public function onCreate(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_CREATED,
			$identifier,
			$documentData,
		);
	}

	public function onSend(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SENT,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onRegister(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_PRINTED_FORM,
			$identifier,
			$documentData
		);
	}

	public function onView(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_VIEWED,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onPrepareToFill(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_PREPARED_TO_FILL,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onFill(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_FILLED,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onSigned(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SIGNED,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onSignCompleted(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SIGN_COMPLETED,
			$identifier,
			$documentData,
		);
	}

	public function onSignConfigureError(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SIGN_CONFIGURATION_ERROR,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onSendFinal(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SENT_FINAL,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onComplete(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		// todo find the document activity
		// todo if it is already closed - do nothing
		// todo check that this event binds to $identifier only

		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_COMPLETED,
			$identifier,
			$documentData,
		);
	}

	public function onRequested(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_REQUESTED,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onSendRepeatedly(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SENT_REPEATEDLY,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onIntegritySuccess(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_INTEGRITY_SUCCESS,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onPinSendLimitReached(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_PIN_SEND_LIMIT_REACHED,
			$identifier,
			$documentData,
			$messageData
		);
	}

	public function onSendIntegrityFailureNotice(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SENT_INTEGRITY_FAILURE,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onMessageStatusChange(
		ItemIdentifier $identifier,
		int $entryId,
		MessageData $messageData
	): bool
	{
		$entry = Timeline\Entity\TimelineTable::getList([
			'select' => ['ID', 'SETTINGS'],
			'filter' => [
				'=ID' => $entryId,
				'=TYPE_ID' => Timeline\TimelineType::SIGN_DOCUMENT,
				'=TYPE_CATEGORY_ID' => [
					Entry::TYPE_CATEGORY_SENT,
					Entry::TYPE_CATEGORY_SENT_FINAL,
					Entry::TYPE_CATEGORY_SENT_REPEATEDLY,
				],
				'=ASSOCIATED_ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
				'=ASSOCIATED_ENTITY_ID' => $identifier->getEntityId(),
			],
			'limit' => 1,
		])->fetch();
		if (!$entry)
		{
			return false;
		}
		$entry['SETTINGS'][Presenter\SignDocument::MESSAGE_DATA_KEY] = $messageData->toArray();

		return Timeline\Entity\TimelineTable::update($entryId, $entry)->isSuccess();
	}

	protected function handleSignEvent(
		int $typeCategoryId,
		ItemIdentifier $identifier,
		DocumentData $documentData,
		?MessageData $messageData = null
	): array
	{
		$documentData->addBinding($identifier);
		$item = $documentData->getItem();
		$authorId = $documentData->getAuthorId();
		if (!$authorId)
		{
			if (!$item)
			{
				$item = $this->getItem($identifier);
			}
			if ($item)
			{
				$authorId = $item->getAssignedById()
					?? $item->getUpdatedBy()
					?? $item->getMovedBy()
					?? $item->getCreatedBy()
				;
			}
		}
		if (!$authorId)
		{
			$authorId = Container::getInstance()->getContext()->getUserId();
		}

		$timelineEntries = [];

		foreach ($this->getCategoryMap()[$typeCategoryId] as $entryType)
		{
			$timelineEntries[] = $this->getTimelineEntryFacade()->create(
				$entryType,
				[
					'ENTITY_TYPE_ID' => $identifier->getEntityTypeId(),
					'ENTITY_ID' => $identifier->getEntityId(),
					'TYPE_CATEGORY_ID' => $typeCategoryId,
					'AUTHOR_ID' => $authorId,
					Presenter\SignDocument::DOCUMENT_DATA_KEY => $documentData,
					Presenter\SignDocument::MESSAGE_DATA_KEY => $messageData,
				],
			);
		}

		if (empty($timelineEntries))
		{
			return [];
		}

		foreach ($documentData->getBindings() as $binding)
		{
			foreach ($timelineEntries as $entry)
			{
				$this->sendPullEventOnAdd($binding, $entry);
			}
		}

		return $timelineEntries;
	}

	protected function getCategoryMap(): array
	{
		return [
			Entry::TYPE_CATEGORY_CREATED => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_SENT => [
				// TimelineEntry\Facade::SIGN_DOCUMENT_LOG,
				TimelineEntry\Facade::SIGN_DOCUMENT,
			],
			Entry::TYPE_CATEGORY_PRINTED_FORM => [TimelineEntry\Facade::SIGN_DOCUMENT,],
			Entry::TYPE_CATEGORY_VIEWED => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_PREPARED_TO_FILL => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_FILLED => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_SIGNED => [
				TimelineEntry\Facade::SIGN_DOCUMENT_LOG,
				TimelineEntry\Facade::SIGN_DOCUMENT,
			],
			Entry::TYPE_CATEGORY_SIGN_COMPLETED => [
//				TimelineEntry\Facade::SIGN_DOCUMENT_LOG,
				TimelineEntry\Facade::SIGN_DOCUMENT,
			],
			Entry::TYPE_CATEGORY_SENT_FINAL => [TimelineEntry\Facade::SIGN_DOCUMENT,],
			Entry::TYPE_CATEGORY_COMPLETED => [TimelineEntry\Facade::SIGN_DOCUMENT,],
			Entry::TYPE_CATEGORY_REQUESTED => [TimelineEntry\Facade::SIGN_DOCUMENT,],
			Entry::TYPE_CATEGORY_SENT_REPEATEDLY => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_INTEGRITY_SUCCESS => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_INTEGRITY_FAILURE => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_SENT_INTEGRITY_FAILURE => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_PIN_SEND_LIMIT_REACHED => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_NOTIFICATION_DELIVERED => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_NOTIFICATION_ERROR => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_NOTIFICATION_READ => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_SIGN_CONFIGURATION_ERROR => [TimelineEntry\Facade::SIGN_DOCUMENT_LOG,],
		];
	}

	protected function getItem(ItemIdentifier $identifier): ?Item
	{
		$factory = Container::getInstance()->getFactory($identifier->getEntityTypeId());
		if (!$factory)
		{
			throw new InvalidOperationException('Factory for ' . $identifier->getEntityTypeId() . ' is not found');
		}

		return $factory->getItem($identifier->getEntityId());
	}

	public function onNotificationDelivered(ItemIdentifier $identifier, DocumentData $documentData,
		?MessageData $messageData)
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_NOTIFICATION_DELIVERED,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onNotificationError(ItemIdentifier $identifier, DocumentData $documentData,
		?MessageData $messageData)
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_NOTIFICATION_ERROR,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onNotificationRead(ItemIdentifier $identifier, DocumentData $documentData,
		?MessageData $messageData)
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_NOTIFICATION_READ,
			$identifier,
			$documentData,
			$messageData,
		);
	}
}
