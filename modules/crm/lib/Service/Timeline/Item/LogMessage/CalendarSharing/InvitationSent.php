<?php

namespace Bitrix\Crm\Service\Timeline\Item\LogMessage\CalendarSharing;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Timeline\Item\Configurable;
use Bitrix\Crm\Service\Timeline\Item\Mixin;
use Bitrix\Crm\Service\Timeline\Layout;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\Client;
use CCrmOwnerType;

class InvitationSent extends Configurable
{
	use Mixin\CalendarSharing\SharingLinkUrlTrait;
	use Mixin\CalendarSharing\ModelDataTrait;
	use Mixin\CalendarSharing\MessageTrait;
	use Mixin\CalendarSharing\MembersBlockTrait;

	public function getType(): string
	{
		return 'CalendarSharingInvitationSent';
	}

	public function getTitle(): ?string
	{
		return $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_INVITATION_SENT_TITLE');
	}

	public function getIconCode(): ?string
	{
		return Layout\Common\Icon::CALENDAR;
	}

	public function getLogo(): ?Layout\Body\Logo
	{
		return Layout\Common\Logo::getInstance(Layout\Common\Logo::CALENDAR_SHARE)
			->createLogo()
		;
	}

	public function getContentBlocks(): ?array
	{
		$communication = [
			'TITLE' => $this->getContactNameFromHistoryModel(),
			'SHOW_URL' => $this->getContactUrlFromHistoryModel(),
			'FORMATTED_VALUE' => $this->getHistoryItemModel()->get('CONTACT_COMMUNICATION'),
		];
		$clientBlockOptions = Client::BLOCK_WITH_FORMATTED_VALUE | Client::BLOCK_WITH_FIXED_TITLE;
		$guestContentBlock = (new Client($communication, $clientBlockOptions))
			->setTitle($this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_GUEST'))
			->build()
		;

		$result = [
			'guest' => $guestContentBlock,
			'communicationChannel' => $this->getCommunicationChannelContentBlock(),
			'accessibilityTitleMobile' => $this->getAccessibilityTitleBlock()->setScopeMobile(),
			'accessibilityMobile' => $this->getSlotsListBlock()->setScopeMobile(),
			'accessibilityWeb' => $this->getAccessibilityContentBlock()->setScopeWeb(),
		];

		$memberIds = $this->getHistoryItemModel()?->get('MEMBER_IDS');
		if (!empty($memberIds))
		{
			$result['membersTitle'] = $this->buildMembersTitleBlock();
			$result['members'] = $this->buildMembersBlock($memberIds);
		}

		return $result;
	}

	private function getContactNameFromHistoryModel(): string
	{
		$contactId = $this->getHistoryItemModel()->get('CONTACT_ID');
		$contactTypeId = $this->getHistoryItemModel()->get('CONTACT_TYPE_ID');

		$result = false;
		if ($contactId && $contactTypeId)
		{
			$contactData = Container::getInstance()
				->getEntityBroker($contactTypeId)
				->getById($contactId)
			;

			if ($contactData)
			{
				if ($contactTypeId === CCrmOwnerType::Contact)
				{
					$result = $contactData->getFullName();
				}
				else if ($contactTypeId === CCrmOwnerType::Company)
				{
					$result = $contactData->getTitle();
				}
			}
		}

		return $result ?: $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_GUEST');
	}

	private function getContactUrlFromHistoryModel(): ?string
	{
		$contactId = $this->getHistoryItemModel()->get('CONTACT_ID');
		$contactTypeId = $this->getHistoryItemModel()->get('CONTACT_TYPE_ID');
		if ($contactId && $contactTypeId)
		{
			return Container::getInstance()
				->getRouter()
				->getItemDetailUrl(
					$this->getHistoryItemModel()->get('CONTACT_TYPE_ID'),
					$this->getHistoryItemModel()->get('CONTACT_ID')
				)
			;
		}

		return null;
	}

	private function getChannelNameFromHistoryModel(): string
	{
		$channelName = $this->getHistoryItemModel()->get('CHANNEL_NAME');

		return $channelName ?? $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_COMMUNICATION_CHANNEL_VALUE');
	}

	public function getAdditionalIconButton(): ?Layout\Footer\IconButton
	{
		$result = null;
		$linkUrl = $this->getSharingLinkUrl($this->getLinkHash());
		if ($linkUrl)
		{
			$result = (new Layout\Footer\IconButton(
				'qr-code',
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_QR_CODE_BUTTON'))
			)
				->setAction(
					(new Layout\Action\JsEvent('CalendarSharingInvitationSent:ShowQr'))
						->addActionParamString('url', $linkUrl)
				)
				->setScopeWeb()
			;
		}

		return $result;
	}

	public function needShowNotes(): bool
	{
		return true;
	}

	private function getCommunicationChannelContentBlock(): Layout\Body\ContentBlock
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_COMMUNICATION_CHANNEL')
			)
			->setContentBlock(
				(new Layout\Body\ContentBlock\Text())
					->setValue($this->getChannelNameFromHistoryModel())
			)
		;
	}

	private function getAccessibilityTitleBlock(): Layout\Body\ContentBlock
	{
		$text = $this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_ACCESSIBILITY');

		return Layout\Body\ContentBlock\ContentBlockFactory::createTitle($text);
	}

	private function getAccessibilityContentBlock(): Layout\Body\ContentBlock
	{
		return (new Layout\Body\ContentBlock\ContentBlockWithTitle())
			->setInline()
			->setTitle(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_ACCESSIBILITY_SHORT')
			)
			->setContentBlock(
				$this->getSlotsListBlock()
			)
		;
	}

	private function getSlotsListBlock(): Layout\Body\ContentBlock\Calendar\SharingSlotsList
	{
		$rule = $this->getLinkRule();

		$slotsListBlock = new Layout\Body\ContentBlock\Calendar\SharingSlotsList();

		foreach ($rule['ranges'] as $range)
		{
			$ruleArray = [
				'from' => $range['from'],
				'to' => $range['to'],
				'weekdays' => $range['weekdays'],
				'weekdaysTitle' => $range['weekdaysTitle'],
				'slotSize' => $rule['slotSize'],
			];
			$slotListItem = (new Layout\Body\ContentBlock\Calendar\SharingSlotsListItem())
				->setRule($ruleArray)
			;

			$slotsListBlock->addListItem($slotListItem);
		}

		return $slotsListBlock;
	}

	public function getButtons(): ?array
	{
		$result = [];
		$linkUrl = $this->getSharingLinkUrl($this->getLinkHash());
		if ($linkUrl)
		{
			$action = new Layout\Action\Redirect($linkUrl);
			if ($linkUrl->getHost())
			{
				$action->addActionParamString('target', '_blank');
			}

			$result['openSlots'] = (new Layout\Footer\Button(
				$this->getMessage('CRM_TIMELINE_CALENDAR_SHARING_OPEN_SLOTS_BUTTON'),
				Layout\Footer\Button::TYPE_SECONDARY)
			)
				->setAction($action)
				->setScopeWeb()
			;
		}

		return $result;
	}
}
