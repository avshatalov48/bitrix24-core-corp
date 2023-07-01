<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Audio;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\FileList;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Model\File;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItemFactory;
use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Localization\Loc;
use CCrmActivity;
use CCrmOwnerType;

class ToDo extends Activity
{
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

		$descriptionBlock = $this->buildDescriptionBlock();
		if (isset($descriptionBlock))
		{
			$result['description'] = $descriptionBlock;
		}

		$filesBlock = $this->buildFilesBlock();
		if (isset($filesBlock))
		{
			$result = array_merge($result, $filesBlock);
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

		return (new Layout\Body\ContentBlock\LineOfTextBlocks())
			->addContentBlock(
				'completeTo',
				ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_COMPLETE_TO'))
			)
			->addContentBlock(
				'deadlineSelector',
				(new Layout\Body\ContentBlock\EditableDate())
					->setStyle(Layout\Body\ContentBlock\EditableDate::STYLE_PILL)
					->setDate($deadline)
					->setAction($updateDeadlineAction)
					->setBackgroundColor($this->isScheduled() ? Layout\Body\ContentBlock\EditableDate::BACKGROUND_COLOR_WARNING : null)
			)
		;
	}

	private function buildDescriptionBlock(): ?ContentBlock
	{
		$description = (string)($this->getAssociatedEntityModel()->get('DESCRIPTION') ?? '');

		if ($description === '')
		{
			return null;
		}
		$description = trim($description);

		$editableDescriptionBlock = (new Layout\Body\ContentBlock\EditableDescription())
			->setText($description)
			->setEditable(false)
			->setBackgroundColor(Layout\Body\ContentBlock\EditableDescription::BG_COLOR_YELLOW)
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

	private function buildFilesBlock(): ?array
	{
		$storageFiles = $this->fetchStorageFiles();
		if (empty($storageFiles))
		{
			return null;
		}

		$audioRecordsCount = $this->getAudioFilesCount($storageFiles);

		$files = [];
		$audioRecords = [];
		foreach ($storageFiles as $file)
		{
			$fileId = $file['ID']; // unique ID
			$sourceFileId = (int)$file['FILE_ID'];
			$fileName = trim((string)$file['NAME']);
			$fileSize = (int)$file['SIZE'];
			$viewUrl = (string)$file['VIEW_URL'];
			$previewUrl = $file['PREVIEW_URL'] ? (string)$file['PREVIEW_URL'] : null;

			$fileModel = new File($fileId, $sourceFileId, $fileName, $fileSize, $viewUrl, $previewUrl);

			// fill audio records
			if (in_array($fileModel->getExtension(), self::ALLOWED_AUDIO_EXTENSIONS, true))
			{
				$audioRecord = (new Audio())
					->setId($fileId)
					->setSource((string)$file['VIEW_URL'])
					->setTitle($fileName)
				;

				if (!empty($fileName && $audioRecordsCount > 1))
				{
					$audioRecord->setRecordName($fileName);
				}

				$audioRecords["audio_{$fileId}"] = $audioRecord;

			}

			$files[] = $fileModel;
		}

		$fileListBlock = (new FileList())
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_FILES'))
			->setFiles($files);

		if ($this->isScheduled() && $this->hasUpdatePermission())
		{
			$fileListBlock->setUpdateParams([
				'entityTypeId' => CCrmOwnerType::Activity,
				'entityId' => $this->getActivityId(),
				'files' => array_column($storageFiles, 'FILE_ID'),
				'ownerTypeId' =>  $this->getContext()->getIdentifier()->getEntityTypeId(),
				'ownerId' => $this->getContext()->getIdentifier()->getEntityId(),
			]);
		}

		// audio record(s) at the top
		return array_merge($audioRecords, ['fileList' => $fileListBlock]);
	}

	private function buildBaseActivityBlock(): ?ContentBlock
	{
		$associatedEntityId = $this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID');
		if (!isset($associatedEntityId))
		{
			return null;
		}

		$baseActivity = CCrmActivity::GetList(
			[],
			[
				'=ID' => $associatedEntityId,
				'CHECK_PERMISSIONS' => 'N'
			],
			false,
			false,
			[
				'SUBJECT',
				'ID'
			]
		)->Fetch();
		if ($baseActivity)
		{
			return (new Layout\Body\ContentBlock\LineOfTextBlocks())
				->addContentBlock(
					'createdFrom',
					ContentBlockFactory::createTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_CREATED_FROM'))
				)
				->addContentBlock(
					'baseActivity',
					(new Layout\Body\ContentBlock\Text())
						->setValue($baseActivity['SUBJECT'] ?: Loc::getMessage('CRM_COMMON_UNTITLED'))
						->setIsBold(true)
						->setColor(Layout\Body\ContentBlock\Text::COLOR_BASE_70)
				)
			;
		}

		return null;
	}

	private function getAudioFilesCount(array $files): int
	{
		$result =  array_values(
			array_filter(
				$files,
				static fn($row) => in_array(
					GetFileExtension(mb_strtolower($row['NAME'])),
					self::ALLOWED_AUDIO_EXTENSIONS
				)
			)
		);

		return count($result);
	}
}
