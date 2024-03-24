<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage;

use Bitrix\Crm\Service\Timeline\Layout\Action\Redirect;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Item\LogMessage;
use Bitrix\Crm\Timeline;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Timeline\SignDocument\DocumentData;
use Bitrix\Crm\Timeline\SignDocument\MessageData;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Member\Role;

class SignB2eDocument extends LogMessage
{
	protected ?DocumentData $documentData = null;
	protected ?MessageData $messageData = null;
	protected ?\Bitrix\Sign\Item\Document $signDocument = null;

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
		$messageData = $this->loadMessageData();
		$type = Timeline\SignDocument\Channel::TYPE_CHAT;
		if ($messageData)
		{
			$type = $messageData->getChannel()->getType();
		}

		$titlesMap = [
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_EMPLOYEE => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_REVIEWER => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_EDITOR => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_RESPONSIBILITY_PERSON => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_EMPLOYEE => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_REVIEWER => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_EDITOR => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_DELIVERED => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_DELIVERY_ERROR => Icon::DOCUMENT,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_SENT =>
				( $type === Timeline\SignDocument\Channel::TYPE_EMAIL
					? Icon::MAIL_OUTCOME
					: ($type === Timeline\SignDocument\Channel::TYPE_SMS ? Icon::SMS : Icon::IM)
				),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNING_EXPIRED => Icon::ATTENTION,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SNILS_ERROR => Icon::ATTENTION,
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNING_ERROR => Icon::ATTENTION,
		];
		return $titlesMap[$this->model->getTypeCategoryId()] ?? Icon::INFO;
	}

	public function getTitle(): ?string
	{
		$titlesMap = [
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CREATED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_CREATE_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_RESPONSIBILITY_SIGN_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_EMPLOYEE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EMPLOYEE_SIGN_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_REVIEWER => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_REVIEWER_SIGN_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_EDITOR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EDITOR_SIGN_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_RESPONSIBILITY_PERSON => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_RESPONSIBILITY_CANCEL_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_EMPLOYEE => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EMPLOYEE_CANCEL_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_REVIEWER => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_REVIEWER_CANCEL_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_CANCELED_BY_EDITOR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EDITOR_CANCEL_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_SENT => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SENT_TITLE'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_DELIVERED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERED'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_DELIVERY_ERROR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_ERROR'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNING_EXPIRED => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_MEMBER_SIGNING_EXPIRED'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SNILS_ERROR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_MEMBER_SNILS_ERROR'),
			Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MEMBER_SIGNING_ERROR => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_MEMBER_SIGNING_ERROR'),
		];
		$messageData = $this->loadMessageData();

		if ($messageData)
		{
			$type = $messageData->getChannel()->getType();
			$titlesMap[Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_MESSAGE_SENT] = match ($type)
			{
				Timeline\SignDocument\Channel::TYPE_EMAIL => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MAIL_SEND_TITLE'),
				Timeline\SignDocument\Channel::TYPE_SMS => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_SMS_SEND_TITLE'),
				default => Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_MESSAGE_SEND_TITLE'),
			};
		}

		return $titlesMap[$this->model->getTypeCategoryId()] ?? null;
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];

		$document = $this->loadSignDocument();
		if (!$document)
		{
			return $blocks;
		}

		if ($this->model->getTypeCategoryId() === Timeline\SignB2eDocument\Entry::TYPE_CATEGORY_SIGNED_BY_RESPONSIBILITY_PERSON)
		{
			$blocks['responsible'] = $this->getResponsiblePersonBlock($document->representativeId);
		}

		$memberHash = $this->documentData?->getMemberHash();
		if ($memberHash)
		{
			$member = Container::instance()->getMemberRepository()->getByUid($memberHash);

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
		}

		$goskeyOrderId = $this->messageData?->getGoskeyOrderId();
		if ($goskeyOrderId !== null)
		{
			$blocks['providerGoskey'] = $this->getProviderChannelBlock(
				Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_CHANNEL_GOSKEY')
			);
			$blocks['goskeyOrderId'] = $this->getGoskeyOrderIdBlock($goskeyOrderId);
		}

		return $blocks;
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

	private function getChannelContentBlock(): ?Layout\Body\ContentBlock
	{
		$messageData = $this->loadMessageData();
		if (!$messageData)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNDOCUMENT_BLOCK_CHANNEL_TITLE'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($messageData->getChannel()->getType())
				->setColor('whatsapp')
				->setIsBold(true)
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
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EDITOR'))
			->setContentBlock($this->getUserNameLink($userId));
	}

	private function getReviewerBlock(int $userId): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_REVIEWER'))
			->setContentBlock($this->getUserNameLink($userId));
	}

	private function getResponsiblePersonBlock(int $userId): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_RESPONSIBILITY'))
			->setContentBlock($this->getUserNameLink($userId));
	}

	private function getEmployeePersonBlock(int $userId): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(true)
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_EMPLOYEE'))
			->setContentBlock($this->getUserNameLink($userId));
	}

	private function getUserNameLink(int $userId): Layout\Body\ContentBlock\Link
	{
		$userData = $this->getUserData($userId);

		return (new Layout\Body\ContentBlock\Link())
			->setValue($userData['FORMATTED_NAME'] ?? null)
			->setAction(new Redirect(new Uri($userData['SHOW_URL'] ?? '')))
		;
	}

	private function getProviderChannelBlock(string $channelName): Layout\Body\ContentBlock\ContentBlockWithTitle
	{
		return
			(new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_CHANNEL'))
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
		if (!$this->messageData || !$this->messageData->getSesUsername() || !$this->messageData->getSesSign())
		{
			return [];
		}

		$channelName = Loc::getMessage('CRM_SERVICE_TIMELINE_LAYOUT_SIGNB2EDOCUMENT_DOCUMENT_DELIVERY_CHANNEL_CHAT');

		return [
			'providerSes' => $this->getProviderChannelBlock($channelName),
			'sesSign' => $this->getSesSignBlock($this->messageData->getSesSign()),
			'sesSignBuild' => $this->getSesSignBuildBlock($this->messageData->getSesUsername(), $userId),
		];
	}

}
