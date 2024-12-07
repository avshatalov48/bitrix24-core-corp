<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Item\Member;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\Sign\MemberService;
use Bitrix\Sign\Type\Member\Role;
use Bitrix\Sign\Type\ProviderCode;

class SignB2eDocument extends LogMessage
{
	protected ?DocumentData $documentData = null;
	protected ?MessageData $messageData = null;
	protected ?\Bitrix\Sign\Item\Document $signDocument = null;
	private ?\Bitrix\Sign\Item\Member $member = null;
	private ?MemberService $memberService = null;

	public function __construct(Context $context, Model $model)
	{
		if (Loader::includeModule('sign'))
		{
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

	public function getType(): string
	{
		return 'SignB2eDocumentLog';
	}

	public static function isActive(): bool
	{
		return \Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled();
	}

	public function getIconCode(): ?string
	{
		return match ($this->model->getTypeCategoryId())
		{
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CREATED => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_STOPPED => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_DONE => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_EMPLOYEE => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_REVIEWER => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_EDITOR => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_RESPONSIBILITY_PERSON => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_EMPLOYEE => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_REVIEWER => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_EDITOR => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_DELIVERED => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNED_DELIVERED => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_DELIVERY_ERROR => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_SENT => $this->getChannelIcon(),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNING_EXPIRED => Icon::ATTENTION,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SNILS_ERROR => Icon::ATTENTION,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNING_ERROR => Icon::ATTENTION,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_STOPPED_BY_ASSIGNEE => $this->getChannelIcon(),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_STOPPED_BY_REVIEWER => $this->getChannelIcon(),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_STOPPED_BY_EDITOR => $this->getChannelIcon(),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CONFIGURATION_ERROR => Icon::ATTENTION,
			default => Icon::INFO,
		};
	}

	public function getTitle(): ?string
	{
		return match ($this->model->getTypeCategoryId())
		{
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CREATED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_CREATE_TITLE_MSG_1'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_STOPPED,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_RESPONSIBILITY_PERSON,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_REVIEWER,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_EDITOR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_STOPPED_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_DONE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DONE_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_RESPONSIBILITY_SIGN_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_EMPLOYEE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EMPLOYEE_SIGN_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_REVIEWER => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_REVIEWER_SIGN_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_EDITOR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EDITOR_SIGN_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_EMPLOYEE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EMPLOYEE_CANCEL_TITLE_MSG_1'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_SENT => $this->getMessageSentTitle(),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_DELIVERED => $this->getMessageDelivered(),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNED_DELIVERED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EMPLOYEE_SIGN_DELIVERED_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_DELIVERY_ERROR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_ERROR'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNING_EXPIRED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_MEMBER_SIGNING_EXPIRED'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SNILS_ERROR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_MEMBER_SNILS_ERROR'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNING_ERROR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_MEMBER_SIGNING_ERROR'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_STOPPED_BY_ASSIGNEE,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_STOPPED_BY_REVIEWER,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_STOPPED_BY_EDITOR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EMPLOYEE_STOPPED'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CONFIGURATION_ERROR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_CONFIGURE_ERROR_TITLE'),
			default => null,
		};
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];

		$document = $this->loadSignDocument();
		if (!$document)
		{
			return $blocks;
		}

		if ($this->model->getTypeCategoryId() === Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CONFIGURATION_ERROR)
		{
			return array_filter([
				'error' => $this->getErrorContentBlock()
			]);
		}

		if ($this->isNeedDocumentNameBlock())
		{
			$blocks['doc'] = $this->getDocumentTitleBlock();
		}

		if ($this->model->getTypeCategoryId() === Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON)
		{
			$blocks['responsible'] = $this->getResponsiblePersonBlock($document->representativeId);
		}

		$member = $this->getMember();
		if ($member)
		{
			$blocks['employee'] = match ($member->role)
			{
				Role::ASSIGNEE => $this->getResponsiblePersonBlock($document->representativeId),
				Role::SIGNER => $this->getEmployeePersonBlock($member->entityId),
				Role::REVIEWER => $this->getReviewerBlock($member->entityId),
				Role::EDITOR => $this->getEditorBlock($member->entityId),
			};
			$blocks += $this->getSesSignBlocks($member->entityId);
		}

		$goskeyOrderId = $this->loadMessageData()?->getGoskeyOrderId();
		if ($goskeyOrderId !== null)
		{
			$blocks['provider'] = $this->getProviderChannelBlock(
				Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_CHANNEL_GOSKEY')
			);
			$blocks['goskeyOrderId'] = $this->getGoskeyOrderIdBlock($goskeyOrderId);
		}

		if ($this->isNeedChannel())
		{
			$blocks['channel'] = $this->getMessageChannelBlock();
		}
		if ($this->loadMessageData()?->getProviderName() && $this->isNeedProvider())
		{
			$blocks['provider'] = $this->getProviderChannelBlock(
				$this->getProviderNameByCode($this->loadMessageData()?->getProviderName())
			);
		}

		if ($this->loadDocumentData()->getInitiatorUserId() && $this->isStoppedByInitiator())
		{
			$blocks['initiator'] = $this->getStopInitiatorBlock();
		}

		return array_filter($blocks);
	}

	private function getGoskeyOrderIdBlock(string $goskeyOrderId): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_GOSKEY_ORDER_ID'))
			->setContentBlock(
				(new Layout\Body\ContentBlock\Text())
					->setValue($goskeyOrderId)
			)
		;
	}

	private function getDocumentTitleBlock(): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_BLOCK_NAME_TITLE'))
			->setFixedWidth(false)
			->setContentBlock(
				(new Layout\Body\ContentBlock\Text())
					->setValue($this->getAssociatedEntityModel()->get('TITLE'))
			)
		;
	}

	protected function loadDocumentData(): DocumentData
	{
		if (!$this->documentData)
		{
			$this->documentData = DocumentData::createFromArray(
				$this->getHistoryItemModel()->get(
					Timeline\HistoryDataModel\Presenter\SignDocument::DOCUMENT_DATA_KEY
				)
			);
		}

		return $this->documentData;
	}

	protected function loadSignDocument(): ?\Bitrix\Sign\Item\Document
	{
		if (!$this->documentData)
		{
			$this->loadDocumentData();
		}

		if (!$this->signDocument)
		{
			$this->signDocument = Container::instance()->getDocumentRepository()->getById($this->documentData->getDocumentId());
		}

		return $this->signDocument;
	}

	protected function loadMessageData(): ?MessageData
	{
		if (!$this->messageData)
		{
			$data = $this->getHistoryItemModel()->get(
				Timeline\HistoryDataModel\Presenter\SignDocument::MESSAGE_DATA_KEY
			);

			if (!empty($data))
			{
				$this->messageData = MessageData::createFromArray($data);
			}
		}

		return $this->messageData;
	}

	private function getEditorBlock(int $userId): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setFixedWidth(false)
			->setTitle($this->getEditorBlockTitle())
			->setContentBlock($this->getUserNameLink($userId))
		;
	}

	private function getReviewerBlock(int $userId): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setFixedWidth(false)
			->setTitle($this->getReviewerBlockTitle())
			->setContentBlock($this->getUserNameLink($userId))
		;
	}

	private function getResponsiblePersonBlock(int $userId): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setFixedWidth(false)
			->setTitle($this->getResponsibleBlockTitle())
			->setContentBlock($this->getUserNameLink($userId))
		;
	}

	private function getEmployeePersonBlock(int $userId): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setFixedWidth(false)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EMPLOYEE'))
			->setContentBlock($this->getUserNameLink($userId))
		;
	}

	private function getUserNameLink(int $userId): Layout\Body\ContentBlock\Link
	{
		$userData = $this->getUserData($userId);
		$name = $this->memberService !== null
			? $this->memberService->getUserRepresentedName($userId)
			: $userData['FORMATTED_NAME'] ?? null
		;

		return (new Layout\Body\ContentBlock\Link())
			->setValue($name)
			->setAction(new Redirect(new Uri($userData['SHOW_URL'] ?? '')))
		;
	}

	private function getProviderChannelBlock(?string $channelName): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return
			(new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setFixedWidth(false)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_CHANNEL_MSG_1'))
			->setContentBlock(
				(new Layout\Body\ContentBlock\Text())
					->setValue($channelName)
			)
		;
	}

	private function getSesSignBlock(string $signature): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return
			(new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setInline()
				->setFixedWidth(false)
				->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_BLOCK_SES_SIGN_TITLE'))
				->setContentBlock(
					(new Layout\Body\ContentBlock\Text())
						->setValue($signature)
				)
		;
	}

	private function getSesSignBuildBlock(string $username, int $userId): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		$title = Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_BLOCK_SES_BUILD_TITLE');
		$userData = $this->getUserData($userId);
		$url = $userData['SHOW_URL'] ?? '';
		$usernameWithLink = $this->isUrl($url) ? '<a href="' . $url . '">' . $username . '</a>' : $url;
		$description = Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_BLOCK_SES_BUILD_DESCRIPTION', [
			'#USERNAME#' => $usernameWithLink,
		]);

		return
			(new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setInline()
				->setFixedWidth(false)
				->setTitle($title)
				->setContentBlock(Layout\Body\ContentBlock\ContentBlockFactory::createFromHtmlString($description))
		;
	}

	private function isUrl(string $url): bool
	{
		return preg_match('#^(?:/|https?://)#', $url);
	}

	/**
	 * @return array<string, Layout\Body\ContentBlock\ContentBlockWithTitle>
	 */
	private function getSesSignBlocks(int $userId): array
	{
		if (!$this->loadMessageData() || !$this->messageData->getSesUsername())
		{
			return [];
		}

		$channelName = Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_CHANNEL_SES');

		return [
			'provider' => $this->getProviderChannelBlock($channelName),
			'sesSignBuild' => $this->getSesSignBuildBlock($this->messageData->getSesUsername(), $userId),
		];
	}

	private function getMember(): ?Member
	{
		if (!isset($this->member))
		{
			$document = $this->loadSignDocument();
			if (!$document)
			{
				return null;
			}
			$memberHash = $this->documentData?->getMemberHash();
			if (!$memberHash)
			{
				return null;
			}

			$this->member = Container::instance()->getMemberRepository()->getByUid($memberHash);
		}

		return $this->member;
	}

	private function getMessageSentTitle(): ?string
	{
		return match ($this->getMember()?->role)
		{
			Role::ASSIGNEE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_TO_ASSIGNEE'),
			Role::SIGNER => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_TO_SIGNER'),
			Role::REVIEWER => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_TO_REVIEWER'),
			Role::EDITOR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_TO_EDITOR'),
			default => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_TITLE'),
		};
	}

	private function getMessageChannelBlock(): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		$value = match ($this->loadMessageData()?->getChannel()?->getType())
		{
			Timeline\SignDocument\Channel::TYPE_EMAIL => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_CHANNEL_EMAIL'),
			Timeline\SignDocument\Channel::TYPE_SMS => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_CHANNEL_SMS'),
			Timeline\SignDocument\Channel::TYPE_B24 => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_CHANNEL_B24'),
			default => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_CHANNEL_CHAT'),
		};

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setFixedWidth(false)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_CHANNEL_TITLE'))
			->setContentBlock(
				(new Layout\Body\ContentBlock\Text())
					->setValue($value)
			)
		;
	}

	private function getMessageDelivered(): ?string
	{
		if ($this->getMember()->role === Role::ASSIGNEE)
		{
			return Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERED_ASSIGNEE');
		}

		return Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERED');
	}

	private function isNeedChannel(): bool
	{
		return match ($this->model->getTypeCategoryId())
		{
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_SENT => true,
			default => false,
		};
	}

	private function isNeedProvider(): bool
	{
		return $this->getMember()?->role === Role::SIGNER;
	}

	private function getProviderNameByCode(string $providerCode): ?string
	{
		return match ($providerCode)
		{
			ProviderCode::SES_RU => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_CHANNEL_SES'),
			ProviderCode::SES_COM => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_CHANNEL_SES_COM'),
			ProviderCode::GOS_KEY => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_CHANNEL_GOSKEY'),
			default => $providerCode,
		};
	}

	private function getReviewerBlockTitle(): ?string
	{
		return match ($this->model->getTypeCategoryId())
		{
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_REVIEWER => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_REVIEWER_DONE'),
			default => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_REVIEWER'),
		};
	}

	private function getEditorBlockTitle(): ?string
	{
		return match ($this->model->getTypeCategoryId())
		{
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_EDITOR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EDITOR_DONE'),
			default => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EDITOR_MSG_1'),
		};
	}

	private function getResponsibleBlockTitle(): ?string
	{
		return match ($this->model->getTypeCategoryId())
		{
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_RESPONSIBILITY_DONE'),
			default => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_RESPONSIBILITY_MSG_1'),
		};
	}

	private function isNeedDocumentNameBlock(): bool
	{
		return match ($this->model->getTypeCategoryId())
		{
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CREATED,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_DONE => true,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_STOPPED => empty($this->loadDocumentData()->getInitiatorUserId()),
			default => false,
		};
	}

	private function isStoppedByInitiator(): bool
	{
		return in_array($this->model->getTypeCategoryId(), [
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_STOPPED,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_STOPPED_BY_ASSIGNEE,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_STOPPED_BY_REVIEWER,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_STOPPED_BY_EDITOR,
		], true);
	}

	private function getStopInitiatorBlock(): ?Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		$userId = $this->loadDocumentData()->getInitiatorUserId();
		if (!$userId)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setFixedWidth(false)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EMPLOYEE_STOPPED_INITIATOR_TITLE'))
			->setContentBlock($this->getUserNameLink($userId))
		;
	}

	private function getChannelIcon(): string
	{
		return match ($this->loadMessageData()?->getChannel()?->getType())
		{
			Timeline\SignDocument\Channel::TYPE_EMAIL => Icon::MAIL_OUTCOME,
			Timeline\SignDocument\Channel::TYPE_SMS => Icon::SMS,
			default => Icon::IM,
		};
	}

	private function getErrorContentBlock(): ?Layout\Body\ContentBlock
	{
		$messageData = $this->loadMessageData();
		if (!$messageData || empty($messageData->getError()?->getMessage()))
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\Text())
			->setValue($messageData->getError()?->getMessage())
			->setIsMultiline()
		;
	}

}
