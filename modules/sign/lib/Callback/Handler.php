<?php

namespace Bitrix\Sign\Callback;

use Bitrix\Main;
use Bitrix\Sign\Callback\Messages\Member\InviteToSign;
use Bitrix\Sign\Callback\Messages\Member\MemberStatusChanged;
use Bitrix\Sign\Controllers;
use Bitrix\Sign\Document as DocumentCore;
use Bitrix\Sign\File;
use Bitrix\Sign\Integration\CRM\Model\EventData;
use Bitrix\Sign\Item;
use Bitrix\Sign\Operation\Document\SaveResultFile;
use Bitrix\Sign\Operation\FillFields;
use Bitrix\Sign\Operation;
use Bitrix\Sign\Operation\SyncMemberStatus;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\EntityFileRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Integration\Imbot\HrBot;
use Bitrix\Sign\Service\B2e\MyDocumentsGrid;
use Bitrix\Sign\Service\Sign\LegalLogService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type;
use Bitrix\Main\Grid\Cell;
use Bitrix\Sign\Operation\ChangeDocumentStatus;
use Bitrix\Sign\Service\PullService;
use Bitrix\Sign\Ui\Member\Stage;

class Handler
{
	public const OLD_API_VERSION = 1;
	public const FILTER_COUNTER_TAG = 'SIGN_CALLBACK_MEMBER_STATUS_CHANGED';
	private PullService $pullService;
	private MemberService $memberService;
	private LegalLogService $legalLogService;
	private EntityFileRepository $entityFileRepository;
	private readonly MyDocumentsGrid\EventService $myDocumentGridService;

	public function __construct(
		private readonly DocumentRepository $documentRepository,
		private readonly MemberRepository $memberRepository,
	)
	{
		$this->pullService = Container::instance()->getPullService();
		$this->memberService = Container::instance()->getMemberService();
		$this->legalLogService = Container::instance()->getLegalLogService();
		$this->entityFileRepository = Container::instance()->getEntityFileRepository();
		$this->myDocumentGridService = Container::instance()->getMyDocumentGridEventService();
	}

	public function execute(array $payload): Main\Result
	{
		$result = new Main\Result();

		$message = Messages\Factory::createMessage($payload['type'] ?? '', $payload['data'] ?? []);
		switch ($message::Type)
		{
			case Messages\DocumentStatus::Type: {
				/** @var Messages\DocumentStatus $message */
				$document = $this->documentRepository->getByUid($message->getCode());
				if (!$document)
				{
					return $result->addError(new Main\Error('Invalid callback token.'));
				}

				if ($document->status === $message->getStatus())
				{
					return new Main\Result();
				}

				// FIXME there is no initiatorUid for some scenarios
				return (new ChangeDocumentStatus(
					$document,
					$message->getStatus(),
					$message->getSignDate(),
					$this->memberService->getMemberOfDocument($document, $message->getInitiatorUid()),
				))->launch();
			}
			case Messages\Member\InviteToSign::Type:
			{
				/** @var Messages\Member\InviteToSign $message */
				$document = $this->documentRepository->getByUid($message->getDocumentUid());
				if ($document === null)
				{
					$result->addError(new Main\Error('Document doesnt exist'));
					break;
				}
				$member = $this->memberRepository->getByUid($message->getMemberUid());
				if ($member === null || $member->documentId !== $document->id)
				{
					$result->addError(new Main\Error('Member doesnt exist'));
					break;
				}

				$this->processInviteToSign($document, $member, $message, $result);
				break;
			}
			case Messages\Mobile\SigningConfirm::Type:
			{
				/** @var Messages\Mobile\SigningConfirm $message */
				$document = $this->documentRepository->getByUid($message->getDocumentUid());
				if ($document === null)
				{
					$result->addError(new Main\Error('Document doesnt exist'));
					break;
				}
				$member = $this->memberRepository->getByUid($message->getMemberUid());
				if ($member === null || $member->documentId !== $document->id)
				{
					$result->addError(new Main\Error('Member doesnt exist'));
					break;
				}

				$sendSignConfirmationEventResult = Container::instance()
					->getMobileService()
					->sendSignConfirmationEvent($member)
				;
				$result->addErrors($sendSignConfirmationEventResult->getErrors());
				break;
			}
			case Messages\Member\MemberStatusChanged::Type:
			{
				/** @var Messages\Member\MemberStatusChanged $message */
				$result = $this->processMemberStatusChanged($message);
				break;
			}
			case Messages\TimelineEvent::Type:
			{
				/** @var Messages\TimelineEvent $message */
				Main\DI\ServiceLocator::getInstance()
					->get('sign.service.integration.crm.events')
					->handleTimelineEvent(
						$message->toArray(),
						$message->getSecurityCode()
					);
				break;
			}
			case Messages\Member\MemberResultFile::Type:
			{
				/** @var Messages\Member\MemberResultFile $message */
				$this->processSaveResultFileForMember($message, $result);
				break;
			}
			case Messages\Member\MemberPrintVersionFile::Type:
			{
				/** @var Messages\Member\MemberPrintVersionFile $message */
				$this->processSavePrintVersionFileForMember($message, $result);
				break;
			}
			case Messages\ResultFile::Type:
			{
				/** @var Messages\ResultFile $message */
				$this->processSaveResultFile($message, $result);
				break;
			}
			case Messages\ReadyLayoutCommand::Type:
			{
				/** @var Messages\ReadyLayoutCommand $message */
				$this->processHandleReadyLayoutCommand($message, $result);
				break;
			}
			case Messages\DocumentOperation::Type:
			{
				/** @var Messages\DocumentOperation $message */
				$this->processDocumentOperation($message, $result);
				break;
			}
			case Messages\FieldSet::Type:
			{
				/** @var Messages\FieldSet $message */
				$this->processFieldSet($message, $result);
				break;
			}
			default:
				$result->addError(new Main\Error('Message of unknown type.'));
		}

		return $result;
	}

	private function processFieldSet(Messages\FieldSet $message, Main\Result $result): void
	{
		$document = DocumentCore::getByHash($message->getDocumentCode());
		if (!$document)
		{
			$result->addError(new Main\Error('Invalid callback token.'));

			return;
		}

		if (!isset($message->getData()['fields']))
		{
			$result->addError(new Main\Error('Invalid fields data.'));

			return;
		}

		$result = (new FillFields(
			$message->getData()['fields'],
			Container::instance()->getMemberService()->getByUid($message->getMemberCode())
		))->launch();

		if (!$result->isSuccess())
		{
			$result->addError(new Main\Error('Failed to set fields'));
		}
	}

	private function processSaveResultFile(Messages\ResultFile $message, Main\Result $result): void
	{
		$file = $message->getData()['file'] ?? null;
		if ($file === null)
		{
			$result->addError(new Main\Error('File is empty.'));

			return;
		}

		if (!is_array($file))
		{
			$result->addError(new Main\Error('Invalid file data.'));

			return;
		}

		$saveResultFileResult = (new SaveResultFile($message->getDocumentCode(), $file))->launch();

		if (!$saveResultFileResult->isSuccess())
		{
			$result->addErrors($saveResultFileResult->getErrors());
		}
	}

	private function processSaveResultFileForMember(
		Messages\Member\MemberResultFile $message,
		Main\Result $result
	): void
	{
		$document = Container::instance()->getDocumentRepository()->getByUid($message->getDocumentUid());
		if (!$document)
		{
			$result->addError(new Main\Error('Invalid callback token.'));

			return;
		}

		if (!isset($message->getData()['file']))
		{
			$result->addError(new Main\Error('Invalid file data.'));

			return;
		}

		$file = new File($message->getData()['file']);
		$fsFile = Item\Fs\File::createByLegacyFile($file);
		$fsFile->dir = '';

		$member = $this->memberRepository->getByUid($message->getMemberUid());
		if (!$member)
		{
			$result->addError(new Main\Error('Member not found'));

			return;
		}

		$saveResult = (new Operation\Member\ResultFile\Save($document, $member, $fsFile))->launch();
		$result->addErrors($saveResult->getErrors());
	}

	private function processSavePrintVersionFileForMember(
		Messages\Member\MemberPrintVersionFile $message,
		Main\Result $result
	): void
	{
		$member = $this->memberRepository->getByUid($message->getMemberUid());
		$document = $this->documentRepository->getByUid($message->getDocumentUid());

		if (!$member)
		{
			$result->addError(new Main\Error('Member not found'));

			return;
		}

		if (!$document)
		{
			$result->addError(new Main\Error('Document not found'));

			return;
		}

		if ($member->documentId !== $document->id)
		{
			$result->addError(new Main\Error('Document id mismatch'));

			return;
		}

		if (!isset($message->getData()['file']))
		{
			$result->addError(new Main\Error('Invalid file data'));

			return;
		}

		$file = new File($message->getData()['file']);
		$fsFile = \Bitrix\Sign\Item\Fs\File::createByLegacyFile($file);
		$fsFile->dir = '';

		$fsRepo = Container::instance()->getFileRepository();
		$saveResult = $fsRepo->put($fsFile);

		if (!$saveResult->isSuccess())
		{
			$result->addErrors($saveResult->getErrors());

			return;
		}

		$isDone = $this->addFileItem(
			$member,
			$fsFile,
			Type\EntityFileCode::PRINT_VERSION,
		);

		if (!$isDone->isSuccess())
		{
			$result->addError(new Main\Error('Failed to save file'));
		}
	}

	private function logMemberFileSaved(
		?Item\Document $document,
		?Item\Member $member,
		Item\Fs\File $fsFile,
	): void
	{
		if ($document && $member)
		{
			$this->legalLogService->registerMemberFileSaved($document, $member, $fsFile->id);
		}
	}

	private function updateMemberDateSigned(?Item\Member $member): void
	{
		if ($member !== null && $member->dateSigned === null)
		{
			$member->dateSigned = new Main\Type\DateTime();
			$this->memberRepository->update($member);
		}
	}

	private function addFileItem(
		?Item\Member $member,
		?Item\Fs\File $fsFile,
		int $code,
	): Main\Result
	{
		$fileItem = new Item\EntityFile(
			id: null,
			entityTypeId: Type\EntityType::MEMBER,
			entityId: $member->id,
			code: $code,
			fileId: $fsFile->id,
		);

		return $this->entityFileRepository->add($fileItem);
	}

	private function processDocumentOperation(Messages\DocumentOperation $message, Main\Result $result): void
	{
		$member = $this->memberRepository->getByUid($message->getMemberCode());
		if (!$member)
		{
			$result->addError(new Main\Error('Invalid member token.'));

			return;
		}

		if ($message->getOperationCode() === 'DOCUMENT_SET_USER_DATA')
		{
			$member->status = Type\MemberStatus::DONE;
			$updateResult = $this->memberRepository->update($member);
		}
		else
		{
			$updateResult = $this->memberRepository->setAsVerified($member);
		}

		if (!$updateResult->isSuccess())
		{
			$result->addError(new Main\Error('Error handling document status setting.'));
		}
	}

	private function processHandleReadyLayoutCommand(
		Messages\ReadyLayoutCommand $message,
		Main\Result $result
	): void
	{
		//todo refactoring
		$document = DocumentCore::getByHash($message->getDocumentCode());
		if (!$document)
		{
			$result->addError(new Main\Error('Invalid callback token.'));

			return;
		}

		if (!\Bitrix\Main\Loader::includeModule('pull'))
		{
			$result->addError(new Main\Error('Does not include module pull'));

			return;
		}
		$version = (int)($message->getData()['version'] ?? self::OLD_API_VERSION);

		if ($version === self::OLD_API_VERSION)
		{
			$command = 'layoutIsReady';
			$params = $document->getLayout();
		}
		else
		{
			$command = 'blankIsReady';
			$params = (new Controllers\V1\Document\Pages())->listAction($message->getDocumentCode());
			$params += ['uid' => $document->getUid()];
		}

		\Bitrix\Pull\Event::add($document->getModifiedUserId(), [
			'module_id' => 'sign',
			'command' => $command,
			'params' => $params,
		]);
	}

	private function processInviteToSign(
		Item\Document $document,
		Item\Member $member,
		InviteToSign $message,
		Main\Result $result,
	): void
	{
		if ($member->entityId === null)
		{
			return;
		}

		$member->dateSend = new Type\DateTime();
		$this->memberRepository->update($member);

		$this->sendChatOnSendTimelineEvent($document, $member, $message);

		$hrBotMessageService = Container::instance()->getHrBotMessageService();
		if (HrBot::isAvailable())
		{
			if ($message->isSecondarySigning() && $member->role === Type\Member\Role::ASSIGNEE)
			{
				$resultSendToChat = $hrBotMessageService->repeatSigningOnErrors($document, $member);
			}
			else
			{
				$resultSendToChat = new Main\Result();
				if (!$this->memberService->skipChatInvitationForMember($member, $document))
				{
					$resultSendToChat = $hrBotMessageService->sendInviteMessage($document, $member, $message->getProvider());
				}
			}

			$this->sendChatMessageDeliveredTimelineEvent($resultSendToChat->isSuccess(), $document, $member, $message);
			if (!$resultSendToChat->isSuccess())
			{
				$result->addErrors($resultSendToChat->getErrors());
			}

			$this->legalLogService->registerChatInviteDelivered($document, $member);
			$this->pullService->sendMemberInvitedToSign($document, $member);
		}
		else
		{
			$result->addError(new Main\Error('Can\'t add message to chat'));
			$this->sendChatMessageDeliveredTimelineEvent(false, $document, $member, $message);
			$this->legalLogService->registerChatInviteNotDelivered($document, $member);
		}
	}

	private function sendChatMessageDeliveredTimelineEvent(bool $delivered, Item\Document $document, Item\Member $member, InviteToSign $message): void
	{
		// delivered event only for employee and assignee
		if ($delivered && !in_array($member->role, [Type\Member\Role::SIGNER, Type\Member\Role::ASSIGNEE], true))
		{
			return;
		}

		// can be delivered by mail/sms
		if (!$delivered && $member->channelType !== Type\Member\ChannelType::IDLE)
		{
			return;
		}

		$eventType = $delivered ? EventData::TYPE_ON_DELIVERED : EventData::TYPE_ON_DELIVERY_ERROR;

		$eventData = new EventData();
		$eventData->setEventType($eventType)
			->setDocumentItem($document)
			->setMemberItem($member)
			->addDataValue($eventData::DATA_KEY_PROVIDER_NAME, $message->getProvider())
			->addDataValue($eventData::DATA_KEY_GOSKEY_ORDER_ID, $message->getProviderExtraData()['orderId'] ?? '')
		;
		Container::instance()->getEventHandlerService()->createTimelineEvent($eventData);
	}

	private function sendChatOnSendTimelineEvent(Item\Document $document, Item\Member $member, InviteToSign $message): void
	{
		$eventType = EventData::TYPE_ON_SEND;
		$eventData = new EventData();
		$eventData->setEventType($eventType)
			->setDocumentItem($document)
			->setMemberItem($member)
			->addDataValue($eventData::DATA_KEY_PROVIDER_NAME, $message->getProvider())
		;
		Container::instance()->getEventHandlerService()->createTimelineEvent($eventData);
	}

	private function processMemberStatusChanged(MemberStatusChanged $message): Main\Result
	{
		$document = $this->documentRepository->getByUid($message->getDocumentUid());
		if ($document === null)
		{
			return (new Main\Result())->addError(new Main\Error('Document does not exist'));
		}

		$member = $this->memberRepository->getByUid($message->getMemberUid());
		if ($member === null || $member->documentId !== $document->id)
		{
			return (new Main\Result())->addError(new Main\Error('Member does not exist'));
		}

		return (new SyncMemberStatus($member, $document, $message))->launch();
	}
}
