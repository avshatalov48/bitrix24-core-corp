<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Contact;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Common\Logo;
use Bitrix\Main\Localization\Loc;

Container::getInstance()->getLocalization()->loadMessages();

final class SignDocument extends Activity
{
	private ?\Bitrix\Crm\Item $document = null;
	private ?\Bitrix\Sign\Document $signDocument = null;

	protected function getActivityTypeId(): string
	{
		return 'SignDocument';
	}

	public function getIconCode(): ?string
	{
		return Icon::DOCUMENT;
	}

	public function getBackgroundColorToken(): string
	{
		return Layout\Icon::BACKGROUND_PRIMARY_ALT;
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

		if ($document)
		{
			$clientCount = 1;
			/** @var Contact $contact */
			foreach ($document->getContacts() as $contact)
			{
				$blocks['client' . $clientCount] =
					(new Layout\Body\ContentBlock\ContentBlockWithTitle())
						->setTitle(
							Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_CONTER_AGENT')
						)
						->setContentBlock(
							(new Layout\Body\ContentBlock\Text())
								->setValue($contact->getFormattedName())
						)
				;

				$clientCount++;
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

		if ($signDocument && $signDocument->canBeChanged())
		{
			$buttons['edit'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_MODIFY'),
				Layout\Footer\Button::TYPE_SECONDARY,
			))->setAction(
				(new Layout\Action\JsEvent($this->getType() . ':Modify'))
					->addActionParamInt('documentId', $this->getDocumentId())
			);
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

	private function getEntityTypeId(): int
	{
		return (int)$this->getAssociatedEntityModel()->get('OWNER_TYPE_ID');
	}

	private function getDocument(): ?\Bitrix\Crm\Item
	{
		if (!$this->document)
		{
			$factory = Container::getInstance()->getFactory($this->getEntityTypeId());
			if (!$factory)
			{
				return null;
			}

			$documentId = $this->getDocumentId();

			$this->document = $factory->getItem($documentId);
		}

		return $this->document;
	}

	private function getMyCompanyCaption(): string
	{
		//todo validate if this way of getting requisites is correct
		$link = EntityLink::getByEntity($this->getEntityTypeId(), $this->getDocumentId());
		if ($link)
		{
			$requisiteId = $link['MC_REQUISITE_ID'] ?? null;
			$linkedRequisiteId = ((int)$requisiteId > 0) ? (int)$requisiteId : null;
		}

		$document = $this->getDocument();
		if (!empty($linkedRequisiteId))
		{
			$requisites = EntityRequisite::getSingleInstance()->getById($linkedRequisiteId);
		}
		elseif ($document && isset($document->getData()['MYCOMPANY_ID']) && $document->getMycompanyId() > 0)
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

	private function getSignDocument(): ?\Bitrix\Sign\Document
	{
		if (!$this->signDocument)
		{
			$this->signDocument = \Bitrix\Sign\Document::resolveByEntity('SMART', $this->getDocumentId());
		}

		return $this->signDocument;
	}
}
