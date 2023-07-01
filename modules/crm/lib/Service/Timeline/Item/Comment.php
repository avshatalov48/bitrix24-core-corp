<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Service\Timeline\Layout\Action\Animation;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\RunAjaxAction;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\CommentContent;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItemFactory;
use Bitrix\Crm\Timeline\CommentController;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Main\Localization\Loc;

class Comment extends Configurable
{
	public function getType(): string
	{
		return 'Comment';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('CRM_TIMELINE_COMMENT_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Common\Icon::COMMENT;
	}

	public function getLogo(): ?Logo
	{
		return Common\Logo::getInstance(Common\Logo::COMMENT)->createLogo();
	}

	public function getContentBlocks(): ?array
	{
		$result = [];
		
		$contentBlock = $this->buildContentBlock();
		if (isset($contentBlock))
		{
			$result['commentContent'] = $contentBlock;
		}

		$filesBlock = $this->buildFilesBlock();
		if (isset($filesBlock))
		{
			$result = array_merge($result, $filesBlock);
		}

		return $result;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();

		$updateCommentAction = (new JsEvent('Comment:Edit')) // use single event to edit comment text and update files
			->setAnimation(Animation::disableItem()->setForever())
		;

		$items['edit'] = MenuItemFactory::createEditMenuItem()
			->setAction($updateCommentAction)
			->setScopeWeb()
		;

		$items['addFile'] = MenuItemFactory::createAddFileMenuItem()
			->setAction($updateCommentAction)
			->setScopeWeb()
		;

		$items['delete'] = MenuItemFactory::createDeleteMenuItem()
			->setAction(
				(new JsEvent('Comment:Delete'))
					->addActionParamInt('commentId', $this->getModel()->getId())
					->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getEntityId())
					->addActionParamString('confirmationText', Loc::getMessage('CRM_TIMELINE_COMMENT_DELETION_CONFIRM'))
					->setAnimation(Animation::disableItem()->setForever())
			)
		;
		
		return $items;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function buildContentBlock(): ?ContentBlock
	{
		$text = trim($this->getHistoryItemModel()->get('TEXT') ?? '');
		$hasFiles = $this->getHistoryItemModel()->get('HAS_FILES') === 'Y';
		//if ($text === '' && !$hasFiles)
		//{
		//	return null;
        //}

		$content = CommentController::convertToHtml(
			TimelineEntry::getByID($this->getModel()->getId()) ?? []
		)['COMMENT'] ?? '';

		return (new CommentContent())
			->setText($content)
			->setHeight(ContentBlock\EditableDescription::HEIGHT_LONG)
			->setAction(
				(new RunAjaxAction('crm.timeline.comment.update'))
					->addActionParamInt('ownerTypeId', $this->getContext()->getIdentifier()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getIdentifier()->getEntityId())
					->addActionParamInt('commentId', $this->getModel()->getId())
			)
			->setLoadAction(
				(new RunAjaxAction('crm.timeline.comment.load'))
					->addActionParamInt('ownerTypeId', $this->getContext()->getIdentifier()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getIdentifier()->getEntityId())
					->addActionParamInt('commentId', $this->getModel()->getId())
			)
			// TODO: set real count when new Disk API is available, currently 'rand method' in use to correct Vue client code work
			->setFilesCount($hasFiles ? random_int(10, 10000) : 0)
			->setHasInlineFiles($this->getHistoryItemModel()->get('HAS_INLINE_ATTACHMENT') === 'Y')
			->setScopeWeb()
		;
	}

	private function buildFilesBlock(): ?array
	{
		$storageFiles = $this->getUserFieldFiles();
		if (empty($storageFiles))
		{
			return null;
		}

		return null;
	}

	/**
	 * @return array
	 *
	 * @todo: implement new $USER_FIELD_MANAGER api
	 */
	private function getUserFieldFiles(): array
	{
		return [];
	}
}
