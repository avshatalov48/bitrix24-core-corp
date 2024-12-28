<?php

namespace Bitrix\Crm\Service\Timeline\Item\Activity;

use Bitrix\Crm\Activity\Analytics\Dictionary;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Activity\TodoPingSettingsProvider;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\EventHandler;
use Bitrix\Crm\Integration\Calendar;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Mixin\FileListPreparer;
use Bitrix\Crm\Service\Timeline\Item\Payload;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action\JsEvent;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockFactory;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDate;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\EditableDescription;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\FileList;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ItemSelector;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\LineOfTextBlocks;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\PingSelector;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Text;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItemFactory;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Uri;
use CCrmOwnerType;

class ToDo extends Activity
{
	use FileListPreparer;

	protected function getActivityTypeId(): string
	{
		return 'ToDo';
	}

	public function canUseColorSelector(): bool
	{
		return true;
	}

	final public function getColor(): ?array
	{
		$settings = $this->getAssociatedEntityModel()?->get('SETTINGS') ?? [];
		$color = $settings['COLOR'] ?? ColorSettingsProvider::getDefaultColorId();

		return (new ColorSettingsProvider())->getByColorId($color);
	}

	public function getIconCode(): ?string
	{
		return Icon::CIRCLE_CHECK;
	}

	public function getTitle(): string
	{
		$subject = $this->getAssociatedEntityModel()?->get('SUBJECT');

		if ($subject !== null && $subject !== '')
		{
			return $subject;
		}

		if ($this->isScheduled())
		{
			return Loc::getMessage('CRM_TIMELINE_ITEM_TODO_TITLE_SCHEDULED');
		}

		return Loc::getMessage('CRM_TIMELINE_ITEM_TODO_TITLE_HISTORY_ITEM');
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$deadline = $this->getDeadline();
		if (!$deadline)
		{
			return null;
		}

		$calendarEventId = $this->getCalendarEventId();

		$logo = (new Layout\Body\CalendarLogo($deadline, $calendarEventId));

		$color = $this->getColor();
		if ($color && $this->isScheduled())
		{
			$logo->setBackgroundColor($color['logoBackground']);
		}

		return $logo;
	}

	protected function getCalendarEventId(): ?int
	{
		$eventId = $this->getModel()->getAssociatedEntityModel()?->get('CALENDAR_EVENT_ID');

		return ($eventId === null ? null : (int)$eventId);
	}

	/**
	 * @throws ArgumentTypeException
	 */
	public function getContentBlocks(): array
	{
		$result = [];

		$calendarEventBlock = $this->buildCalendarEventBlock();
		if (isset($calendarEventBlock))
		{
			$result['calendarEventBlock'] = $calendarEventBlock->setScopeWeb();
		}

		$deadlineBlock = $this->buildDeadlineBlock();
		if (isset($deadlineBlock))
		{
			$result['deadline'] = $deadlineBlock->setScopeMobile();
		}

		$webDeadlineAndPingSelectorBlock = $this->buildWebDeadlineAndPingSelectorBlock();
		if (isset($webDeadlineAndPingSelectorBlock))
		{
			$result['webDeadlineAndPingSelector'] = $webDeadlineAndPingSelectorBlock->setScopeWeb();
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

		$locationBlock = $this->buildLocationBlock();
		if (isset($locationBlock))
		{
			$result['location'] = $locationBlock;
		}

		$usersBlock = $this->buildUsersBlock();
		if (isset($usersBlock))
		{
			$result['users'] = $usersBlock;
		}

		$clientsBlock = $this->buildClientsBlock();
		if (isset($clientsBlock))
		{
			$result['clients'] = $clientsBlock;
		}

		$addressBlock = $this->buildAddressBlock();
		if (isset($addressBlock))
		{
			$result['address'] = $addressBlock;
		}

		$linkBlock = $this->buildLinkBlock();
		if (isset($linkBlock))
		{
			$result['link'] = $linkBlock;
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
			$repeatButton = new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ITEM_TODO_REPEAT'),
				Layout\Footer\Button::TYPE_SECONDARY,
			);
			$repeatAction = (new Layout\Action\JsEvent('Activity:ToDo:Repeat'))
				->addActionParamInt('activityId', $this->getActivityId())
				->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
				->addActionParamInt('ownerId', $this->getContext()->getEntityId())
			;

			$buttons['repeat'] = $repeatButton
				->setAction($repeatAction)
				->setScopeWeb()
				->setHideIfReadonly()
			;

			return $buttons;
		}

		$buttons['complete'] = (
			new Layout\Footer\Button(
				Loc::getMessage('CRM_TIMELINE_ITEM_TODO_COMPLETE'),
				Layout\Footer\Button::TYPE_PRIMARY,
			)
		)->setAction($this->getCompleteAction())->setHideIfReadonly();

		$updateButton = new Layout\Footer\Button(
			Loc::getMessage('CRM_TIMELINE_ITEM_TODO_UPDATE'),
			Layout\Footer\Button::TYPE_SECONDARY,
		);
		$updateAction = (new Layout\Action\JsEvent('Activity:ToDo:Update'))
			->addActionParamInt('activityId', $this->getActivityId())
			->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
			->addActionParamInt('ownerId', $this->getContext()->getEntityId())
		;

		$buttons['update'] = $updateButton
			->setAction($updateAction)
			->setScopeWeb()
			->setHideIfReadonly()
		;

		return $buttons;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();
		unset($items['view']);

		$identifier = $this->getContext()->getIdentifier();
		$ownerTypeId = $identifier->getEntityTypeId();
		$ownerId = $identifier->getEntityId();

		if ($this->isScheduled() && $this->hasUpdatePermission())
		{
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
					->addActionParamInt('responsibleId', (int)$this->getAssociatedEntityModel()?->get('RESPONSIBLE_ID'))
				)
			;

			if ($this->hasOverlapEventTag())
			{
				$items['deleteTag'] = $this->createDeleteTagMenuItem($this->getActivityId());
			}
		}

		return $items;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function buildCalendarEventBlock(): ?ContentBlockWithTitle
	{
		$calendarEvent = $this->getCalendarEvent();

		if ($calendarEvent === null)
		{
			return null;
		}

		$entryDateFrom = \CUtil::JSescape($calendarEvent['DATE_FROM']);
		$offset = (int)$calendarEvent['TZ_OFFSET_FROM'];

		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setAlignItems('center')
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_CALENDAR_EVENT'))
			->setContentBlock(
				(new ContentBlock\Link())
					->setValue($calendarEvent['NAME'])
					->setAction(
						(new Layout\Action\JsEvent('Activity:ToDo:ShowCalendar'))
							->addActionParamInt('calendarEventId', $calendarEvent['ID'])
							->addActionParamString('entryDateFrom', $entryDateFrom)
							->addActionParamInt('timezoneOffset', $offset)
					)
			)
			->setInline()
		;
	}

	private function buildDeadlineBlock(): ?ContentBlockWithTitle
	{
		$deadline = $this->getDeadline();
		if (!isset($deadline))
		{
			return null;
		}

		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setAlignItems('center')
			->setTitle($this->getDeadlineEditableDateTitle())
			->setContentBlock($this->buildDeadlineEditableDateBlock())
			->setInline()
		;
	}

	private function buildDeadlineEditableDateBlock(): ?EditableDate
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

		return (new EditableDate())
			->setReadonly(!$this->isScheduled() || !$this->hasUpdatePermission())
			->setStyle(EditableDate::STYLE_PILL)
			->setDate($deadline)
			->setDuration($this->getDeadlineEditableDateDuration())
			->setAction($updateDeadlineAction)
			->setBackgroundColor(
				$this->isScheduled() ?
					EditableDate::BACKGROUND_COLOR_WARNING
					: null
			)
		;
	}

	private function getDeadlineEditableDateTitle(): string
	{
		$calendarEvent = $this->getCalendarEvent();
		if ($calendarEvent)
		{
			return Loc::getMessage('CRM_TIMELINE_ITEM_TODO_COMPLETE_TO_WITH_CALENDAR_EVENT');
		}

		return Loc::getMessage('CRM_TIMELINE_ITEM_TODO_COMPLETE_TO');
	}

	private function getDeadlineEditableDateDuration(): ?int
	{
		$startTime = $this->getAssociatedEntityModel()?->get('START_TIME');
		$endTime = $this->getAssociatedEntityModel()?->get('END_TIME');
		if (
			empty($startTime)
			|| empty($endTime)
			|| $startTime === $endTime
			|| $this->getCalendarEventId() <= 0
		)
		{
			return null;
		}

		$startDateTime = DateTime::createFromText($startTime);
		$endDateTime = DateTime::createFromText($endTime);

		return $endDateTime?->getTimestamp() - $startDateTime?->getTimestamp();
	}

	private function getCalendarEvent(): ?array
	{
		if ($this->getCalendarEventId() > 0)
		{
			return \Bitrix\Crm\Integration\Calendar::getEvent($this->getCalendarEventId());
		}

		return null;
	}

	/**
	 * @throws ArgumentTypeException
	 */
	private function buildWebDeadlineAndPingSelectorBlock(): ?ContentBlock
	{
		$deadlineAndPingSelector = new ContentBlock\Activity\DeadlineAndPingSelector();
		$deadlineBlock = $this->buildDeadlineEditableDateBlock();
		if ($deadlineBlock)
		{
			$deadlineBlock
				->setBackgroundColor(EditableDate::BACKGROUND_COLOR_NONE)
				->setStyle(EditableDate::STYLE_PILL_INLINE_GROUP)
			;
			$deadlineAndPingSelector
				->setDeadlineBlock($deadlineBlock)
				->setDeadlineBlockTitle($this->getDeadlineEditableDateTitle())
				->setIsScheduled($this->isScheduled())
			;
		}

		$pingSelectorBlock = $this->buildWebPingListBlock();
		if ($pingSelectorBlock)
		{
			$deadlineAndPingSelector->setPingSelectorBlock($pingSelectorBlock);
		}

		if ($this->isScheduled())
		{
			$deadlineAndPingSelector->setBackgroundToken(
				ContentBlock\Activity\DeadlineAndPingSelector::BACKGROUND_ORANGE
			);
		}
		else
		{
			$deadlineAndPingSelector->setBackgroundToken(
				ContentBlock\Activity\DeadlineAndPingSelector::BACKGROUND_GREY
			);
		}

		$color = $this->getColor();
		if ($color)
		{
			$deadlineAndPingSelector->setBackgroundColorById($color['id']);
		}

		return $deadlineAndPingSelector;
	}

	private function buildWebPingListBlock(): ItemSelector | PingSelector | Text | null
	{
		if ($this->isScheduled() && $this->hasUpdatePermission())
		{
			return $this->buildChangeablePingSelectorItem();
		}

		return $this->buildReadonlyPingSelectorItem();
	}

	private function buildChangeablePingSelectorItem(): PingSelector
	{
		$result = [];
		foreach (TodoPingSettingsProvider::getDefaultOffsetList() as $item)
		{
			$result[$item['id']] = $item;
		}

		$offsets = $this->getPingOffsets();
		foreach ($offsets as $item)
		{
			$result[$item['id']] = $item;
		}

		usort($result, static fn($a, $b) => $a['offset'] <=> $b['offset']);

		$identifier = $this->getContext()->getIdentifier();

		return (new PingSelector())
			->setValue(array_column($offsets, 'offset'))
			->setValuesList(array_map(
				static fn($item) => ['id' => (string)$item['offset'], 'title' => $item['title']],
				array_values($result)
			))
			->setAction(
				(new Layout\Action\RunAjaxAction('crm.activity.ping.updateOffsets'))
					->addActionParamInt('ownerTypeId', $identifier->getEntityTypeId())
					->addActionParamInt('ownerId', $identifier->getEntityId())
					->addActionParamInt('id', $this->getActivityId())
			)
			->setIcon('bell')
			->setDeadline(new DateTime($this->getAssociatedEntityModel()->get('DEADLINE')))
		;
	}

	private function buildMobilePingListBlock(): ?ContentBlock
	{
		$emptyStateText = Loc::getMessage('CRM_TIMELINE_ITEM_TODO_PING_OFFSETS_EMPTY_STATE');
		$selectorTitle = Loc::getMessage('CRM_TIMELINE_ITEM_TODO_PING_OFFSETS_SELECTOR_TITLE_MSGVER_1');

		if ($this->isScheduled() && $this->hasUpdatePermission())
		{
			return $this->buildChangeablePingSelectorMobileBlock($emptyStateText, $selectorTitle);
		}

		return $this->buildReadonlyPingSelectorBlock($this->buildMobilePingBlockText($emptyStateText));
	}

	private function buildChangeablePingSelectorMobileBlock($emptyStateText = null, $selectorTitle = null): ?ContentBlock
	{
		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_PING_OFFSETS_TITLE'))
			->setContentBlock($this->buildChangeableItemSelectorItem($emptyStateText, $selectorTitle))
			->setInline()
		;
	}

	private function buildChangeableItemSelectorItem(
		$emptyStateText = null,
		$selectorTitle = null,
		bool $compactMode = false
	): ?ItemSelector
	{
		$offsets = $this->getPingOffsets();
		$identifier = $this->getContext()->getIdentifier();

		$selector = (new ItemSelector())
			->setEmptyState($emptyStateText)
			->setValue(array_column($offsets, 'offset'))
			->setValuesList(array_map(
				static fn($item) => ['id' => (string)$item['offset'], 'title' => $item['title']],
				TodoPingSettingsProvider::getDefaultOffsetList()
			))
			->setAction(
				(new Layout\Action\RunAjaxAction('crm.activity.ping.updateOffsets'))
					->addActionParamInt('ownerTypeId', $identifier->getEntityTypeId())
					->addActionParamInt('ownerId', $identifier->getEntityId())
					->addActionParamInt('id', $this->getActivityId())
			)
		;

		if ($compactMode)
		{
			$selector->setCompactMode();
			$selector->setIcon('bell');
		}
		else
		{
			$selector->setSelectorTitle($selectorTitle);
		}

		return $selector;
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
				$this->buildReadonlyPingSelectorItem($blockText)
			);
	}

	private function buildReadonlyPingSelectorItem($blockText = null): ?Text
	{
		if ((string)$blockText === '')
		{
			return null;
		}

		return (new Text())
			->setValue($blockText)
			->setColor(Text::COLOR_BASE_70)
			->setFontSize(Text::FONT_SIZE_XS)
			->setFontWeight(Text::FONT_WEIGHT_MEDIUM)
		;
	}

	private function buildDescriptionBlock(): ?ContentBlock
	{
		$description = (string)($this->getAssociatedEntityModel()?->get('DESCRIPTION') ?? '');
		if ($description === '')
		{
			return null;
		}
		$description = trim($description);

		// Temporarily removes [p] for mobile compatibility
		$descriptionType = (int)$this->getAssociatedEntityModel()?->get('DESCRIPTION_TYPE');
		if ($this->getContext()->getType() === Context::MOBILE && $descriptionType === \CCrmContentType::BBCode)
		{
			$description = \Bitrix\Crm\Format\TextHelper::removeParagraphs($description);
		}

		$editableDescriptionBlock = (new EditableDescription())
			->setText($description)
			->setEditable(false)
			->setUseBBCodeEditor(true)
		;

		if (!$this->isScheduled() && $this->getContext()->getType() === Context::MOBILE)
		{
			$editableDescriptionBlock->setBackgroundColor(EditableDescription::BG_COLOR_YELLOW);
		}

		if ($this->isScheduled())
		{
			$editableDescriptionBlock->setAction(
				(new Layout\Action\RunAjaxAction('crm.activity.todo.updateDescription'))
					->addActionParamInt('ownerTypeId', $this->getContext()->getIdentifier()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getIdentifier()->getEntityId())
					->addActionParamInt('id', $this->getActivityId())
			)
			->setEditable(true);

			if (AIManager::isEnabledInGlobalSettings(EventHandler::SETTINGS_FILL_CRM_TEXT_ENABLED_CODE))
			{
				$editableDescriptionBlock->setCopilotSettings([
					'moduleId' => 'crm',
					'contextId' => 'crm_timeline_todo_editor_update_item_' . $this->getActivityId(),
					'category' => 'crm_activity',
					'autoHide' => true,
				]);
			}
		}

		return $editableDescriptionBlock;
	}

	private function buildLocationBlock(): ?ContentBlock
	{
		$settings = $this->getAssociatedEntityModel()?->get('SETTINGS');
		if ($settings === null)
		{
			return null;
		}

		$location = $settings['LOCATION'] ?? null;

		if ($location === null)
		{
			return null;
		}

		if (!Loader::includeModule('calendar'))
		{
			return null;
		}

		$location = \Bitrix\Calendar\Rooms\Util::parseLocation($location);
		$sectionList = \Bitrix\Calendar\Rooms\Manager::getRoomsList();

		$locationItem = null;
		foreach($sectionList as $room)
		{
			if ((int)$room['ID'] === (int)$location['room_id'])
			{
				$locationItem = $room;
				break;
			}
		}

		if (!$locationItem)
		{
			return null;
		}

		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setAlignItems('center')
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_LOCATION_TITLE'))
			->setContentBlock(
				(new ContentBlock\Text())
					->setValue($locationItem['NAME'])
					->setColor(Text::COLOR_BASE_90)
			)
			->setInline()
		;
	}

	private function buildUsersBlock(): ?ContentBlock
	{
		$settings = $this->getAssociatedEntityModel()?->get('SETTINGS');
		if ($settings === null)
		{
			return null;
		}

		$users = $settings['USERS'] ?? null;

		if (!is_array($users))
		{
			return null;
		}

		if ($this->hasOnlyOneClient($users))
		{
			return null;
		}

		$broker = Container::getInstance()->getUserBroker();
		$userItems = $broker->getBunchByIds($users);

		$lineOfTextBlocks = (new Layout\Body\ContentBlock\LineOfTextBlocks())
			->setDelimiter(', ')
		;

		$this->appendUserTextBlocks($lineOfTextBlocks, $userItems);

		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setAlignItems('center')
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_USERS_TITLE'))
			->setContentBlock($lineOfTextBlocks)
			->setInline()
		;
	}

	private function hasOnlyOneClient(array $users): bool
	{
		return (count($users) === 1 && (int)$users[0] === $this->getContext()->getUserId());
	}

	private function appendUserTextBlocks(LineOfTextBlocks $lineOfTextBlocks, array $items): void
	{
		foreach ($items as $item)
		{
			$id = (int) $item['ID'];
			$userTextBlock = (new ContentBlock\Link())
				->setValue($item['FORMATTED_NAME'])
				->setAction(
					(new JsEvent('Activity:ToDo:User:Click'))
						->addActionParamInt('userId', $id)
				)
			;

			$lineOfTextBlocks->addContentBlock(
				'user_' . $id,
				$userTextBlock
			);
		}
	}

	private function buildClientsBlock(): ?ContentBlock
	{
		$settings = $this->getAssociatedEntityModel()?->get('SETTINGS');
		if ($settings === null)
		{
			return null;
		}

		$clients = $settings['CLIENTS'] ?? null;

		if (!is_array($clients))
		{
			return null;
		}

		$lineOfTextBlocks = (new Layout\Body\ContentBlock\LineOfTextBlocks())
			->setDelimiter(', ')
		;

		[$contacts, $companies] = $this->getItemIds($clients);

		$this->appendClientTextBlocks($lineOfTextBlocks, \CCrmOwnerType::Contact, $contacts);
		$this->appendClientTextBlocks($lineOfTextBlocks, \CCrmOwnerType::Company, $companies);

		if ($lineOfTextBlocks->isEmpty())
		{
			return null;
		}

		$title = (
			$lineOfTextBlocks->getContentBlocksCount() <= 1
				? Loc::getMessage('CRM_TIMELINE_ITEM_TODO_CLIENT_TITLE')
				: Loc::getMessage('CRM_TIMELINE_ITEM_TODO_CLIENTS_TITLE')
		);

		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setAlignItems('center')
			->setTitle($title)
			->setContentBlock($lineOfTextBlocks)
			->setInline()
		;
	}

	private function buildAddressBlock(): ?ContentBlockWithTitle
	{
		$settings = $this->getAssociatedEntityModel()?->get('SETTINGS');
		if ($settings === null)
		{
			return null;
		}

		$addressFormatted = $settings['ADDRESS_FORMATTED'] ?? null;

		if (!is_string($addressFormatted) || empty($addressFormatted))
		{
			return null;
		}

		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setAlignItems('center')
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_ADDRESS_TITLE'))
			->setContentBlock(
				(new ContentBlock\Address())
					->setAddressFormatted($addressFormatted)
			)
			->setInline()
		;
	}

	private function getItemIds(array $clients): array
	{
		$contactBroker = Container::getInstance()->getContactBroker();
		$companyBroker = Container::getInstance()->getCompanyBroker();

		$contactIds = [];
		$companyIds = [];

		foreach ($clients as $client)
		{
			if ((int)$client['ENTITY_TYPE_ID'] === \CCrmOwnerType::Contact)
			{
				$contactIds[] = $client['ENTITY_ID'];
			}
			else if ((int)$client['ENTITY_TYPE_ID'] === \CCrmOwnerType::Company)
			{
				$companyIds[] = $client['ENTITY_ID'];
			}
		}

		return [
			$contactBroker->getBunchByIds($contactIds),
			$companyBroker->getBunchByIds($companyIds),
		];
	}

	private function appendClientTextBlocks(
		LineOfTextBlocks $lineOfTextBlocks,
		int $entityTypeId,
		array $items
	): void
	{
		foreach ($items as $item)
		{
			$id = $item->getId();
			$itemIdentifier = new ItemIdentifier($entityTypeId, $id);

			$lineOfTextBlocks->addContentBlock(
				\CCrmOwnerType::ResolveName($entityTypeId) . '_' . $id,
				$this->getClientContentBlock($item->getHeading(), $itemIdentifier)
			);
		}
	}

	private function getClientContentBlock(string $name, ItemIdentifier $item): ContentBlock\Link
	{
		return (new ContentBlock\Link())
			->setValue($name)
			->setAction(
				(new JsEvent('Activity:ToDo:Client:Click'))
					->addActionParamInt('entityTypeId', $item->getEntityTypeId())
					->addActionParamInt('entityId', $item->getEntityId())
			)
		;
	}

	private function buildLinkBlock(): ?ContentBlock
	{
		$settings = $this->getAssociatedEntityModel()?->get('SETTINGS');
		if ($settings === null)
		{
			return null;
		}

		$link = trim($settings['LINK'] ?? '');

		if (empty($link))
		{
			return null;
		}

		return (new ContentBlockWithTitle())
			->setFixedWidth(false)
			->setAlignItems('center')
			->setTitle(Loc::getMessage('CRM_TIMELINE_ITEM_TODO_LINK_TITLE'))
			->setContentBlock(
				(new ContentBlock\Link())
					->setValue($link)
					->setAction(
						(new Layout\Action\Redirect(new Uri($link)))
							->addActionParamString('target', '_blank')
					)
			)
			->setInline()
		;
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
		$offsets = (array)($this->getAssociatedEntityModel()?->get('PING_OFFSETS') ?? []);
		if (empty($offsets))
		{
			$offsets = Provider\ToDo\ToDo::getPingOffsets($this->getActivityId());
		}

		return TodoPingSettingsProvider::getValuesByOffsets($offsets);
	}

	public function getPayload(): ?Payload
	{
		$context = $this->getContext();

		return (new Payload())
			->addValueInt('ownerTypeId', $context->getEntityTypeId())
			->addValueInt('ownerId', $context->getEntityId())
			->addValueInt('id', $this->getActivityId())
		;
	}

	protected function getCompleteAction(): Layout\Action\RunAjaxAction
	{
		$action = parent::getCompleteAction();

		$entityTypeId = $this->getContext()->getEntityTypeId();
		$categoryId = $this->getContext()->getEntityCategoryId();

		if ($entityTypeId === CCrmOwnerType::Contact && $categoryId !== 0)
		{
			$section = \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_CATALOG_CONTRACTOR_CONTACT;
		}
		else if ($entityTypeId === CCrmOwnerType::Company && $categoryId !== 0)
		{
			$section = \Bitrix\Crm\Integration\Analytics\Dictionary::SECTION_CATALOG_CONTRACTOR_COMPANY;
		}
		else
		{
			$entityTypeName = \Bitrix\Crm\Integration\Analytics\Dictionary::getAnalyticsEntityType($entityTypeId);

			if ($entityTypeName === null)
			{
				return $action;
			}

			$section = $entityTypeName . '_section';
		}

		$analytics = new Layout\Action\Analytics([
			'tool' => Dictionary::TOOL,
			'category' => Dictionary::OPERATIONS_CATEGORY,
			'event' => Dictionary::COMPLETE_EVENT,
			'type' => Dictionary::TODO_TYPE,
			'c_section' => $section,
			'c_sub_section' => Dictionary::DETAILS_SUB_SECTION,
			'c_element' => Dictionary::COMPLETE_BUTTON_ELEMENT,
			'p1' => \Bitrix\Crm\Integration\Analytics\Dictionary::getCrmMode(),
		]);
		$action->setAnalytics($analytics);

		return $action;
	}

	public function getTags(): ?array
	{
		$tags = [];

		if ($this->hasOverlapEventTag())
		{
			$tags['overlapEvent'] = (new Tag(
				Loc::getMessage('CRM_TIMELINE_ITEM_TODO_OVERLAP_EVENT'),
				Tag::TYPE_PRIMARY
			));
		}

		return $tags;
	}

	private function hasOverlapEventTag()
	{
		return $this->getAssociatedEntityModel()?->get('SETTINGS')['TAGS']['OVERLAP_EVENT'] ?? false;
	}
}
