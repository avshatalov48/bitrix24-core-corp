<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Crm\Format\TextHelper;
use Bitrix\Crm\Service\Timeline\Item\Mixin\FileListPreparer;
use Bitrix\Crm\Service\Timeline\Layout\Action\Animation;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Action\RunAjaxAction;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\CommentContent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\FileList;
use Bitrix\Crm\Service\Timeline\Layout\Body\Logo;
use Bitrix\Crm\Service\Timeline\Layout\Common;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Header\ChangeStreamButton;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItemFactory;
use Bitrix\Crm\Timeline\CommentController;
use Bitrix\Crm\Timeline\TimelineEntry;
use Bitrix\Main\Localization\Loc;
use CCrmActivity;

class Comment extends Configurable
{
	use FileListPreparer;

	public function getType(): string
	{
		return 'Comment';
	}

	protected function isBuiltOnlyForCurrentUser(): bool
	{
		return true;
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

	private function isCurrentUserAuthor(): bool
	{
		return $this->getAuthorId() === $this->getContext()->getUserId();
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$contentWebBlock = $this->buildContentWebBlock();
		if (isset($contentWebBlock))
		{
			$result['commentContentWeb'] = $contentWebBlock;
		}

		$contentMobileBlock = $this->buildContentMobileBlock();
		if (isset($contentMobileBlock))
		{
			$result['commentContentMobile'] = $contentMobileBlock;
		}

		$filesBlock = $this->buildFilesBlock();
		if ($filesBlock)
		{
			$result['fileList'] = $filesBlock;
		}

		return $result;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();

		if ($this->hasUpdatePermission())
		{
			$updateCommentAction = (new JsEvent('Comment:Edit')) // use single event to edit comment text and update files
				->setAnimation(Animation::disableItem()->setForever())
			;

			$items['edit'] = MenuItemFactory::createEditMenuItem()
											->setAction($updateCommentAction)
											->setScopeWeb()
			;


			$ownerTypeId = $this->getContext()->getIdentifier()->getEntityTypeId();
			$ownerId = $this->getContext()->getIdentifier()->getEntityId();
			$files = implode(',', array_column($this->getUserFieldFiles(), 'FILE_ID'));

			$items['addFile'] = MenuItemFactory::createAddFileMenuItem()
				->setAction((new JsEvent('Comment:AddFile'))
					->addActionParamInt('entityTypeId', $this->getModel()->getTypeId())
					->addActionParamInt('entityId', $this->getModel()->getId())
					->addActionParamString('files', $files)
					->addActionParamInt('ownerTypeId', $ownerTypeId)
					->addActionParamInt('ownerId', $ownerId)
					->setAnimation(Animation::disableItem()->setForever())
				)
			;
		}

		if ($this->hasDeletePermission())
		{
			$items['delete'] = MenuItemFactory::createDeleteMenuItem()
				->setAction((new JsEvent('Comment:Delete'))
					->addActionParamInt('commentId', $this->getModel()->getId())
					->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getEntityId())
					->addActionParamString('confirmationText', Loc::getMessage('CRM_TIMELINE_COMMENT_DELETION_CONFIRM'))
					->setAnimation(Animation::disableItem()->setForever())
				)
			;
		}

		return $items;
	}

	public function getButtons(): ?array
	{
		$buttons = parent::getButtons() ?? [];

		$isFixed = $this->getModel()->isFixed();
		$buttons['changeStreamButton'] = !$isFixed
			? $this->getPinFooterButton()
			: $this->getUnpinFooterButton()
		;

		return $buttons;
	}

	private function getPinFooterButton(): Button
	{
		return (new Button(ChangeStreamButton::getPinTitle(), Button::TYPE_SECONDARY))
			->setAction($this->getPinAction())
		;
	}

	private function getUnpinFooterButton(): Button
	{
		return (new Button(ChangeStreamButton::getUnpinTitle(), Button::TYPE_SECONDARY))
			->setAction($this->getUnpinAction())
		;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	protected function hasUpdatePermission(): bool
	{
		return $this->isCurrentUserAuthor();
	}

	protected function hasDeletePermission(): bool
	{
		if ($this->getContext()->getUserPermissions()->isAdminForEntity($this->getContext()->getEntityTypeId()))
		{
			return true;
		}
		return $this->isCurrentUserAuthor();
	}

	private function buildContentWebBlock(): ?ContentBlock
	{
		return $this->buildContentBlock();
	}

	private function buildContentMobileBlock(): ?ContentBlock
	{
		return $this->buildContentBlock(ContentBlock::SCOPE_MOBILE);
	}

	private function buildContentBlock(string $scope = ContentBlock::SCOPE_WEB): CommentContent
	{
		$hasFiles = $this->getHistoryItemModel()->get('HAS_FILES') === 'Y';

		$block = (new CommentContent())
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
			->setFilesCount($hasFiles ? count($this->getUserFieldFiles()) : 0)
			->setHasInlineFiles($this->getHistoryItemModel()->get('HAS_INLINE_ATTACHMENT') === 'Y')
		;

		$data = TimelineEntry::getByID($this->getModel()->getId()) ?? [];

		$block->setEditable($this->isCurrentUserAuthor());
		if ($scope === ContentBlock::SCOPE_MOBILE)
		{
			$comment = $data['COMMENT'] ?? '';
			$content = TextHelper::sanitizeBbCode($comment);
			$block->setScopeMobile();
		}
		else
		{
			$content = CommentController::convertToHtml($data)['COMMENT'] ?? '';
			$content = htmlspecialcharsbx($content);
			$block->setScopeWeb();
		}

		return $block->setText($content);
	}

	private function buildFilesBlock(): ?ContentBlock
	{
		$storageFiles = $this->getUserFieldFiles();

		if (empty($storageFiles))
		{
			return null;
		}

		$files = $this->prepareFiles($storageFiles);

		$fileListBlock = (new FileList())
			->setTitle(Loc::getMessage('CRM_TIMELINE_COMMENT_FILES'))
			->setFiles($files)
			->setScopeMobile()
		;

		if ($this->hasUpdatePermission())
		{
			$fileListBlock->setUpdateParams([
				'type' => $this->getType(),
				'entityTypeId' => $this->getModel()->getTypeId(),
				'entityId' => $this->getModel()->getId(),
				'files' => array_column($storageFiles, 'FILE_ID'),
				'ownerTypeId' =>  $this->getContext()->getIdentifier()->getEntityTypeId(),
				'ownerId' => $this->getContext()->getIdentifier()->getEntityId(),
			]);
		}

		return $fileListBlock;
	}

	private function getUserFieldFiles(): array
	{
		$model = $this->getModel();
		$context = $this->getContext();

		return CommentController::getFiles(
			$model->getId(),
			$context->getEntityId(),
			$context->getEntityTypeId()
		);
	}
}
