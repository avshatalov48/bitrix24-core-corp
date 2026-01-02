<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Booking;

use Bitrix\Booking\Internals\Integration\Crm\BookingActivity;
use Bitrix\Crm\Dto\Booking\Booking\BookingFields;
use Bitrix\Crm\Dto\Booking\Booking\BookingFieldsMapper;
use Bitrix\Crm\Dto\Booking\Booking\BookingStatusEnum;
use Bitrix\Crm\Dto\Booking\Message\Message;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Action\Animation;
use Bitrix\Crm\Service\Timeline\Layout\Action\RunAjaxJsonAction;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ContentBlockWithTitle;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Intranet\Settings\Tools\ToolsManager;

class Booking extends Activity
{
	private const ACTIVITY_STATUS_FAILED = 'failed';
	private const ACTIVITY_STATUS_SUCCESS = 'success';
	private const ACTIVITY_STATUS_NOT_CONFIRMED = 'not_confirmed';
	private const ACTIVITY_STATUS_CONFIRMED = 'confirmed';
	private const ACTIVITY_STATUS_LATE = 'late';
	private const ACTIVITY_STATUS_OVERBOOKING = 'overbooking';

	private bool $isBookingLoaded = false;
	private BookingFields|null $bookingModel = null;

	public function __construct(Context $context, Model $model)
	{
		if (Loader::includeModule('booking'))
		{
			$this->isBookingLoaded = true;
		}

		parent::__construct($context, $model);
	}

	protected function getActivityTypeId(): string
	{
		return 'Booking';
	}

	public function getTitle(): ?string
	{
		$activityStatus = $this->getActivityStatus();

		if ($this->isScheduled())
		{
			return match ($activityStatus)
			{
				self::ACTIVITY_STATUS_LATE => Loc::getMessage('CRM_TIMELINE_BOOKING_TITLE_LATE'),
				self::ACTIVITY_STATUS_OVERBOOKING => Loc::getMessage('CRM_TIMELINE_BOOKING_TITLE_OVERBOOKING'),
				default => Loc::getMessage('CRM_TIMELINE_BOOKING_TITLE'),
			};
		}

		return match ($activityStatus)
		{
			self::ACTIVITY_STATUS_FAILED => Loc::getMessage('CRM_TIMELINE_BOOKING_TITLE_NOT_VISITED_MSGVER_1'),
			default => Loc::getMessage('CRM_TIMELINE_BOOKING_TITLE_VISITED_MSGVER_1'),
		};
	}

	public function getIconCode(): ?string
	{
		return Icon::BOOKING;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		$booking = $this->getAssociatedEntityModelFields();
		$bookingDateStart = DateTime::createFromTimestamp($booking->datePeriod->from);

		$logo = (new Layout\Body\CalendarLogo($bookingDateStart))
			->setIconType($this->getIconType())
		;
		if ($additionalIconCode = $this->getAdditionalIconCode())
		{
			$logo->setAdditionalIconCode($additionalIconCode);
		}

		return $logo;
	}

	public function getTags(): ?array
	{
		if (!$this->isScheduled())
		{
			return [];
		}

		$tags = [];

		$statusTag = $this->getStatusTag();
		if ($statusTag)
		{
			$tags['status'] = $statusTag;
		}

		return $tags;
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$bookingStartBlock = $this->buildBookingStartBlock();
		if ($bookingStartBlock)
		{
			$result['bookingStart'] = $bookingStartBlock;
		}

		$primaryResourceBlockWeb = $this->buildPrimaryResourceBlock(ContentBlock::SCOPE_WEB);
		$primaryResourceBlockMob = $this->buildPrimaryResourceBlock(ContentBlock::SCOPE_MOBILE);

		if ($primaryResourceBlockWeb)
		{
			$result['primaryResourceWeb'] = $primaryResourceBlockWeb;
		}

		if ($primaryResourceBlockMob)
		{
			$result['primaryResourceMob'] = $primaryResourceBlockMob;
		}

		$secondaryResourceBlockWeb = $this->buildSecondaryResourceBlock(ContentBlock::SCOPE_WEB);
		$secondaryResourceBlockMob = $this->buildSecondaryResourceBlock(ContentBlock::SCOPE_MOBILE);

		if ($secondaryResourceBlockWeb)
		{
			$result['secondaryResourceWeb'] = $secondaryResourceBlockWeb;
		}

		if ($secondaryResourceBlockMob)
		{
			$result['secondaryResourceMob'] = $secondaryResourceBlockMob;
		}

		if ($noteBlock = $this->buildNoteBlock())
		{
			$result['noteBlock'] = $noteBlock;
		}

		return $result;
	}

	/**
	 * @return array<string, Button>
	 */
	public function getButtons(): array
	{
		if (!$this->isBookingLoaded)
		{
			return [];
		}

		if (!$this->isScheduled())
		{
			return ['openButton' => $this->getOpenBookingButton(Button::TYPE_SECONDARY)];
		}

		return !$this->isBookingStarted()
			? $this->getBeforeStartButtons()
			: $this->getAfterStartButtons()
		;
	}

	private function isBookingStarted(): bool
	{
		$from = $this->getAssociatedEntityModelFields()->datePeriod->from ?? null;
		$currentTimestamp = (new \DateTimeImmutable())->getTimestamp();

		return $currentTimestamp > $from;
	}

	/**
	 * @return array<string, Button>
	 */
	private function getBeforeStartButtons(): array
	{
		if (!$this->isBookingLoaded)
		{
			return [];
		}

		$result['openButton'] = $this->getOpenBookingButton();

		$bookingId = $this->getBookingId();
		$messageMenuItems = $this->getMessageMenuItems(
			$bookingId,
			BookingActivity::getMessageMenuItems($bookingId)
		);

		if (!empty($messageMenuItems))
		{
			$result['messageButton'] = (new Button(
				Loc::getMessage('CRM_TIMELINE_BOOKING_BTN_MESSAGE') ?? '',
				Button::TYPE_SECONDARY
			))
				->setScopeWeb()
				->setMenuItems($messageMenuItems)
			;
		}

		$result['cancelButton'] = (new Button(
			Loc::getMessage('CRM_TIMELINE_BOOKING_BTN_CANCEL') ?? '',
			Button::TYPE_SECONDARY
		))
			->setScopeWeb()
			->setAction(
				(new Layout\Action\RunAjaxJsonAction(
					BookingActivity::getDeleteBookingEndpoint()
				))
					->addActionParamInt('id', $bookingId)
					->setAnimation(Layout\Action\Animation::showLoaderForBlock())
			);

		return $result;
	}

	/**
	 * @return array<string, Button>
	 */
	private function getAfterStartButtons(): array
	{
		$result = [];

		$result['served'] = (new Button(
			Loc::getMessage('CRM_TIMELINE_BOOKING_BTN_SERVED'),
			Button::TYPE_PRIMARY
		))
			->setAction($this->getCompleteAction())->setHideIfReadonly();

		$result['notServed'] = (new Button(
			Loc::getMessage('CRM_TIMELINE_BOOKING_BTN_NOT_SERVED'),
			Button::TYPE_SECONDARY
		))
			->setAction(
				(new Layout\Action\RunAjaxAction(\Bitrix\Crm\Controller\Timeline\Booking::ACTION_NAME_COMPLETE_WITH_STATUS))
					->addActionParamInt('activityId', $this->getActivityId())
					->addActionParamInt('ownerTypeId', $this->getContext()->getEntityTypeId())
					->addActionParamInt('ownerId', $this->getContext()->getEntityId())
					->addActionParamString('status', self::ACTIVITY_STATUS_FAILED)
					->setAnimation(Layout\Action\Animation::disableItem()->setForever()
				)
			);

		$result['openButton'] = $this->getOpenBookingButton(Button::TYPE_SECONDARY);

		return $result;
	}

	private function getOpenBookingButton(string $type = Button::TYPE_PRIMARY): Button
	{
		return (new Button(
			Loc::getMessage('CRM_TIMELINE_BOOKING_BTN_OPEN'),
			$type)
		)
			->setScopeWeb()
			->setAction($this->getOpenBookingAction())
		;
	}

	private function getMessageMenuItems(int $bookingId, array $messageMenuItems): array
	{
		$result = [];

		$isToolAvailable = $this->isToolAvailable();

		foreach ($messageMenuItems as $messageMenuItem)
		{
			$result[$messageMenuItem['code'] ?? ''] = (new MenuItem($messageMenuItem['name'] ?? ''))
				->setAction(
					$this->getMessageMenuItemAction($bookingId, $messageMenuItem, $isToolAvailable)
				);
		}

		return $result;
	}

	private function getMessageMenuItemAction(int $bookingId, array $messageMenuItem, bool $isToolAvailable): Action
	{
		if (!$isToolAvailable)
		{
			return (new Action\JsEvent($this->getType() . ':ShowInfoHelper'))
				->addActionParamString('code', 'limit_automation_off');
		}

		$action = (new RunAjaxJsonAction(BookingActivity::getSendBookingMessageEndpoint()))
			->addActionParamInt('bookingId', $bookingId)
			->setAnimation(Animation::showLoaderForBlock())
		;
		$params = (isset($messageMenuItem['params']) && is_array($messageMenuItem['params']))
			? $messageMenuItem['params']
			: []
		;
		foreach ($params as $paramName => $paramValue)
		{
			$action->addActionParamString($paramName, $paramValue);
		}

		return $action;
	}

	public function needShowNotes(): bool
	{
		return false;
	}

	public function getMenuItems(): array
	{
		$items = parent::getMenuItems();

		unset($items['edit'], $items['view']);

		$menuItems = [
			(new MenuItem(Loc::getMessage('CRM_TIMELINE_BOOKING_MENU_ITEM_CYCLE') ?? ''))
				->setAction(
					(new Action\JsEvent($this->getType() . ':ShowCyclePopup'))
						->addActionParamString('status', $this->getActivityStatus())
					,
				)
			,
		];

		return array_merge($items, $menuItems);
	}

	private function buildBookingStartBlock(): ContentBlockWithTitle|null
	{
		$booking = $this->getAssociatedEntityModelFields();
		$dateStart = $booking->datePeriod->from;
		$dateEnd = $booking->datePeriod->to;

		if (!$dateStart || !$dateEnd)
		{
			return null;
		}

		$titleBlockObject = new ContentBlockWithTitle();
		$titleBlockObject
			->setInline()
			->setTitle(Loc::getMessage('CRM_TIMELINE_BOOKING_CONTENT_BLOCK_START_TIME_TITLE'))
			->setContentBlock(
				(new ContentBlock\EditableDate())
					->setStyle(ContentBlock\EditableDate::STYLE_PILL)
					->setDate(DateTime::createFromTimestamp($dateStart))
					->setDuration($dateEnd - $dateStart)
			);

		return $titleBlockObject;
	}

	private function buildPrimaryResourceBlock(string $scope): ContentBlockWithTitle|null
	{
		$booking = $this->getAssociatedEntityModelFields();
		$primaryResource = $booking->resources[0] ?? null;

		if (!$primaryResource)
		{
			return null;
		}

		$resourceTypeName = $primaryResource->typeName
			?? Loc::getMessage('CRM_TIMELINE_BOOKING_CONTENT_BLOCK_PRIMARY_RESOURCE_TITLE');

		$titleBlockObject = new ContentBlockWithTitle();
		$titleBlockObject
			->setInline()
			->setTitle($resourceTypeName);

		switch ($scope)
		{
			case ContentBlock::SCOPE_WEB:
				$titleBlockObject
					->setScopeWeb()
					->setContentBlock(
						(new ContentBlock\Link())
							->setValue($primaryResource->name)
							->setAction($this->getOpenBookingAction()),
					);
				break;
			case ContentBlock::SCOPE_MOBILE:
				$titleBlockObject
					->setScopeMobile()
					->setContentBlock(
						(new ContentBlock\Text())
							->setValue($primaryResource->name)
					);
				break;
		}

		return $titleBlockObject;
	}

	private function buildSecondaryResourceBlock(string $scope): ContentBlockWithTitle|null
	{
		$booking = $this->getAssociatedEntityModelFields();
		$resources = $booking->resources ?? null;

		if (empty($resources) || count($resources) <= 1)
		{
			return null;
		}

		$secondaryResourceNames = [];

		foreach (array_slice($resources, 1) as $resource)
		{
			$secondaryResourceNames[] = $resource->name;
		}

		$titleBlockObject = new ContentBlockWithTitle();
		$titleBlockObject
			->setInline()
			->setTitle(Loc::getMessage('CRM_TIMELINE_BOOKING_CONTENT_BLOCK_SECONDARY_RESOURCE_TITLE'));

		switch ($scope)
		{
			case ContentBlock::SCOPE_WEB:
				$titleBlockObject
					->setScopeWeb()
					->setContentBlock(
						(new ContentBlock\Link())
							->setValue(implode(', ', $secondaryResourceNames))
							->setAction($this->getOpenBookingAction()),
					);
				break;
			case ContentBlock::SCOPE_MOBILE:
				$titleBlockObject
					->setScopeMobile()
					->setContentBlock(
						(new ContentBlock\Text())
							->setValue(implode(', ', $secondaryResourceNames))
					);
				break;
		}

		return $titleBlockObject;
	}

	private function getOpenBookingAction(): Action\JsEvent
	{
		return (new Action\JsEvent($this->getType() . ':ShowBooking'))
			->addActionParamInt('id', $this->getBookingId());
	}

	private function getBookingId(): int
	{
		$associatedEntityId = $this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID') ?? null;
		if (!$associatedEntityId)
		{
			return 0;
		}

		return (int)$associatedEntityId;
	}

	private function getAssociatedEntityModelFields(): BookingFields|null
	{
		if ($this->bookingModel)
		{
			return $this->bookingModel;
		}

		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');
		$fields = isset($settings['FIELDS']) && is_array($settings['FIELDS']) ? $settings['FIELDS'] : null;
		if (!$fields)
		{
			return null;
		}

		$this->bookingModel = isset($fields['description'])
			// bc for old format
			? BookingFieldsMapper::mapFromBookingArray($fields)
			: BookingFields::mapFromArray($fields)
		;

		return $this->bookingModel;
	}

	private function getBookingCompleteStatus(): string|null
	{
		return $this->getAssociatedEntityModel()->get('SETTINGS')['COMPLETE_STATUS'] ?? null;
	}

	private function getActivityStatus(): string
	{
		// late > overbooking > confirmed > not confirmed
		if ($this->isScheduled())
		{
			if ($this->getStatus() === BookingStatusEnum::DelayedCounterActivated)
			{
				return self::ACTIVITY_STATUS_LATE;
			}

			if ($this->getAssociatedEntityModelFields()->isOverbooking)
			{
				return self::ACTIVITY_STATUS_OVERBOOKING;
			}

			if ($this->getAssociatedEntityModelFields()->isConfirmed)
			{
				return self::ACTIVITY_STATUS_CONFIRMED;
			}

			return self::ACTIVITY_STATUS_NOT_CONFIRMED;
		}

		if ($this->getBookingCompleteStatus() === self::ACTIVITY_STATUS_FAILED)
		{
			return self::ACTIVITY_STATUS_FAILED;
		}

		return self::ACTIVITY_STATUS_SUCCESS;
	}

	private function getIconType(): string
	{
		$status = $this->getActivityStatus();
		if ($this->isScheduled() && $this->getAssociatedEntityModelFields()->isOverbooking)
		{
			// rewrite original status, overbooking status always on top of list for icon if scheduled
			$status = self::ACTIVITY_STATUS_OVERBOOKING;
		}

		return match ($status)
		{
			self::ACTIVITY_STATUS_SUCCESS, self::ACTIVITY_STATUS_CONFIRMED => Layout\Body\Logo::ICON_TYPE_GREEN,
			self::ACTIVITY_STATUS_LATE, self::ACTIVITY_STATUS_FAILED => Layout\Body\Logo::ICON_TYPE_ORANGE_STRIPE,
			self::ACTIVITY_STATUS_OVERBOOKING => Layout\Body\Logo::ICON_TYPE_DARK_ORANGE,
			self::ACTIVITY_STATUS_NOT_CONFIRMED => Layout\Body\Logo::ICON_TYPE_DEFAULT,
			default => Layout\Body\Logo::ICON_TYPE_DEFAULT,
		};
	}

	private function getAdditionalIconCode(): string|null
	{
		return match ($this->getActivityStatus())
		{
			self::ACTIVITY_STATUS_SUCCESS => Layout\Body\Logo::ADDITIONAL_ICON_CODE_DONE,
			self::ACTIVITY_STATUS_FAILED => Layout\Body\Logo::ADDITIONAL_ICON_CODE_CROSS,
			default => null,
		};
	}

	private function buildNoteBlock(): ContentBlock|null
	{
		$note = $this->getAssociatedEntityModelFields()->note;
		if (!$note)
		{
			return null;
		}

		return (new Layout\Body\ContentBlock\EditableDescription())
			->setText($note)
			->setEditable(false)
			->setCopied(true)
			->setHeight(Layout\Body\ContentBlock\EditableDescription::HEIGHT_SHORT)
		;
	}

	private function getMessage(): Message|null
	{
		$message = $this->getAssociatedEntityModel()->get('SETTINGS')['MESSAGE'] ?? null;
		if (!$message)
		{
			return null;
		}

		try
		{
			return Message::mapFromArray($message);
		}
		catch (\Throwable)
		{
			return null;
		}
	}

	private function getStatus(): BookingStatusEnum|null
	{
		$storedStatus = $this->getAssociatedEntityModel()->get('SETTINGS')['STATUS'] ?? null;

		return $storedStatus && is_string($storedStatus) ? BookingStatusEnum::tryFrom($storedStatus) : null;
	}

	private function getStatusTag(): Layout\Header\Tag|null
	{
		return TagMapper::mapFromMessageAndStatus(
			message: $this->getMessage(),
			status: $this->getStatus(),
			statusUpdated: (int)($this->getAssociatedEntityModel()->get('SETTINGS')['STATUS_UPDATED'] ?? 0),
		);
	}

	private function isToolAvailable(): bool
	{
		$result = true;

		if (
			Loader::includeModule('intranet')
			&& !ToolsManager::getInstance()->checkAvailabilityByToolId('booking')
		) {
			$result = false;
		}

		return $result;
	}
}
