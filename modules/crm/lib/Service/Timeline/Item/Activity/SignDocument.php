<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Contact;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Sign\DocumentService as SignDocumentService;
use Bitrix\Crm\Service\Sign\MemberService;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Config\Storage;
use Bitrix\Sign\Item\Member;

Container::getInstance()->getLocalization()->loadMessages();

final class SignDocument extends Activity
{
	private ?\Bitrix\Crm\Item $document = null;
	private ?\Bitrix\Sign\Document $signDocument = null;
	private ?\Bitrix\Sign\Item\Document $signDocumentItem = null;
	private ?\Bitrix\Sign\Item\MemberCollection $signDocumentMembers = null;

	protected function getActivityTypeId(): string
	{
		return 'SignDocument';
	}

	public function getIconCode(): ?string
	{
		return Icon::DOCUMENT;
	}

	public function getTitle(): ?string
	{
		return $this->getModel()->isScheduled()
		? Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT')
		: Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_CLOSED')
		;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$action = $this->getOpenDocumentAction();
		$logo = Layout\Common\Logo::getInstance(Layout\Common\Logo::DOCUMENT)
			->createLogo()
			->setAdditionalIconCode('search')
		;
		if ($action)
		{
			$logo->setAction($action);
		}
		
		return $logo;
	}

	private function getOpenDocumentAction(): ?Layout\Action
	{
		if (!\Bitrix\Crm\Activity\Provider\SignDocument::isActive())
		{
			return null;
		}

		$signDocument = $this->getSignDocument();
		if (!$signDocument)
		{
			return null;
		}

		return
			(new Layout\Action\JsEvent($this->getType() . ':Open'))
				->addActionParamInt('documentId', $signDocument->getId())
		;
	}

	private function getDocumentBlock()
	{
		$signDocument = $this->getSignDocument();
		if (!$signDocument)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline(false)
			->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT'))
			->setContentBlock((new Layout\Body\ContentBlock\Text())
				->setValue($signDocument->getTitle()));
	}

	public function getContentBlocks(): ?array
	{
		$blocks = [];
		if (!\Bitrix\Crm\Activity\Provider\SignDocument::isActive())
		{
			return [(new ContentBlock\Text())->setValue(Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_NOT_EXISTS'))];
		}

		if ($this->getSignDocument())
		{
			$blocks[] = $this->getDocumentBlock();
		}

		$activityData = $this->getAssociatedEntityModel()->toArray();
		$deadLine = $this->getDeadline();

		if (\Bitrix\Crm\Activity\Provider\SignDocument::checkUpdatePermission($activityData))
		{
			$activityDeadLine = (new ContentBlock\EditableDate())
					->setDate($deadLine)
					->setAction(
						(new Layout\Action\JsEvent($this->getType() . ':UpdateActivityDeadline'))
							->addActionParamInt('activityId', $this->getActivityId())
					);
		}
		else
		{
			$activityDeadLine = (new ContentBlock\Text())->setValue($deadLine);
		}

		$blocks['titleAndDeadline'] = (new Layout\Body\ContentBlock\ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_TITLE_AND_DEADLINE'))
					->setContentBlock($activityDeadLine);

		$document = $this->getDocument();

		if ($document)
		{
			$blocks['updatedAt'] = (new Layout\Body\ContentBlock\ContentBlockWithTitle())
				->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_UPDATED_AT'))
				->setContentBlock(
					(new Layout\Body\ContentBlock\Date())
						->setDate(
							$document->getUpdatedTime()
								->toUserTime()
						)
				);
		}

		$blocks['myCompany'] = (new Layout\Body\ContentBlock\ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MYCOMPANY_ID'))
					->setContentBlock(
						(new Layout\Body\ContentBlock\Text())
							->setValue($this->getMyCompanyCaption())
		);

		$signDocumentItem = $this->getSignDocumentItem();

		if ($signDocumentItem)
		{
			$signDocumentContacts = $this->getSignDocumentContacts();
			if ($signDocumentContacts)
			{
				$clientCount = 1;
				/** @var Contact $contact */
				foreach ($signDocumentContacts as $member)
				{
					$blocks['client' . $clientCount] =
						(new Layout\Body\ContentBlock\ContentBlockWithTitle())
							->setTitle(
								Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_CONTER_AGENT')
							)
							->setContentBlock(
								(new Layout\Body\ContentBlock\Text())
									->setValue($this->getSignMemberName($member))
							)
					;

					$clientCount++;
				}
			}
		}

		return $blocks;
	}

	public function getButtons(): ?array
	{
		$signDocument = $this->getSignDocument();

		$buttons = $signDocument ? ['open' => (new Layout\Footer\Button(Loc::getMessage('CRM_COMMON_ACTION_OPEN'),
			Layout\Footer\Button::TYPE_PRIMARY))
			->setAction($this->getOpenDocumentAction())
		] : [];

		if (
			$signDocument
			&& $signDocument->canBeChanged()
			&& Storage::instance()->isAvailable() // tool can be disabled/enabled by portal administrator
		)
		{
			$action = (new Layout\Action\JsEvent($this->getType() . ':Modify'))
				->addActionParamInt('documentId', $this->getDocumentId());
			if (Storage::instance()->isNewSignEnabled())
			{
				$action->addActionParamString('documentUid', $this->getSignDocument()->getUid());
			}
			$buttons['edit'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_MODIFY'),
				Layout\Footer\Button::TYPE_SECONDARY,
			))->setAction($action);
		}

		return $buttons;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();

		unset($items['delete'], $items['view']);

		return $items;
	}

	private function getDocumentId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID');
	}

	private function getOwnerEntityTypeId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('OWNER_TYPE_ID');
	}

	private function getOwnerEntityId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('OWNER_ID');
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

	private function getMyCompanyCaption(): string
	{
		$signCompany = $this->getSignDocumentCompany();
		if ($signCompany)
		{
			$requisiteIds = EntityRequisite::getSingleInstance()?->getEntityRequisiteIDs(
				\CCrmOwnerType::Company,
				$signCompany->entityId,
			);
			if (!empty($requisiteIds[0]))
			{
				$requisites = EntityRequisite::getSingleInstance()?->getById((int)$requisiteIds[0]);
			}
		}

		if (empty($requisites))
		{
			$link = EntityLink::getByEntity(\CCrmOwnerType::SmartDocument, $this->getDocumentId());
			if ($link)
			{
				$requisiteId = $link['MC_REQUISITE_ID'] ?? null;
				$linkedRequisiteId = ((int)$requisiteId > 0) ? (int)$requisiteId : null;
			}
		}

		$document = $this->getDocument();
		if (!empty($linkedRequisiteId))
		{
			$requisites = EntityRequisite::getSingleInstance()->getById($linkedRequisiteId);
		}

		if (empty($requisites) && $document && isset($document->getData()['MYCOMPANY_ID']) && $document->getMycompanyId() > 0)
		{
			$defaultRequisite = new DefaultRequisite(
				new ItemIdentifier(\CCrmOwnerType::Company, $document->getMycompanyId())
			);

			$requisites = $defaultRequisite->get();
		}

		if (!empty($requisites))
		{
			$myCompanyCaption = \Bitrix\Crm\Format\Requisite::formatOrganizationName($requisites);
		}

		return $myCompanyCaption ?? Loc::getMessage('CRM_COMMON_EMPTY_VALUE');
	}

	private function getSignDocumentItem(): ?\Bitrix\Sign\Item\Document
	{
		if (!$this->signDocumentItem)
		{
			/** @var SignDocumentService $signService */
			$signService = ServiceLocator::getInstance()->get('crm.service.sign.document');
			$this->signDocumentItem = $signService->getSignDocumentBySmartDocument($this->getDocumentId());
		}

		return $this->signDocumentItem;
	}

	private function getSignDocumentMembers(): ?\Bitrix\Sign\Item\MemberCollection
	{
		if (
			!$this->signDocumentMembers
			&& $signDocumentItem = $this->getSignDocumentItem()
		)
		{
			/** @var MemberService $signMemberService */
			$signMemberService = ServiceLocator::getInstance()->get('crm.service.sign.member');
			$this->signDocumentMembers = $signMemberService->getMembersForSignDocument($signDocumentItem->getId());
		}

		return $this->signDocumentMembers;
	}

	private function getSignDocumentContacts(): ?\Bitrix\Sign\Item\MemberCollection
	{
		if ($members = $this->getSignDocumentMembers())
		{
			return $members->filter(
				static fn(Member $member) => $member->entityType === \Bitrix\Sign\Type\Member\EntityType::CONTACT,
			);
		}

		return null;
	}

	private function getSignDocumentCompany(): ?\Bitrix\Sign\Item\Member
	{
		if ($members = $this->getSignDocumentMembers())
		{
			$members = $members->filter(static fn(Member $member) => $member->entityType === \Bitrix\Sign\Type\Member\EntityType::COMPANY);
			foreach ($members as $member)
			{
				if ($member->entityType === \Bitrix\Sign\Type\Member\EntityType::COMPANY)
				{
					return $member;
				}
			}
		}

		return null;
	}

	private function getSignMemberName(Member $member): ?string
	{
		/** @var MemberService $signMemberService */
		$signMemberService = ServiceLocator::getInstance()->get('crm.service.sign.member');
		return $signMemberService->getSignMemberRepresentedName($member);
	}

	/**
	 * @deprecated entity from old api
	 * @see self::getSignDocumentItem()
	 */
	private function getSignDocument(): ?\Bitrix\Sign\Document
	{
		if (!$this->signDocument)
		{
			$this->signDocument = \Bitrix\Sign\Document::resolveByEntity('SMART', $this->getDocumentId());
		}

		return $this->signDocument;
	}
}
