<?php

declare(strict_types=1);

namespace Bitrix\Crm\Service\Timeline\Item\Activity\Booking;

use Bitrix\Crm\Dto\Booking\Client;
use Bitrix\Crm\Dto\Booking\WaitListItem\WaitListItemFields;
use Bitrix\Crm\Dto\Booking\WaitListItem\WaitListItemFieldsMapper;
use Bitrix\Crm\Service\Timeline\Item\Activity;
use Bitrix\Crm\Service\Timeline\Item\Mixin\CalendarSharing\ContactTrait;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Common\Icon;
use Bitrix\Crm\Service\Timeline\Layout\Footer\Button;
use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;
use Bitrix\Main\Localization\Loc;

class WaitListItem extends Activity
{
	use ContactTrait;

	private WaitListItemFields|null $waitListItemModel = null;

	protected function getActivityTypeId(): string
	{
		return 'WaitListItem';
	}

	public function getTitle(): ?string
	{
		return match ((int)$this->getAssociatedEntityModel()?->get('STATUS'))
		{
			\CCrmActivityStatus::Completed, \CCrmActivityStatus::AutoCompleted => Loc::getMessage('CRM_TIMELINE_WAIT_LIST_ITEM_TITLE_COMPLETED'),
			default => Loc::getMessage('CRM_TIMELINE_WAIT_LIST_ITEM_TITLE'),
		};
	}

	public function getIconCode(): ?string
	{
		return Icon::BOOKING;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return (new Layout\Body\Logo(Layout\Common\Logo::BOOKING_WAIT_LIST_ITEM))
			->setIconType(Layout\Body\Logo::ICON_TYPE_PALE_BLUE)
		;
	}

	public function getTags(): ?array
	{
		return [];
	}

	public function getContentBlocks(): ?array
	{
		$result = [];

		$clientBlock = $this->createClientBlock($this->getAssociatedEntityModelFields()->clients);
		if ($clientBlock)
		{
			$result['clientBlock'] = $clientBlock;
		}

		if ($noteBlock = $this->buildNoteBlock())
		{
			$result['noteBlock'] = $noteBlock;
		}

		return $result;
	}

	public function getButtons(): array
	{
		if (!$this->isScheduled())
		{
			return [];
		}

		$openButton = new Button(
			title: Loc::getMessage('CRM_TIMELINE_WAIT_LIST_ITEM_BTN_OPEN') ?? '',
			type: Button::TYPE_PRIMARY,
		);
		$openButton->setScopeWeb();
		$openButton->setAction($this->getOpenWaitListItemAction());
		$buttons = [
			'openButton' => $openButton,
		];

		$deleteButton = new Button(
			title: Loc::getMessage('CRM_TIMELINE_WAIT_LIST_ITEM_BTN_DELETE') ?? '',
			type: Button::TYPE_SECONDARY,
		);
		$deleteButton->setAction($this->getDeleteWaitListItemAction());
		$buttons['deleteButton'] = $deleteButton;

		return $buttons;
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
			(new MenuItem(Loc::getMessage('CRM_TIMELINE_WAIT_LIST_ITEM_MENU_ITEM_CYCLE')))
				->setAction(
					(new Action\JsEvent($this->getType() . ':ShowCyclePopup'))
						->addActionParamString('status', 'waitlist')
					,
				)
			,
		];

		return array_merge($items, $menuItems);
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

	/**
	 * @param Client[]|null $clients
	 * @return ContentBlock|null
	 */
	private function createClientBlock(array|null $clients): ContentBlock|null
	{
		if (empty($clients))
		{
			return null;
		}

		$client = $clients[0];

		if ($client->typeModule !== 'crm')
		{
			return null;
		}

		$contactTypeName = $client->typeCode ?? null;
		if (!in_array($contactTypeName, [\CCrmOwnerType::ContactName, \CCrmOwnerType::CompanyName], true))
		{
			return null;
		}
		$contactTypeId = \CCrmOwnerType::ResolveID($contactTypeName);

		$contactId = $client->id ?? null;
		if (!$contactId)
		{
			return null;
		}

		$contactName = $this->getContactName($contactTypeId, $contactId);
		$contactUrl = $this->getContactUrl($contactTypeId, $contactId);

		return (new ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setAlignItems('center')
			->setTitle(
				Loc::getMessage('CRM_TIMELINE_WAIT_LIST_ITEM_CLIENT')
			)
			->setContentBlock(
				ContentBlock\ContentBlockFactory::createTextOrLink(
					$contactName, $contactUrl ? new Layout\Action\Redirect($contactUrl) : null
				)
			)
		;
	}

	private function getOpenWaitListItemAction(): Action\JsEvent
	{
		return (new Action\JsEvent($this->getType() . ':ShowWaitListItem'))
			->addActionParamInt('id', $this->getWaitListItemId())
		;
	}

	private function getDeleteWaitListItemAction(): Action\RunAjaxJsonAction
	{
		return (new Action\RunAjaxJsonAction('booking.api_v1.WaitListItem.delete'))
			->addActionParamInt('id', $this->getWaitListItemId())
		;
	}

	private function getWaitListItemId(): ?int
	{
		$associatedEntityId = $this->getAssociatedEntityModel()->get('ASSOCIATED_ENTITY_ID') ?? null;
		if (!$associatedEntityId)
		{
			return null;
		}

		return (int)$associatedEntityId;
	}

	private function getAssociatedEntityModelFields(): WaitListItemFields|null
	{
		if ($this->waitListItemModel)
		{
			return $this->waitListItemModel;
		}

		$settings = $this->getAssociatedEntityModel()->get('SETTINGS');
		$fields = isset($settings['FIELDS']) && is_array($settings['FIELDS']) ? $settings['FIELDS'] : null;
		if (!$fields)
		{
			return null;
		}

		// new wait list item structure not have updatedAt field, but older has
		$this->waitListItemModel = isset($fields['updatedAt'])
			// bc for old format
			? WaitListItemFieldsMapper::mapFromWaitListItemArray($fields)
			: WaitListItemFields::mapFromArray($fields)
		;

		return $this->waitListItemModel;
	}
}
