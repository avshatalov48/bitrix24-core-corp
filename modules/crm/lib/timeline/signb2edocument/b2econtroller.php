<?php

namespace Bitrix\Crm\Timeline\SignB2eDocument;

use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Crm\Timeline\HistoryDataModel\Presenter;
use Bitrix\Main\InvalidOperationException;

final class B2eController extends Timeline\Controller
{
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
	public function onStart(
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

	public function onMessageSent(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_MESSAGE_SENT,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onMessageDelivered(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_MESSAGE_DELIVERED,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onDeliveryError(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_MESSAGE_DELIVERY_ERROR,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onMemberSigningExpiredError(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_MEMBER_SIGNING_EXPIRED,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onMemberSigningError(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_MEMBER_SIGNING_ERROR,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onMemberSnilsError(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_MEMBER_SNILS_ERROR,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onCancelByEmployee(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_CANCELED_BY_EMPLOYEE,
			$identifier,
			$documentData
		);
	}

	public function onCancelByReviewer(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_CANCELED_BY_REVIEWER,
			$identifier,
			$documentData
		);
	}

	public function onCancelByEditor(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_CANCELED_BY_EDITOR,
			$identifier,
			$documentData
		);
	}

	public function onCancelByResponsiblePerson(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_CANCELED_BY_RESPONSIBILITY_PERSON,
			$identifier,
			$documentData
		);
	}

	public function onSignedByEmployee(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SIGNED_BY_EMPLOYEE,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onSignedByResponsiblePerson(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onSignedByEditor(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SIGNED_BY_EDITOR,
			$identifier,
			$documentData,
			$messageData,
		);
	}

	public function onSignedByReviewer(
		ItemIdentifier $identifier,
		DocumentData $documentData,
		MessageData $messageData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_SIGNED_BY_REVIEWER,
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
			Entry::TYPE_CATEGORY_DONE,
			$identifier,
			$documentData,
		);
	}


	public function onSignStarted(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_STARTED,
			$identifier,
			$documentData,
		);
	}

	public function onSignStopped(
		ItemIdentifier $identifier,
		DocumentData $documentData
	): array
	{
		return $this->handleSignEvent(
			Entry::TYPE_CATEGORY_STOPPED,
			$identifier,
			$documentData,
		);
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
			Entry::TYPE_CATEGORY_CREATED => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT,],
			Entry::TYPE_CATEGORY_STOPPED => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT,],
			Entry::TYPE_CATEGORY_DONE => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT,],
			Entry::TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_SIGNED_BY_EMPLOYEE => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_SIGNED_BY_REVIEWER => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_SIGNED_BY_EDITOR => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_CANCELED_BY_RESPONSIBILITY_PERSON => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_CANCELED_BY_EMPLOYEE => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_CANCELED_BY_REVIEWER => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_CANCELED_BY_EDITOR => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_MESSAGE_SENT => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_MESSAGE_DELIVERED => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_MESSAGE_DELIVERY_ERROR => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_MEMBER_SIGNING_ERROR => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_MEMBER_SIGNING_EXPIRED => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
			Entry::TYPE_CATEGORY_MEMBER_SNILS_ERROR => [TimelineEntry\Facade::SIGN_B2E_DOCUMENT_LOG,],
		];
	}

	protected function getItem(ItemIdentifier $identifier): ?Item
	{
		$factory = Container::getInstance()
			->getFactory($identifier->getEntityTypeId());
		if (!$factory)
		{
			throw new InvalidOperationException('Factory for '.$identifier->getEntityTypeId().' is not found');
		}

		return $factory->getItem($identifier->getEntityId());
	}
}
