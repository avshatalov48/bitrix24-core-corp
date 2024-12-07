<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Sign\Callback\Messages\Member\MemberStatusChanged;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Sign\Operation\DocumentChat\AddMemberByDocument;
use Bitrix\Sign\Operation\Kanban\B2e\SendUpdateEntityPullEvent;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\HrBotMessageService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\Document\ProviderCodeService;
use Bitrix\Sign\Service\Sign\LegalLogService;
use Bitrix\Sign\Type;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Main;
use Bitrix\Sign\Service\Integration\Crm\EventHandlerService;
use Bitrix\Sign\Service\PullService;

final class ChangeMemberStatus implements Contract\Operation
{
	public const MEMBER_STATUS_ALREADY_SET_ERROR_CODE = 'MEMBER_STATUS_ALREADY_SET';
	private EventHandlerService $eventHandlerService;
	private PullService $pullService;
	private HrBotMessageService $hrBotMessageService;
	private LegalLogService $legalLogService;
	private MemberRepository $memberRepository;
	private ?MemberStatusChanged $message = null;
	private  readonly ProviderCodeService $providerCodeService;

	public function __construct(
		private readonly Item\Member $member,
		private readonly Item\Document $document,
		private readonly string $status,
		?ProviderCodeService $providerService = null,
	)
	{
		$this->eventHandlerService = Container::instance()->getEventHandlerService();
		$this->pullService = Container::instance()->getPullService();
		$this->hrBotMessageService = Container::instance()->getHrBotMessageService();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->legalLogService = Container::instance()->getLegalLogService();
		$this->providerCodeService = $providerService ?? Container::instance()->getProviderCodeService();
	}

	public function setMessage(MemberStatusChanged $message): self
	{
		$this->message = $message;

		return $this;
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();

		if ($this->member->id === null)
		{
			return $result->addError(new Main\Error('Empty member ID.'));
		}

		if ($this->member->documentId !== $this->document->id)
		{
			return $result->addError(new Main\Error('Wrong document.'));
		}

		if (!in_array($this->member->status, Type\MemberStatus::getAll()))
		{
			return $result->addError(new Main\Error("Unknown member status '{$this->member->status}'"));
		}

		if (
			$this->message === null
			&& $this->member->status === $this->status
		)
		{
			return $result->addError(
				new Main\Error(
					'Member status is already set.',
					self::MEMBER_STATUS_ALREADY_SET_ERROR_CODE,
				)
			);
		}

		$this->member->status = $this->status;
		if ($this->member->dateSigned === null && $this->status === Type\MemberStatus::DONE)
		{
			$this->member->dateSigned = new Main\Type\DateTime();
		}

		$updateResult = $this->memberRepository->update($this->member);
		if (!$updateResult->isSuccess())
		{
			return $result->addErrors($updateResult->getErrors());
		}

		if ($this->sendReadyEvents() === false)
		{
			$this->sendKanbanPullEvent();
		}

		if (Type\DocumentScenario::isB2EScenario($this->document->scenario ?? ''))
		{
			$addMemberResult = (new AddMemberByDocument($this->member, $this->document))->launch();
			if (!$addMemberResult->isSuccess())
			{
				return $result->addErrors($addMemberResult->getErrors());
			}
		}

		if (
			$this->message !== null
			&& Type\DocumentScenario::isB2EScenario($this->document->scenario ?? '')
		)
		{
			$this->legalLogService->registerFromMemberStatusChanged($this->document, $this->member, $this->message);

			$this->eventHandlerService->handleCurrentMemberStatus($this->document, $this->member, $this->message);

			$sendMessageResult = $this->hrBotMessageService->handleMemberStatusChangedMessage($this->document, $this->member);
			$result->addErrors($sendMessageResult->getErrors());

			if (
				$sendMessageResult->isSuccess()
				&& $this->member->status !== Type\MemberStatus::PROCESSING
			)
			{
				$this->onStatusChangedMessageTimelineEvent();
			}

			$memberCollection = $this->memberRepository->listByDocumentId($this->document->id);
			$this->pullService->sendMemberStatusChanged($this->document, $this->member, $memberCollection);
		}

		$updateReminderSettingsResult = $this->updateReminderSettings($this->document, $this->member);
		$result->addErrors($updateReminderSettingsResult->getErrors());

		return $result;
	}

	private function onStatusChangedMessageTimelineEvent(): void
	{
		$isSignerDone = $this->member->role === Type\Member\Role::SIGNER
			&& $this->member->status === Type\MemberStatus::DONE
		;
		switch (true)
		{
			case $isSignerDone:
			{
				/**
				 * A signed copy of the document was received by the employee
				 * @see \Bitrix\Crm\Timeline\SignB2eDocument\B2eController::onSignedDocumentDelivered
				 */
				$eventType = EventData::TYPE_ON_MEMBER_SIGNED_DELIVERED;
				$eventData = new EventData();
				$eventData->setEventType($eventType)
					->setDocumentItem($this->document)
					->setMemberItem($this->member);
				$this->eventHandlerService->createTimelineEvent($eventData);
				break;
			}
		}
	}

	private function updateAssigneeReminderSettings(Item\Member $currentMember, Item\Document $document, Main\Type\DateTime $now): Main\Result
	{
		if (
			Type\MemberStatus::WAIT !== $currentMember->status
			|| $currentMember->role !== Type\Member\Role::SIGNER
		)
		{
			return new Main\Result();
		}

		$assignee = $this->memberRepository->getAssigneeByDocumentId($document->id);
		if ($assignee === null || !Type\MemberStatus::isReadyForSigning($assignee->status))
		{
			return new Main\Result();
		}

		$this->refreshReminderSettings($assignee, $now);

		return $this->memberRepository->update($assignee);
	}

	private function refreshReminderSettings(Item\Member $member, Main\Type\DateTime $now): void
	{
		$member->reminder->startDate = $now;
		$member->reminder->completed = false;
		$member->reminder->lastSendDate = null;
		$member->reminder->plannedNextSendDate = null;
	}

	private function sendReadyEvents(): bool
	{
		if (Type\DocumentScenario::isB2EScenario($this->document->scenario) === false)
		{
			return false;
		}

		if (Type\MemberStatus::isReadyForSigning($this->member->status) === false)
		{
			return false;
		}

		if (in_array($this->member->role,  [Type\Member\Role::REVIEWER, Type\Member\Role::EDITOR], true) === false)
		{
			return false;
		}

		$result = false;
		$this->sendEvent(EventData::TYPE_ON_READY_BY_REVIEWER_OR_EDITOR);
		if ($this->member->role === Type\Member\Role::REVIEWER)
		{
			$result = $this->sendEvent(EventData::TYPE_ON_READY_BY_REVIEWER);
		}
		elseif ($this->member->role === Type\Member\Role::EDITOR)
		{
			$result = $this->sendEvent(EventData::TYPE_ON_READY_BY_EDITOR);
		}

		return $result;
	}

	private function updateReminderSettings(Item\Document $document, Item\Member $member): Main\Result
	{
		$now = new Main\Type\DateTime();
		$shouldUpdate = false;
		$result = $this->providerCodeService->loadByDocument($document);
		if (!$result->isSuccess())
		{
			return $result;
		}

		if (Type\MemberStatus::isFinishForSigning($member->status) && $member->reminder->completed === false)
		{
			$member->reminder->completed = true;
			$shouldUpdate = true;
		}
		elseif (Type\MemberStatus::isReadyForSigning($member->status))
		{
			$result = (new Member\Reminder\Start($document))->launch();
			if (!$result->isSuccess())
			{
				return $result;
			}
			$this->refreshReminderSettings($member, $now);
			$shouldUpdate = true;
		}
		$result = $this->updateAssigneeReminderSettings($member, $document, $now);
		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($shouldUpdate)
		{
			return $this->memberRepository->update($member);
		}

		return new Main\Result();
	}

	private function sendEvent(string $eventType): bool
	{
		$eventData = new EventData();
		$eventData->setEventType($eventType);
		$eventData->setDocumentItem($this->document);
		$result = false;

		try
		{
			$this->eventHandlerService->createTimelineEvent($eventData);
			$result = true;
		}
		catch (Main\ArgumentOutOfRangeException)
		{
		}

		return $result;
	}

	private function sendKanbanPullEvent(): void
	{
		$kanbanPullEventOperation = new SendUpdateEntityPullEvent($this->document);
		$kanbanPullEventOperation->launch();
	}
}
