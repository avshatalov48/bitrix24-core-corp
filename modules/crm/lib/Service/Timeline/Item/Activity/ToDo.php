<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Mixin\FileListPreparer;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDate;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDescription;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\FileList;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ItemSelector;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItemFactory;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

class ToDo extends Activity
{
	use FileListPreparer;

	protected function getActivityTypeId(): string
	{
		return 'ToDo';
	}

	public function getIconCode(): ?string
	{
		return Icon::CIRCLE_CHECK;
	}

	public function getTitle(): string
	{
		return $this->isScheduled()
			? Loc::getMessage('CRM_TIMELINE_ITEM_TODO_TITLE_SCHEDULED')
			: Loc::getMessage('CRM_TIMELINE_ITEM_TODO_TITLE_HISTORY_ITEM')
		;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$deadline = $this->getDeadline();
		if (!$deadline)
		{
			return null;
		}

		return (new Layout\Body\CalendarLogo($deadline));
	}

	public function getContentBlocks(): array
	{
		$result = [];

		$deadlineBlock = $this->buildDeadlineBlock();
		if (isset($deadlineBlock))
		{
			$result['deadline'] = $deadlineBlock;
		}

		$webPingSelectorBlock = $this->buildWebPingListBlock();
		if (isset($webPingSelectorBlock))
		{
			$result['webPingSelector'] = $webPingSelectorBlock->setScopeWeb();
		}

		$mobilePingSelectorBlock = $this->buildMobilePingListBlock();
		if (isset($mobilePingSelectorBlock))
		{
			$result['mobilePingSelector'] = $mobilePingSelectorBlock->setScopeMobile();
		}

		$descriptionBlock = $this->buildDescriptionBlock();
		if (isset($descriptionBlock))
		{
			$result['description'] = $descriptionBlock;
		}

		$filesBlock = $this->buildFilesBlock();
		if (isset($filesBlock))
		{
			$result['fileList'] = $filesBlock;
		}

		$baseActivityBlock = $this->buildBaseActivityBlock();
		if (isset($baseActivityBlock))
		{
			$result['createdFrom'] = $baseActivityBlock;
		}

		return $result;
	}

	public function getButtons(): array
	{
		$buttons = [];
		if (!$this->isScheduled())
		{
			return $buttons;
		}

		$buttons['complete'] = (
			new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ITEM_TODO_COMPLETE'),
				Layout\Footer\Button::TYPE_PRIMARY,
			)
		)->setAction($this->getCompleteAction())->setHideIfReadonly();

		if ($this->canPostpone())
		{
			$buttons['postpone'] = (
				new Layout\Footer\Button(
					Loc::getMessage('CRM_TIMELINE_ITEM_TODO_POSTPONE'),
					Layout\Footer\Button::TYPE_SECONDARY,
				)
			)->setAction(new Layout\Action\ShowMenu($this->getPostponeMenu($this->getActivityId())))->setHideIfReadonly();
		}

		return $buttons;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();
		unset($items['view']);

		if ($this->isScheduled() && $this->hasUpdatePermission())
		{
			$ownerTypeId = $this->getContext()->getIdentifier()->getEntityTypeId();
			$ownerId = $this->getContext()->getIdentifier()->getEntityId();

			$items['addFile'] = MenuItemFactory::createAddFileMenuItem()
				->setAction((new Layout\Action\JsEvent('Activity:ToDo:AddFile'))
					->addActionParamInt('entityTypeId', CCrmOwnerType::Activity)
					->addActionParamInt('entityId', $this->getActivityId())
					->addActionParamString('files', implode(',', array_column($this->fetchStorageFiles(), 'FILE_ID')))
					->addActionParamInt('ownerTypeId', $ownerTypeId)
					->addActionParamInt('ownerId', $ownerId)
				)
			;

			$items['changeResponsible'] = MenuItemFactory::createChangeResponsibleMenuItem()
				->setAction((new Layout\Action\JsEvent('Activity:ToDo:ChangeResponsible'))
					->addActionParamInt('ownerTypeId', $ownerTypeId)
					->addActionParamInt('ownerId', $ownerId)
					->addActionParamInt('id', $this->getActivityId())
					->addActionParamInt('responsibleId', (int)$this->getAssociatedEntityModel()->get('RESPONSIBLE_ID'))
				)
			;

			if (Crm::isTimelineToDoCalendarSyncEnabled())
			{
				$items['settings'] = (new MenuItem(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_SETTINGS')))
					->setHideIfReadonly()
					->setSort(9990)
					->setAction((new Layout\Action\JsEvent('Activity:ToDo:ShowSettings'))
						->addActionParamInt('entityTypeId', CCrmOwnerType::Activity)
						->addActionParamInt('entityId', $this->getActivityId())
						->addActionParamInt('ownerTypeId', $ownerTypeId)
						->addActionParamInt('ownerId', $ownerId)
					)
					->setScopeWeb()
				;
			}
		}

		return $items;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function buildDeadlineBlock(): ?ContentBlock
	{
		$deadline = $this->getDeadline();
		if (!isset($deadline))
		{
			return null;
		}

		$updateDeadlineAction = null;
		if ($this->isScheduled())
		{
			$updateDeadlineAction = (new Layout\Action\RunAjaxAction('crm.activity.todo.updateDeadline'))
				->addActionParamInt('ownerTypeId', $this->getContext()->getIdentifier()->getEntityTypeId())
				->addActionParamInt('ownerId', $this->getContext()->getIdentifier()->getEntityId())
				->addActionParamInt('id', $this->getActivityId())
			;
		}

		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setAlignItems('center')
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_COMPLETE_TO'))
			->setContentBlock(
				(new EditableDate())
					->setReadonly(!$this->isScheduled())
					->setStyle(EditableDate::STYLE_PILL)
					->setDate($deadline)
					->setAction($updateDeadlineAction)
					->setBackgroundColor($this->isScheduled() ? EditableDate::BACKGROUND_COLOR_WARNING : null)
				)
			->setInline()
		;
	}

	private function buildWebPingListBlock(): ?ContentBlock
	{
		if ($this->isScheduled() && $this->hasUpdatePermission())
		{
			return $this->buildChangeablePingSelectorBlock();
		}

		$blockText = $this->buildWebPingBlockText();
		return $this->buildReadonlyPingSelectorBlock($blockText);
	}

	private function buildMobilePingListBlock(): ?ContentBlock
	{
		$emptyStateText = Loc::getMessage('CRM_TIMELINE_ITEM_TODO_PING_OFFSETS_EMPTY_STATE');
		$selectorTitle = Loc::getMessage('CRM_TIMELINE_ITEM_TODO_PING_OFFSETS_SELECTOR_TITLE_MSGVER_1');

		if ($this->isScheduled() && $this->hasUpdatePermission())
		{
			return $this->buildChangeablePingSelectorBlock($emptyStateText, $selectorTitle);
		}

		$blockText = $this->buildMobilePingBlockText($emptyStateText);
		return $this->buildReadonlyPingSelectorBlock($blockText);
	}

	private function buildChangeablePingSelectorBlock($emptyStateText = null, $selectorTitle = null): ?ContentBlock
	{
		$offsets = $this->getPingOffsets();
		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_PING_OFFSETS_TITLE'))
			->setContentBlock(
				(new ItemSelector())
					->setSelectorTitle($selectorTitle)
					->setValuesList(array_map(
						static fn($item) => ['id' => (string)$item['offset'], 'title' => $item['title']],
						TodoPingSettingsProvider::getDefaultOffsetList()
					))
					->setValue(array_column($offsets, 'offset'))
					->setEmptyState($emptyStateText)
					->setAction(
						(new Layout\Action\RunAjaxAction('crm.activity.todo.updatePingOffsets'))
							->addActionParamInt('ownerTypeId', $this->getContext()->getIdentifier()->getEntityTypeId())
							->addActionParamInt('ownerId', $this->getContext()->getIdentifier()->getEntityId())
							->addActionParamInt('id', $this->getActivityId())
					)
			)
			->setInline();
	}

	private function buildMobilePingBlockText($emptyStateText = null)
	{
		$offsets = array_column($this->getPingOffsets(), 'title');
		if (count($offsets) === 0)
		{
			return $emptyStateText;
		}

		$blockText = mb_strtolower(implode(', ', array_slice($offsets, 0, 2)));
		if (count($offsets) > 2)
		{
			$blockText = Loc::getMessage( 'CRM_TIMELINE_ITEM_TODO_PING_OFFSETS_MORE',
				[
					'#ITEMS#' => $blockText,
					'#COUNT#' => count($offsets) - 2,
				],
			);
		}

		return $blockText;
	}

	private function buildWebPingBlockText($emptyStateText = null)
	{
		$offsets = $this->getPingOffsets();
		$blockText = mb_strtolower(implode(', ', array_column($offsets, 'title')));
		if ($blockText === '')
		{
			$blockText = $emptyStateText;
		}

		return $blockText;
	}

	private function buildReadonlyPingSelectorBlock($blockText = null): ?ContentBlock
	{
		if ((string)$blockText === '')
		{
			return null;
		}

		return (new LineOfTextBlocks())
			->addContentBlock(
				'remindTo',
				ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_PING_OFFSETS_TITLE'))
			)
			->addContentBlock(
				'pingSelectorReadonly',
				(new Text())
					->setValue($blockText)
					->setColor(Text::COLOR_BASE_70)
					->setFontSize(Text::FONT_SIZE_XS)
					->setFontWeight(Text::FONT_WEIGHT_MEDIUM)
			);
	}

	private function buildDescriptionBlock(): ?ContentBlock
	{
		$description = (string)($this->getAssociatedEntityModel()->get('DESCRIPTION') ?? '');

		if ($description === '')
		{
			return null;
		}
		$description = trim($description);

		$editableDescriptionBlock = (new EditableDescription())
			->setText($description)
			->setEditable(false)
			->setBackgroundColor(EditableDescription::BG_COLOR_YELLOW)
		;

		if ($this->isScheduled())
		{
			$editableDescriptionBlock->setAction(
				(new Layout\Action\RunAjaxAction('crm.activity.todo.updateDescription'))
					->addActionParamInt('ownerTypeId', $this->getContext()->getIdentifier()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getIdentifier()->getEntityId())
					->addActionParamInt('id', $this->getActivityId())
			)
			->setEditable(true);
		}

		return $editableDescriptionBlock;
	}

	private function buildFilesBlock(): ?ContentBlock
	{
		$storageFiles = $this->fetchStorageFiles();
		if (empty($storageFiles))
		{
			return null;
		}

		$files = $this->prepareFiles($storageFiles);

		$fileListBlock = (new FileList())
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_FILES_MSGVER_1'))
			->setFiles($files);

		if ($this->isScheduled() && $this->hasUpdatePermission())
		{
			$fileListBlock->setUpdateParams([
				'type' => $this->getType(),
				'entityTypeId' => CCrmOwnerType::Activity,
				'entityId' => $this->getActivityId(),
				'files' => array_column($storageFiles, 'FILE_ID'),
				'ownerTypeId' =>  $this->getContext()->getIdentifier()->getEntityTypeId(),
				'ownerId' => $this->getContext()->getIdentifier()->getEntityId(),
			]);
		}

		return $fileListBlock;
	}

	private function buildBaseActivityBlock(): ?ContentBlock
	{
		$associatedActivityId = $this->getAssociatedEntityModel()?->get('ASSOCIATED_ENTITY_ID');
		if (!isset($associatedActivityId))
		{
			return null;
		}

		$baseActivitySubject = Container::getInstance()
			->getActivityBroker()
			->getById($associatedActivityId)['SUBJECT'] ?? ''
		;

		if (empty($baseActivitySubject))
		{
			return null;
		}

		return (new LineOfTextBlocks())
			->addContentBlock(
				'createdFrom',
				ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_CREATED_FROM'))
			)
			->addContentBlock(
				'baseActivity',
				(new Text())
					->setValue($baseActivitySubject)
					->setColor(Text::COLOR_BASE_70)
					->setFontWeight(Text::FONT_WEIGHT_MEDIUM)
			)
		;
	}

	private function getPingOffsets(): array
	{
		$offsets = (array)($this->getAssociatedEntityModel()->get('PING_OFFSETS') ?? []);
		if (empty($offsets))
		{
			$offsets = Provider\ToDo::getPingOffsets($this->getActivityId());
		}

		return TodoPingSettingsProvider::getValuesByOffsets($offsets);
	}
}
