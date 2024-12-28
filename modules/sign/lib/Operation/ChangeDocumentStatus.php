<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\B2e\MyDocumentsGrid\EventService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\HrBotMessageService;
use Bitrix\Sign\Service\Integration\Crm\EventHandlerService;
use Bitrix\Sign\Service\PullService;
use Bitrix\Sign\Service\Integration\Im\ImService;
use Bitrix\Sign\Service\UserService;
use Bitrix\Sign\Service\Sign\LegalLogService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type;
use Bitrix\Sign\Item\Integration\Im\Messages\GroupChat\B2b\SigningStartedMessage;
use Bitrix\Sign\Item\Integration\Im\Messages\GroupChat\B2b\SigningCompletedMessage;

final class ChangeDocumentStatus implements Contract\Operation
{
	private DocumentRepository $documentRepository;
	private EventHandlerService $eventHandlerService;
	private PullService $pullService;
	private HrBotMessageService $hrBotMessageService;
	private MemberRepository $memberRepository;
	private readonly MemberService $memberService;
	private LegalLogService $legalLogService;
	private readonly EventService $myDocumentGridEventService;
	private readonly ImService $imService;
	private readonly UserService $userService;

	public function __construct(
		private Item\Document $document,
		private string $status,
		private ?DateTime $signDate = null,
		private ?Item\Member $stopInitiatorMember = null,
	)
	{
		$this->documentRepository = Container::instance()->getDocumentRepository();
		$this->eventHandlerService = Container::instance()->getEventHandlerService();
		$this->pullService = Container::instance()->getPullService();
		$this->hrBotMessageService = Container::instance()->getHrBotMessageService();
		$this->memberRepository = Container::instance()->getMemberRepository();
		$this->legalLogService = Container::instance()->getLegalLogService();
		$this->memberService = Container::instance()->getMemberService();
		$this->myDocumentGridEventService = Container::instance()->getMyDocumentGridEventService();
		$this->imService = Container::instance()->getImService();
		$this->userService = Container::instance()->getUserService();
	}

	public function launch(): Main\Result
	{
		$result = new Main\Result();
		if ($this->document->id === null)
		{
			return $result->addError(new Main\Error('Empty document ID.'));
		}

		// status may be changed asynchronously
		$this->document = $this->documentRepository->getById($this->document->id);

		if (!in_array($this->document->status, Type\DocumentStatus::getAll()))
		{
			return $result->addError(new Main\Error("Unknown document status '{$this->document->status}'"));
		}

		if ($this->document->status === $this->status)
		{
			return $result;
		}

		if ($this->status === Type\DocumentStatus::DONE)
		{
			$signDate = $this->signDate ?? new \DateTime();
			$signDate->setDefaultTimeZone();
			$this->document->dateSign = $signDate;
		}

		if ($this->document->status !== $this->status)
		{
			$this->document->dateStatusChanged = new DateTime();
		}
		$this->document->status = $this->status;
		$updateResult = $this->documentRepository->update($this->document);
		if (!$updateResult->isSuccess())
		{
			return $result->addErrors($updateResult->getErrors());
		}

		if (Type\DocumentScenario::isB2EScenario($this->document->scenario ?? ''))
		{
			$this->legalLogService->registerDocumentChangedStatus($this->document, $this->stopInitiatorMember);
				$sendMessageResult = $this->hrBotMessageService->handleDocumentStatusChangedMessage(
				$this->document,
				$this->status,
				$this->stopInitiatorMember,
			);
			$result->addErrors($sendMessageResult->getErrors());
			$this->eventHandlerService->handleCurrentDocumentStatus($this->document, $this->stopInitiatorMember);
			$members = $this->memberRepository->listByDocumentId($this->document->id);
			foreach ($members->toArray() as $member)
			{
				// TODO: remove this after the "My Documents" grid is released
				$this->pullService->sendMemberStatusChanged($this->document, $member);
			}
			if ($this->status === Type\DocumentStatus::STOPPED)
			{
				$addMembersResult = (new Operation\DocumentChat\AddMembersByStoppedDocument($this->document))->launch();
				if (!$addMembersResult->isSuccess())
				{
					return $result->addErrors($addMembersResult->getErrors());
				}

				$updateMyDocumentsCounterResult = (new Operation\Document\UpdateCounters(
					Type\CounterType::SIGN_B2E_MY_DOCUMENTS,
					$this->document,
				))->launch();

				if (!$updateMyDocumentsCounterResult->isSuccess())
				{
					return $updateMyDocumentsCounterResult;
				}

				if ($this->stopInitiatorMember)
				{
					$setDocumentStoppedByResult = $this->setDocumentStoppedBy();
					if (!$setDocumentStoppedByResult->isSuccess())
					{
						return $setDocumentStoppedByResult;
					}
				}

				$kanbanPullEventOperation = new Operation\Kanban\B2e\SendDeleteEntityPullEvent($this->document);
				$kanbanPullEventOperationResult = $kanbanPullEventOperation->launch();
				if (!$kanbanPullEventOperationResult->isSuccess())
				{
					return $kanbanPullEventOperationResult;
				}

				$unsetDocumentSmartProcessResult = $this->unsetStoppedSmartB2eProcess();
				if (!$unsetDocumentSmartProcessResult->isSuccess())
				{
					return $unsetDocumentSmartProcessResult;
				}

				$result = $this->myDocumentGridEventService->onDocumentStop($this->document, $this->stopInitiatorMember);
				if (!$result->isSuccess())
				{
					return $result;
				}
			}
		}
		elseif (
			Type\DocumentScenario::isB2BScenario($this->document->scenario ?? '')
			&& $this->document->chatId !== null
		)
		{
			$sendMessageResult = $this->sendB2bGroupChatMessage($this->document);

			if (!$sendMessageResult->isSuccess())
			{
				return $sendMessageResult;
			}
		}

		return $result;
	}

	private function sendB2bGroupChatMessage(Item\Document $document): Main\Result
	{
		if (!Type\DocumentScenario::isB2BScenario($document->scenario ?? ''))
		{
			return (new Main\Result())->addError(new Main\Error('Invalid document scenario.'));
		}

		if ($document->chatId === null)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid chatId.'));
		}

		if ($document->createdById === null)
		{
			return (new Main\Result())->addError(new Main\Error('Invalid createById.'));
		}

		$user = $this->userService->getUserById($document->createdById);

		if ($user === null)
		{
			return (new Main\Result())->addError(new Main\Error('User not found.'));
		}

		return match($this->status)
		{
			Type\DocumentStatus::SIGNING => $this->imService->sendGroupChatMessage(
				$document->chatId,
				$document->createdById,
				new SigningStartedMessage($document),
			),
			Type\DocumentStatus::DONE => $this->imService->sendGroupChatMessage(
				$document->chatId,
				$document->createdById,
				new SigningCompletedMessage($document),
			),
			default => new Main\Result(),
		};
	}

	private function unsetStoppedSmartB2eProcess(): Main\Result
	{
		if (!$this->document->isInitiatedByEmployee())
		{
			return new Main\Result();
		}

		$member = $this->memberService->getSigner($this->document);
		if (in_array($member?->status, Type\MemberStatus::getReadyForSigning(), true))
		{
			$unsetDocumentSmartProcessResult = (new UnsetStoppedSmartB2eProcess($this->document))->launch();
			if (!$unsetDocumentSmartProcessResult->isSuccess())
			{
				return $unsetDocumentSmartProcessResult;
			}
		}

		return new Main\Result();
	}

	private function setDocumentStoppedBy(): Main\Result
	{
		$result = new Main\Result();

		if ($this->stopInitiatorMember === null)
		{
			return $result->addError(new Main\Error('Empty member'));
		}

		$userId = $this->memberService->getUserIdForMember(
			$this->stopInitiatorMember,
			$this->document,
		);

		if ($userId === null)
		{
			return $result->addError(new Main\Error('Can not find user'));
		}

		$this->document->stoppedById = $userId;
		$updateResult = $this->documentRepository->update($this->document);
		if (!$updateResult->isSuccess())
		{
			return $updateResult;
		}

		return $result;
	}
}
