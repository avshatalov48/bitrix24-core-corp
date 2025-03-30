<?php

namespace Bitrix\Sign\Operation;

use Bitrix\Main;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Analytic\AnalyticService;
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
	private readonly AnalyticService $analyticService;

	public function __construct(
		private Item\Document $document,
		private readonly string $status,
		private readonly ?DateTime $signDate = null,
		private readonly ?Item\Member $initiatorMember = null,
	)
	{
		$container = Container::instance();
		$this->documentRepository = $container->getDocumentRepository();
		$this->eventHandlerService = $container->getEventHandlerService();
		$this->pullService = $container->getPullService();
		$this->hrBotMessageService = $container->getHrBotMessageService();
		$this->memberRepository = $container->getMemberRepository();
		$this->legalLogService = $container->getLegalLogService();
		$this->memberService = $container->getMemberService();
		$this->myDocumentGridEventService = $container->getMyDocumentGridEventService();
		$this->imService = $container->getImService();
		$this->userService = $container->getUserService();
		$this->analyticService = $container->getAnalyticService();
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
			$this->legalLogService->registerDocumentChangedStatus($this->document, $this->initiatorMember);

			$sendMessageResult = $this->hrBotMessageService->handleDocumentStatusChangedMessage(
				$this->document,
				$this->status,
				$this->initiatorMember,
			);
			$result->addErrors($sendMessageResult->getErrors());

			$this->eventHandlerService->handleCurrentDocumentStatus($this->document, $this->initiatorMember);

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

				if ($this->initiatorMember)
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

				$result = $this->myDocumentGridEventService->onDocumentStop($this->document, $this->initiatorMember);
				if (!$result->isSuccess())
				{
					return $result;
				}
			}
			elseif ($this->status === Type\DocumentStatus::DONE && $this->document->isInitiatedByEmployee())
			{
				$signer = $members->findFirstByRole(Type\Member\Role::SIGNER);
				if ($signer)
				{
					$result = $this->myDocumentGridEventService->onMemberStatusChanged($this->document, $signer);
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
		$this->collectAnalytics();

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

		if ($this->initiatorMember === null)
		{
			return $result->addError(new Main\Error('Empty member'));
		}

		$userId = $this->memberService->getUserIdForMember(
			$this->initiatorMember,
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

	private function collectAnalytics(): void
	{
		if (Type\DocumentScenario::isB2bScenarioByDocument($this->document) && $this->status === Type\DocumentStatus::DONE)
		{
			$member = $this->memberRepository->getByPartyAndDocumentId($this->document->id, $this->document->parties);

			$analyticsEvent = (new Main\Analytics\AnalyticsEvent(
				'document_signed',
				'sign',
				'documents',
			))
				->setType('b2b')
				->setStatus('success')
				->setP5("docId_{$this->document->id}")
			;
			$this->analyticService->sendEventWithSigningContext(
				$analyticsEvent,
				$member,
			);
		}
	}
}
