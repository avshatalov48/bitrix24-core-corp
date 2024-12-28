<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\FeatureResolver;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Item\MemberCollection;
use Bitrix\Sign\Repository\DocumentRepository;
use Bitrix\Sign\Repository\MemberRepository;
use Bitrix\Sign\Service\Sign\DocumentService;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Document\EntityType;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Type\Integration\Im\DocumentChatType;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\MemberStatus;

final class SignB2eDocument extends Activity
{

	private ?\Bitrix\Crm\Item $document = null;
	private ?\Bitrix\Sign\Item\Document $signDocument = null;
	private ?DocumentRepository $documentRepository = null;
	private ?MemberRepository $memberRepository = null;
	private ?MemberCollection $members = null;
	private DocumentService $documentService;
	private ?MemberService $memberService = null;
	private ?bool $isSignDocumentFill = null;
	private ?bool $isSignDocumentReview = null;
	private ?array $signersByStatuses = null;

	private const WAIT_DOCUMENT_CHAT = 1;
	private const READY_DOCUMENT_CHAT = 2;
	private const STOPPED_DOCUMENT_CHAT = 3;

	private const MAX_USER_IN_LINE = 3;
	private ?Member $companyMember = null;

	public function __construct(Context $context, Model $model)
	{
		if (Loader::includeModule('sign'))
		{
			$this->documentRepository = \Bitrix\Sign\Service\Container::instance()->getDocumentRepository();
			$this->memberRepository = \Bitrix\Sign\Service\Container::instance()->getMemberRepository();
			$this->documentService = \Bitrix\Sign\Service\Container::instance()->getDocumentService();

			if (method_exists(
				'\Bitrix\Sign\Service\Cache\Memory\Sign\MemberService',
				'getUserRepresentedName'
			))
			{
				$this->memberService = new \Bitrix\Sign\Service\Cache\Memory\Sign\MemberService();
			}
		}

		parent::__construct($context, $model);
	}

	protected function getActivityTypeId(): string
	{
		return 'SignB2eDocument';
	}

	public function getIconCode(): ?string
	{
		return Icon::DOCUMENT;
	}

	public function getTitle(): ?string
	{
		if ($this->isSignDocumentDone())
		{
			return Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_TITLE_DONE');
		}

		if ($this->isSignDocumentStopped())
		{
			return Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_TITLE_STOPPED');
		}

		return $this->getModel()->isScheduled()
		? Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT')
		: Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_CLOSED')
		;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$action = $this->getShowSigningProcessAction();
		$logo = Layout\Common\Logo::getInstance($this->getLogoCode())->createLogo();
		if ($this->isSignDocumentDraft() || $this->isSignDocumentSending())
		{
			$logo
				->setIconType(Layout\Body\Logo::ICON_TYPE_SECONDARY)
				->setAdditionalIconCode(Layout\Body\Logo::ADDITIONAL_ICON_CODE_PROGRESS)
				->setAdditionalIconType(Layout\Body\Logo::ICON_TYPE_SECONDARY)
			;
		}
		elseif ($this->isSignDocumentDone())
		{
			$logo->setAdditionalIconCode(Layout\Body\Logo::ADDITIONAL_ICON_CODE_DONE);
		}
		elseif ($this->isSignDocumentStopped())
		{
			$logo->setAdditionalIconCode(Layout\Body\Logo::ADDITIONAL_ICON_CODE_UNAVAILABLE);
		}
		elseif ($this->isSignDocumentFill() || $this->isSignDocumentReview())
		{
			$logo->setAdditionalIconCode(Layout\Body\Logo::ADDITIONAL_ICON_CODE_PENCIL);
		}
		elseif ($this->isSignDocumentSigning())
		{
			$logo->setAdditionalIconCode(Layout\Body\Logo::ADDITIONAL_ICON_CODE_SIGN);
		}

		if ($action)
		{
			$logo->setAction($action);
		}

		return $logo;
	}

	private function getShowSigningProcessAction(): ?Layout\Action
	{
		if (!\Bitrix\Crm\Activity\Provider\SignB2eDocument::isActive())
		{
			return null;
		}

		$signDocument = $this->getSignDocument();
		if (!$signDocument)
		{
			return null;
		}

		$uri = new Uri('/bitrix/components/bitrix/sign.document.list/slider.php');
		$uri->addParams([
			'site_id' => SITE_ID,
			'sessid' => bitrix_sessid_get(),
			'type' => 'document',
			'entity_id' => $signDocument->entityId,
		]);

		return
			(new Layout\Action\JsEvent($this->getType() . ':ShowSigningProcess'))
				->addActionParamString('processUri', $uri->getUri())
		;
	}

	private function getShowSigningCancelAction(): ?Layout\Action
	{
		$action = null;

		if (\Bitrix\Crm\Activity\Provider\SignB2eDocument::isActive())
		{
			$signDocument = $this->getSignDocument();
			$action = new Layout\Action\JsEvent($this->getType() . ':ShowSigningCancel');
			$action->addActionParamString('documentUid', $signDocument->uid)
				->addActionParamString('buttonId', 'signingCancel')
			;
		}

		return $action;
	}

	private function getDocumentBlock(): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(false)
			->setTitle(Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_DOCUMENT_TITLE'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($this->getDocument()?->getTitle())
			);
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];
		if (!\Bitrix\Crm\Activity\Provider\SignDocument::isActive())
		{
			return [(new ContentBlock\Text())->setValue(Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_NOT_EXISTS'))];
		}

		$blocks['doc'] = $this->getDocumentBlock();

		if (!$this->getSignDocument())
		{
			return $blocks;
		}

		$blocks['company'] = $this->getCompanyBlock();
		$blocks['representative'] = $this->getRepresentativeBlock();

		if ($this->isSignDocumentDone())
		{
			$blocks['signersDone'] = $this->getSignersDoneBlock();
			$blocks['signersCanceled'] = $this->getSignersCanceledBlock();
		}
		elseif ($this->isSignDocumentStopped())
		{
			$blocks['signersDone'] = $this->getSignersDoneBlock();
		}
		elseif ($this->isRepresentativeSigningStage())
		{
			if (!$this->isCompanyDone())
			{
				$blocks['signersWait'] = $this->isFromEmployeeDocument()
					? $this->getFromEmployeeSignersWaitBlock()
					: $this->getSignersWaitBlock()
				;
			}
			$blocks['signersReady'] = $this->getSignersReadyBlock();
			$blocks['signersCanceled'] = $this->getSignersCanceledBlock();
		}
		else
		{
			$blocks['signers'] = $this->getSignersBlock();
		}

		return array_filter($blocks);
	}

	public function getButtons(): ?array
	{
		$signDocument = $this->getSignDocument();
		$buttons = [];

		if (!$signDocument)
		{
			return $buttons;
		}

		if ($this->isSignDocumentSigning() || $this->isSignDocumentStopped())
		{
			$buttons['signingProcess'] = (
				new Layout\Footer\Button(
					(string)Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_SIGNING_PROCESS_MSG_1'),
					$this->isSignDocumentStopped() ? Layout\Footer\Button::TYPE_SECONDARY : Layout\Footer\Button::TYPE_PRIMARY
				))->setProps(['id' => 'signB2e-document-process-button'])
				->setAction($this->getShowSigningProcessAction())
			;
		}

		if ($this->isSignDocumentDraft())
		{
			$action = (new Layout\Action\JsEvent($this->getType() . ':Modify'))
				->addActionParamInt('documentId', $this->getDocumentId())
				->addActionParamString('documentUid', $signDocument->uid)
			;

			$buttons['edit'] = (new Layout\Footer\Button(
				(string)Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_MODIFY_MSG_1'),
				Layout\Footer\Button::TYPE_PRIMARY,
			))->setAction($action);
		}

		if ($this->isSignDocumentInWork() && $this->isSignFeaturePreviewAvailable())
		{
			$buttons['preview'] = (new Layout\Footer\Button(
				(string)Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_PREVIEW'),
				Layout\Footer\Button::TYPE_SECONDARY,
			))->setAction($this->getPreviewAction());
		}

		if ($this->isSignDocumentDone())
		{
			$buttons['signingProcess'] = $this->getDownloadButton();
		}

		return $buttons;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();

		unset($items['delete'], $items['view']);

		if ($this->isSignDocumentSigning()
			&& $this->isSignFeatureSenderStopAvailable()
			&& $this->documentService->isCurrentUserCanEditDocument($this->getSignDocument()))
		{
			$items['cancel'] = (new Layout\Menu\MenuItem((string)Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_SIGNING_CANCEL')))
				->setAction($this->getShowSigningCancelAction())
			;
		}

		return $items;
	}

	private function getDocumentId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID');
	}

	private function getOwnerEntityId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('OWNER_ID');
	}

	private function getOwnerEntityTypeId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('OWNER_TYPE_ID');
	}

	private function getDocument(): ?\Bitrix\Crm\Item
	{
		if (!$this->document)
		{
			$factory = Container::getInstance()->getFactory($this->getOwnerEntityTypeId());
			if (!$factory)
			{
				return null;
			}

			$documentId = $this->getOwnerEntityId();

			$this->document = $factory->getItem($documentId);
		}

		return $this->document;
	}

	private function getSignDocument(): ?\Bitrix\Sign\Item\Document
	{
		if (!$this->signDocument && isset($this->documentRepository))
		{
			$this->signDocument = $this->documentRepository->getByEntityIdAndType(
				$this->getDocumentId(),
				EntityType::SMART_B2E
			);
		}

		return $this->signDocument;
	}

	private function getCompanyBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		$companyName = $this->getCompanyMember()?->companyName;
		if (!$companyName)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setTitle(Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_COMPANY'))
			->setContentBlock(
				(new Layout\Body\ContentBlock\Text())
					->setValue($companyName)
			)
		;
	}

	private function getUserProfileLink(int $userId, bool $withDelimiter = false): Layout\Body\ContentBlock\Link
	{
		$user = $this->getUserData($userId);
		$name = $this->memberService !== null
			? $this->memberService->getUserRepresentedName($userId)
			: $user['FORMATTED_NAME'] ?? ''
		;

		return (new Layout\Body\ContentBlock\Link())
			->setValue($withDelimiter ? $name . ',' : $name)
			->setAction(new Redirect(new Uri($user['SHOW_URL'] ?? '')))
		;
	}

	/**
	 * @param value-of<DocumentChatType> $chatType
	 */
	private function getDocumentChatButton(int $chatType): ContentBlock\LineOfTextBlocksButton
	{
		$action = (new Layout\Action\JsEvent($this->getType() . ':CreateDocumentChat'))
			->addActionParamInt('chatType', $chatType)
			->addActionParamInt('documentId', $this->signDocument->getId())
		;

		return (new Layout\Body\ContentBlock\LineOfTextBlocksButton())
			->setAction($action)
			->setIcon('chat')
			->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_CREATE_CHAT'))
		;
	}

	private function getUserMoreLink(int $moreUserCount): Layout\Body\ContentBlock\Link
	{
		$text = Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_MORE_USERS', [
			'#USER_COUNT#' => $moreUserCount,
		]);

		return (new Layout\Body\ContentBlock\Link())
			->setValue($text)
			->setAction($this->getShowSigningProcessAction())
		;
	}

	/**
	 * @return MemberCollection
	 * @deprecated only for old sign module version
	 */
	private function getMembers(): MemberCollection
	{
		if (isset($this->members))
		{
			return $this->members;
		}

		$document = $this->getSignDocument();

		$this->members = $document
			? $this->memberRepository->listByDocumentId($document->id)
			: new MemberCollection()
		;

		return $this->members;
	}

	public function getCompleteButton(): ?Layout\Header\ChangeStreamButton
	{
		return null;
	}

	/**
	 * Get code of icon background color
	 * @return string
	 */
	public function getBackgroundColorToken(): string
	{
		return $this->isSignDocumentInWork() ? Layout\Icon::BACKGROUND_PRIMARY_ALT : Layout\Icon::BACKGROUND_PRIMARY;
	}

	private function isSignDocumentInWork(): bool
	{
		return $this->getSignDocument()
			&& !in_array($this->getSignDocument()->status, [DocumentStatus::DONE, DocumentStatus::STOPPED], true);
	}

	private function isSignDocumentDone(): bool
	{
		return $this->getSignDocument()?->status === DocumentStatus::DONE;
	}

	private function isSignDocumentStopped(): bool
	{
		return $this->getSignDocument()?->status === DocumentStatus::STOPPED;
	}

	private function isSignDocumentDraft(): bool
	{
		return in_array($this->getSignDocument()?->status, [
				DocumentStatus::NEW,
				DocumentStatus::UPLOADED,
			], true);
	}

	private function isSignDocumentSending(): bool
	{
		return $this->getSignDocument()?->status === DocumentStatus::READY;
	}

	private function isSignDocumentFill(): bool
	{
		if (!isset($this->isSignDocumentFill))
		{
			$this->isSignDocumentFill = $this->isSignDocumentSigning()
				&& $this->isFirstUserWithRoleReady(Role::EDITOR);
		}

		return (bool)$this->isSignDocumentFill;
	}

	private function isSignDocumentReview(): bool
	{
		if (!isset($this->isSignDocumentReview))
		{
			$this->isSignDocumentReview = $this->isSignDocumentSigning()
				&& $this->isFirstUserWithRoleReady(Role::REVIEWER);
		}

		return (bool)$this->isSignDocumentReview;
	}

	private function isFirstUserWithRoleReady(string $role): bool
	{
		$members = $this->memberRepository->listByDocumentIdWithRole($this->getSignDocumentId(), $role);
		foreach ($members as $member)
		{
			if ($member->role === $role)
			{
				return $member->status === MemberStatus::READY;
			}
		}

		return false;
	}

	private function isSignDocumentSigning(): bool
	{
		return $this->getSignDocument()?->status === DocumentStatus::SIGNING;
	}

	public function getTags(): ?array
	{
		if (!$this->getSignDocument())
		{
			return null;
		}

		return [
			'status' => $this->getStatusTag(),
		];
	}

	private function getStatusTag(): Layout\Header\Tag
	{
		return new Layout\Header\Tag($this->getStatusTagTitle(), $this->getStatusTagType());
	}

	private function getStatusTagTitle(): string
	{
		if ($this->isSignDocumentDraft())
		{
			return (string)Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_STATUS_DRAFT');
		}

		if ($this->isSignDocumentSending())
		{
			return (string)Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_STATUS_SENDING');
		}

		if ($this->isSignDocumentStopped())
		{
			return (string)Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_STATUS_STOPPED');
		}

		if ($this->isSignDocumentDone())
		{
			return (string)Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_STATUS_DONE');
		}

		if ($this->isSignDocumentFill())
		{
			return (string)Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_STATUS_FILL');
		}

		if ($this->isSignDocumentReview())
		{
			return (string)Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_STATUS_REVIEW');
		}

		return (string)Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_STATUS_SIGNING');
	}

	private function getStatusTagType(): string
	{
		return $this->isSignDocumentDone() ? Layout\Header\Tag::TYPE_SUCCESS : Layout\Header\Tag::TYPE_SECONDARY;
	}

	private function getLogoCode(): string
	{
		if ($this->isSignDocumentDone())
		{
			return Layout\Common\Logo::DOCUMENT_SIGNED;
		}

		if ($this->isSignDocumentDraft() || $this->isSignDocumentSending())
		{
			return Layout\Common\Logo::DOCUMENT_DRAFT;
		}

		return Layout\Common\Logo::DOCUMENT;
	}

	private function getCompanyMember(): ?Member
	{
		if (!isset($this->companyMember))
		{
			$this->companyMember = $this->memberRepository
				->listByDocumentIdWithRole($this->getSignDocument()->id, Role::ASSIGNEE, 1)
				->getFirst()
			;
		}

		return $this->companyMember;
	}

	/**
	 * @param value-of<DocumentChatType>|null $chatType
	 */
	private function getUsersBlockWithCount(
		?string $title,
		array $userIds,
		int $userCount,
		?int $chatType = null,
	): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		if (empty($userIds))
		{
			return null;
		}

		$lineOfTextBlocks = new Layout\Body\ContentBlock\LineOfTextBlocks();
		$num = 0;
		foreach ($userIds as $userId)
		{
			$num += 1;
			if ($num === self::MAX_USER_IN_LINE && $userCount > self::MAX_USER_IN_LINE)
			{
				$link = $this->getUserMoreLink($userCount - $num + 1);
				$lineOfTextBlocks->addContentBlock("roleUserMore", $link);

				break;
			}

			$link = $this->getUserProfileLink($userId, $num !== $userCount);
			$lineOfTextBlocks->addContentBlock("roleUser_$userId", $link);
		}
		if (class_exists(FeatureResolver::class))
		{
			$featureResolver = FeatureResolver::instance();
			if ($chatType !== null && $featureResolver->released('createDocumentChat'))
			{
				$link = $this->getDocumentChatButton($chatType);
				$lineOfTextBlocks->setButton($link);
			}
		}

		if ($lineOfTextBlocks->isEmpty())
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setTitle($title)
			->setContentBlock($lineOfTextBlocks)
		;
	}

	private function getSignersWaitBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		[$userIds, $count] = $this->getSignersUserIdsAndCountForBlock([MemberStatus::WAIT]);

		$title = Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_SIGNER_WAIT');

		return $this->getUsersBlockWithCount($title, $userIds, $count, self::WAIT_DOCUMENT_CHAT);
	}

	private function getFromEmployeeSignersWaitBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		[$userIds, $count] = $this->getSignersUserIdsAndCountForBlock([MemberStatus::DONE]);

		$title = Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_SIGNER_WAIT');

		return $this->getUsersBlockWithCount($title, $userIds, $count, self::WAIT_DOCUMENT_CHAT);
	}

	private function getSignersReadyBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		$statuses = method_exists(MemberStatus::class, 'getReadyForSigning')
			? MemberStatus::getReadyForSigning()
			: [MemberStatus::READY];
		[$userIds, $count] = $this->getSignersUserIdsAndCountForBlock($statuses);

		$title = Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_SIGNER_READY');

		return $this->getUsersBlockWithCount($title, $userIds, $count, self::READY_DOCUMENT_CHAT);
	}

	private function getSignersCanceledBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		$statuses = [
			MemberStatus::REFUSED,
			MemberStatus::STOPPED,
		];
		[$userIds, $count] = $this->getSignersUserIdsAndCountForBlock($statuses);

		$title = Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_TITLE_STOPPED');

		return $this->getUsersBlockWithCount($title, $userIds, $count, self::STOPPED_DOCUMENT_CHAT);
	}

	private function getSignersDoneBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		[$userIds, $count] = $this->getSignersUserIdsAndCountForBlock([MemberStatus::DONE]);

		$title = Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_SIGNER_DONE');

		return $this->getUsersBlockWithCount($title, $userIds, $count);
	}

	private function getRepresentativeBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		$userId = $this->getSignDocument()?->representativeId;
		if (empty($userId))
		{
			return null;
		}

		$title = Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_REPRESENTATIVE');

		return $this->getUsersBlockWithCount($title, [$userId], 1);
	}

	private function getSignersBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		[$userIds, $count] = $this->getSignersUserIdsAndCountForBlock();

		$title = Loc::getMessage('CRM_SIGN_B2E_ACTIVITY_FIELD_SIGNER');

		return $this->getUsersBlockWithCount($title, $userIds, $count);
	}

	/**
	 * @deprecated only for old sign module version
	 *
	 * @return array<MemberStatus::*, array<int>>
	 */
	private function getSignersByStatuesUserIds(): array
	{
		if (!isset($this->signersByStatuses))
		{
			foreach ($this->getMembers() as $member)
			{
				if ($member->role === Role::SIGNER)
				{
					$this->signersByStatuses[$member->status][] = $member->entityId;
				}
			}
		}

		return $this->signersByStatuses ?? [];
	}

	private function isRepresentativeSigningStage(): bool
	{
		return $this->isSignDocumentSigning()
			&& !$this->isSignDocumentReview()
			&& !$this->isSignDocumentFill();
	}

	private function getPreviewAction(): Layout\Action
	{
		return (new Layout\Action\JsEvent($this->getType() . ':Preview'))
			->addActionParamInt('documentId', $this->getDocumentId())
		;
	}

	private function getDownloadButton(): Layout\Footer\Button
	{
		$title = (string)Loc::getMessage('CRM_TIMELINE_ACTIVITY_DOWNLOAD');
		$type = Layout\Footer\Button::TYPE_SECONDARY;

		return (new Layout\Footer\Button($title, $type))
			->setAction($this->getShowSigningProcessAction())
		;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function isSignFeaturePreviewAvailable(): bool
	{
		return $this->isSignFeatureSenderStopAvailable();
	}

	private function isSignFeatureSenderStopAvailable(): bool
	{
		return isset($this->documentService)
			&& method_exists($this->documentService, 'isCurrentUserCanEditDocument');
	}

	private function getSignDocumentId(): int
	{
		return (int)$this->getSignDocument()->id;
	}

	/**
	 * @param list<\Bitrix\Sign\Type\MemberStatus::*> $statuses
	 *
	 * @return list{list<int>, int}
	 */
	private function getSignersUserIdsAndCountForBlock(array $statuses = []): array
	{
		if (method_exists($this->memberRepository, 'countMembersByDocumentIdAndRoleAndStatus'))
		{
			$limit = self::MAX_USER_IN_LINE + 1;
			$documentId = $this->getSignDocumentId();
			$members = $this->memberRepository
				->listByDocumentIdAndRoleAndStatus($documentId, Role::SIGNER, $limit, $statuses)
			;
			$userIds = array_map(fn(Member $member): int => $member->entityId, $members->toArray());
			$count = count($userIds);
			if ($count >= $limit)
			{
				$count = $this->memberRepository->countMembersByDocumentIdAndRoleAndStatus($documentId, $statuses);
			}

			return [$userIds, $count];
		}

		$userIds = [];
		foreach ($this->getSignersByStatuesUserIds() as $status => $statusUserIds)
		{
			if (empty($statuses) || in_array($status, $statuses, true))
			{
				$userIds = array_merge($userIds, $statusUserIds);
			}
		}

		return [$userIds, count($userIds)];
	}

	private function isCompanyDone(): bool
	{
		return $this->getCompanyMember()?->status === MemberStatus::DONE;
	}

	private function isFromEmployeeDocument(): bool
	{
		if (!class_exists(\Bitrix\Sign\Type\Document\InitiatedByType::class))
		{
			return false;
		}

		return $this->getSignDocument()?->initiatedByType === \Bitrix\Sign\Type\Document\InitiatedByType::EMPLOYEE;
	}
}
