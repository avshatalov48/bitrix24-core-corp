<?php

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Calendar;
use Bitrix\Calendar\Sharing\Link\CrmDealLink;

class Sharing extends Item
{
	public const PAY_ATTENTION_TO_NEW_FEATURE_OPTION_NAME = 'payAttentionToNewCrmSharingFeature';
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
			'config' => [
				'link' => $this->prepareLink(),
				'contacts' => $this->getPhoneContacts(),
				'selectedChannelId' => $this->getSelectedChannelId(),
				'communicationChannels' => $this->getCommunicationChannels(),
				'isNotificationsAvailable' => $this->isNotificationsAvailable(),
				'doPayAttentionToNewFeature' => $this->doPayAttentionToNewFeatureAction(),
				'areCommunicationChannelsAvailable' => $this->areCommunicationChannelsAvailable(),
			],
			'isAvailable' => \Bitrix\Crm\Restriction\RestrictionManager::getCalendarSharingRestriction()->hasPermission(),
		];
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

		/** @var CrmDealLink $crmDealLink  */
		$crmDealLink = (new Calendar\Sharing\Link\Factory())->getCrmDealLink($entityId, $ownerId);
		if ($crmDealLink === null)
		{
			$crmDealLink = (new Calendar\Sharing\Link\Factory())->createCrmDealLink($ownerId, $entityId);
		}

		return [
			'hash' => $crmDealLink->getHash(),
			'url' => Calendar\Sharing\Helper::getShortUrl($crmDealLink->getUrl()),
		];
	}

	protected function getPhoneContacts(): array
	{
		$communications = $this->getCommunications();
		return array_map(static function ($communication) {
			return [
				'entityId' => (int)$communication['entityId'],
				'entityTypeId' => (int)$communication['entityTypeId'],
				'name' => $communication['caption'],
				'phone' => $communication['phones'][0]['value'] ?? null,
			];
		}, $communications) ?? [];
	}

	protected function getContact(?int $contactId): ?array
	{
		if (is_null($contactId))
		{
			return null;
		}

		$communications = $this->getCommunications();
		return array_filter($communications, static function ($communication) use ($contactId) {
			return $communication['entityId'] === $contactId;
		})[0];
	}

	protected function getCommunications(): array
	{
		if ($this->communications === null)
		{
			$this->communications = SmsManager::getEntityPhoneCommunications($this->getEntityTypeId(), $this->getEntityId());
		}

		return $this->communications ?? [];
	}

	protected function doPayAttentionToNewFeatureAction(): bool
	{
		$today = new \DateTime();
		$outdated = new \DateTime('2023-09-01');
		if ($today->getTimestamp() > $outdated->getTimestamp())
		{
			return false;
		}

		$calendarOptionName = self::PAY_ATTENTION_TO_NEW_FEATURE_OPTION_NAME;
		$signOptionName = 'create-document-from-deal';

		$calendarOption = \CUserOptions::getOption('crm', $calendarOptionName, 'Y');
		$signOption = \CUserOptions::GetOption('crm', $signOptionName, []);

		return $calendarOption === 'Y' && (isset($signOption['closed']) && $signOption['closed'] === 'Y');
	}

	protected function areCommunicationChannelsAvailable(): bool
	{
		$result = false;

		if ($this->getEntityId() > 0 && $this->getEntityTypeId() > 0)
		{
			$entity = new Crm\ItemIdentifier($this->getEntityTypeId(), $this->getEntityId());
			$result = Crm\Integration\Calendar\Notification\NotificationService::canSendMessage($entity)
				|| Crm\Integration\Calendar\Notification\SmsService::canSendMessage($entity)
			;
		}

		return $result;
	}

	protected function getSelectedChannelId(): ?string
	{
		$result = null;

		if ($this->getEntityId() > 0 && $this->getEntityTypeId() > 0)
		{
			$entity = new Crm\ItemIdentifier($this->getEntityTypeId(), $this->getEntityId());
			$result = Crm\Integration\Calendar\Notification\Manager::getOptimalChannelId($entity);
		}

		return $result;
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

	protected function isNotificationsAvailable(): bool
	{
		return Loader::includeModule('bitrix24')
			&& Application::getInstance()->getLicense()->getRegion() === 'ru'
		;
	}
}