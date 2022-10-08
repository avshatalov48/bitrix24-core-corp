<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Contact;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Requisite\DefaultRequisite;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;

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
		return 'document';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_CREATED_AT');
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$action = $this->getOpenDocumentAction();
		$logo = (new Layout\Body\Logo('document'));
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
		$deadLine = $this->model->getDate();

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

		$blocks['updatedAt'] = (new Layout\Body\ContentBlock\ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_UPDATED_AT'))
					->setContentBlock(
						(new Layout\Body\ContentBlock\Text())
							->setValue($this->getDocument()->getUpdatedTime()));

		$blocks['myCompany'] = (new Layout\Body\ContentBlock\ContentBlockWithTitle())
					->setTitle(Loc::getMessage('CRM_TYPE_ITEM_FIELD_MYCOMPANY_ID'))
					->setContentBlock(
						(new Layout\Body\ContentBlock\Text())
							->setValue($this->getMyCompanyCaption())
		);


		$clientCount = 1;
		/** @var Contact $contact */
		foreach ($this->getDocument()->getContacts() as $contact)
		{
			$blocks['client' . $clientCount] =
				(new Layout\Body\ContentBlock\ContentBlockWithTitle())
					->setTitle(
						Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_CLIENT_WITH_COUNT', ['#CLIENT_COUNT#' => $clientCount])
					)
					->setContentBlock(
						(new Layout\Body\ContentBlock\Text())
							->setValue($contact->getFormattedName())
					)
			;

			$clientCount++;
		}

		return $blocks;
	}

	public function getButtons(): ?array
	{
		$buttons = [];
		$buttons['open'] = (new Layout\Footer\Button(Loc::getMessage('CRM_COMMON_ACTION_OPEN'),
					Layout\Footer\Button::TYPE_PRIMARY))
					->setAction($this->getOpenDocumentAction());

		$signDocument = $this->getSignDocument();
		if ($signDocument && $signDocument->canBeChanged())
		{
			$buttons['edit'] = (new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ACTIVITY_SIGN_DOCUMENT_MODIFY'),
				Layout\Footer\Button::TYPE_SECONDARY,
			))->setAction(
				(new Layout\Action\JsEvent($this->getType() . ':Modify'))
					->addActionParamInt('documentId', $this->getSignDocument()->getId())
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

	private function getDocument(): \Bitrix\Crm\Item
	{
		if (!$this->document)
		{
			$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartDocument);
			if (!$factory)
			{
				throw new ObjectNotFoundException(
					'Factory for ' . \CCrmOwnerType::ResolveName(\CCrmOwnerType::SmartDocument) . ' was not found'
				);
			}

			$documentId = $this->getDocumentId();

			$this->document = $factory->getItem($documentId);
			if (!$this->document)
			{
				$identifier = new ItemIdentifier(\CCrmOwnerType::SmartDocument, $documentId);

				throw new ObjectNotFoundException('Item was not found: ' . $identifier);
			}
		}

		return $this->document;
	}

	private function getMyCompanyCaption(): string
	{
		//todo validate if this way of getting requisites is correct
		$link = EntityLink::getByEntity(\CCrmOwnerType::SmartDocument, $this->getDocumentId());
		if ($link)
		{
			$requisiteId = $link['MC_REQUISITE_ID'] ?? null;
			$linkedRequisiteId = ((int)$requisiteId > 0) ? (int)$requisiteId : null;
		}

		if (!empty($linkedRequisiteId))
		{
			$requisites = EntityRequisite::getSingleInstance()->getById($linkedRequisiteId);
		}
		elseif ($this->getDocument()->getMycompanyId() > 0)
		{
			$defaultRequisite = new DefaultRequisite(
				new ItemIdentifier(\CCrmOwnerType::Company, $this->getDocument()->getMycompanyId())
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
