<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Calendar;
use Bitrix\Calendar\Sharing\Link;
use Bitrix\Crm;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

class Sharing extends Item
{
	protected ?array $communications = null;

	public function getId(): string
	{
		return 'sharing';
	}

	public function getName(): string
	{
		return Loc::getMessage('CRM_TIMELINE_SHARING');
	}

	public function isAvailable(): bool
	{
		$isAvailable = Crm\Integration\Calendar\Helper::isSharingCrmAvaible();

		return
			Loader::includeModule('calendar')
			&& $this->getEntityTypeId() === \CCrmOwnerType::Deal
			&& $isAvailable
		;
	}

	public function hasTariffRestrictions(): bool
	{
		return !\Bitrix\Crm\Restriction\RestrictionManager::getCalendarSharingRestriction()->hasPermission();
	}

	public function prepareSettings(): array
	{
		return [
			'isAvailable' => \Bitrix\Crm\Restriction\RestrictionManager::getCalendarSharingRestriction()->hasPermission(),
		];
	}

	public function getConfig(): array
	{
		if (!$this->isAvailable())
		{
			return [];
		}

		$settings = \CCalendar::GetSettings();
		$userId = $this->getUserId();

		return [
			'config' => [
				'isResponsible' => $this->isResponsible(),
				'link' => $this->prepareLink(),
				'selectedChannelId' => $this->getSelectedChannelId(),
				'communicationChannels' => $this->getCommunicationChannels(),
				'contacts' => $this->getContacts(),
				'isNotificationsAvailable' => $this->isNotificationsAvailable(),
				'areCommunicationChannelsAvailable' => $this->areCommunicationChannelsAvailable(),
				'calendarSettings' => [
					'weekHolidays' => $settings['week_holidays'],
					'weekStart' => \CCalendar::GetWeekStart(),
					'workTimeStart' => $settings['work_time_start'],
					'workTimeEnd' => $settings['work_time_end'],
				],
				'userInfo' => [
					'id' => $userId,
					'name' => \CCalendar::GetUserName($userId),
					'avatar' => \CCalendar::GetUserAvatarSrc($userId),
				],
			],
			'isAvailable' => \Bitrix\Crm\Restriction\RestrictionManager::getCalendarSharingRestriction()->hasPermission(),
		];
	}

	protected function isResponsible(): bool
	{
		return $this->getAssignedId() === $this->getUserId();
	}

	protected function getAssignedId(): ?int
	{
		$entityBroker = Container::getInstance()->getEntityBroker($this->getEntityTypeId());
		if (!$entityBroker)
		{
			return null;
		}

		$entity = $entityBroker->getById($this->getEntityId());
		if (!$entity)
		{
			return null;
		}

		return $entity->getAssignedById();
	}

	protected function prepareLink(): array
	{
		if (!Loader::includeModule('calendar'))
		{
			return [];
		}

		$entityId = $this->getEntityId();

		$broker = Container::getInstance()->getEntityBroker($this->getEntityTypeId());
		if (!$broker)
		{
			return [];
		}

		$deal = $broker->getById($this->getEntityId());
		if (!$deal)
		{
			return [];
		}

		$ownerId = $deal->getAssignedById();

		/** @var Link\CrmDealLink $crmDealLink  */
		$crmDealLink = (new Calendar\Sharing\Link\Factory())->getCrmDealLink($entityId, $ownerId);
		if ($crmDealLink === null)
		{
			$crmDealLink = (new Calendar\Sharing\Link\Factory())->createCrmDealLink($ownerId, $entityId);
		}

		return [
			'hash' => $crmDealLink->getHash(),
			'url' => Calendar\Sharing\Helper::getShortUrl($crmDealLink->getUrl()),
			'rule' => (new Link\Rule\Mapper())->convertToArray($crmDealLink->getSharingRule()),
		];
	}

	protected function areCommunicationChannelsAvailable(): bool
	{
		$result = false;

		if ($this->getEntityId() > 0 && $this->getEntityTypeId() > 0)
		{
			$entity = new Crm\ItemIdentifier($this->getEntityTypeId(), $this->getEntityId());
			$result = Crm\Integration\Calendar\Notification\NotificationService::canSendMessage($entity)
				|| Crm\Integration\Calendar\Notification\SmsService::canSendMessage($entity)
				|| Crm\Integration\Calendar\Notification\MailService::canSendMessage($entity)
			;
		}

		return $result;
	}

	protected function getSelectedChannelId(): ?string
	{
		$result = null;

		$lastSentCrmDealLink = $this->getLastSentCrmDealLink();
		if ($lastSentCrmDealLink !== null && !empty($lastSentCrmDealLink->getChannelId()))
		{
			return $lastSentCrmDealLink->getChannelId();
		}

		if ($this->getEntityId() > 0 && $this->getEntityTypeId() > 0)
		{
			$entity = new Crm\ItemIdentifier($this->getEntityTypeId(), $this->getEntityId());
			$result = Crm\Integration\Calendar\Notification\Manager::getOptimalChannelId($entity, $this->getUserId());
		}

		return $result;
	}

	protected function getLastSentCrmDealLink(): ?Link\CrmDealLink
	{
		$broker = Container::getInstance()->getEntityBroker($this->getEntityTypeId());
		if (!$broker)
		{
			return null;
		}

		$deal = $broker->getById($this->getEntityId());
		if (!$deal)
		{
			return null;
		}

		$ownerId = $deal->getAssignedById();

		/** @var Link\CrmDealLink $lastSentCrmDealLink */
		$lastSentCrmDealLink = (new Calendar\Sharing\Link\Factory())->getLastSentCrmDealLink($ownerId);

		return $lastSentCrmDealLink;
	}

	protected function getCommunicationChannels(): array
	{
		$result = [];

		if ($this->getEntityId() > 0 && $this->getEntityTypeId() > 0)
		{
			$entity = new Crm\ItemIdentifier($this->getEntityTypeId(), $this->getEntityId());
			$result = Crm\Integration\Calendar\Notification\Manager::getCommunicationChannels($entity);
		}

		return $result;
	}

	protected function getContacts(): array
	{
		$result = [];

		if ($this->getEntityId() > 0 && $this->getEntityTypeId() > 0)
		{
			$entity = new Crm\ItemIdentifier($this->getEntityTypeId(), $this->getEntityId());
			$result = Crm\Integration\Calendar\Notification\Manager::getContacts($entity);
		}

		return $result;
	}

	protected function isNotificationsAvailable(): bool
	{
		return Loader::includeModule('bitrix24') && $this->context->getRegion() === 'ru';
	}

	protected function getUserId(): int
	{
		return (new Context())->getUserId();
	}
}
