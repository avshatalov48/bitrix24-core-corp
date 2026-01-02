<?php

declare(strict_types=1);

namespace Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;

use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Context;
use Bitrix\Crm\Component\EntityDetails\TimelineMenuBar\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Type\DateTime;

class Booking extends Item
{
	private const BOOKING_ADS_OPTION = 'booking_ads';
	private const START_BANNER = 'start_banner';
	private const BEFORE_FIRST_RESOURCE_AHA = 'before_first_resource';
	private const AFTER_FIRST_RESOURCE_AHA = 'after_first_resource';
	private const FIRST_RESOURCE_ADDED_OPTION = 'first_resource_added';

	private const PROMO_PERIOD_EXPIRE_DATE = '01.05.2025';
	private const NEW_PORTAL_START_FROM_DATE = '01.03.2025';

	private int $userId;

	public function __construct(Context $context)
	{
		$this->userId = (int)CurrentUser::get()->getId();

		parent::__construct($context);
	}

	public function getId(): string
	{
		return 'booking';
	}

	public function getName(): string
	{
		return \Bitrix\Main\Localization\Loc::getMessage('CRM_TIMELINE_BOOKING');
	}

	public function isAvailable(): bool
	{
		$validTypes = [\CCrmOwnerType::Deal, \CCrmOwnerType::Contact, \CCrmOwnerType::Company];

		$isValidEntityType = in_array($this->getEntityTypeId(), $validTypes, true);

		return \Bitrix\Main\ModuleManager::isModuleInstalled('booking') && $isValidEntityType;
	}

	public function isNew(): bool
	{
		return true;
	}

	public function prepareSettings(): array
	{
		$entity = [
			'id' => $this->getEntityId(),
			'code' => \CCrmOwnerType::resolveName($this->getEntityTypeId()),
			'module' => 'crm',
		];

		$ahaMoments = [];
		if ($this->shouldShowAha())
		{
			$ahaMoments = $this->getAhaMoments();
		}

		$shouldShowBanner = $this->shouldShowBanner();

		if ($this->getEntityTypeId() === \CCrmOwnerType::Company)
		{
			// pick up the linked contact
			$contacts = Container::getInstance()
				->getFactory($this->getEntityTypeId())
				?->getItem($this->getEntityId(), ['CONTACTS'])
				?->getContactIds()
			;

			if (!empty($contacts))
			{
				return [
					'entities' => [
						[
							'id' => $contacts[0],
							'code' => 'CONTACT',
							'module' => 'crm'
						],
						$entity,
					],
					'ahaMoments' => $ahaMoments,
					'shouldShowBanner' => $shouldShowBanner,
				];
			}
		}

		return [
			'entities' => [
				$entity,
			],
			'ahaMoments' => $ahaMoments,
			'shouldShowBanner' => $shouldShowBanner,
		];
	}

	/**
	 * Returns false if some of global preconditions not satisfied.
	 */
	private function globalShowPreconditions(): bool
	{
		$isNewPortal = !\Bitrix\Crm\Settings\Crm::isPortalCreatedBefore(
			(new DateTime(self::NEW_PORTAL_START_FROM_DATE, 'd.m.Y'))->getTimestamp()
		);
		$promoPeriodExpired = new DateTime() > new DateTime(self::PROMO_PERIOD_EXPIRE_DATE, 'd.m.Y');

		return !$this->isHideAllTours()
			&& !$isNewPortal
			&& !$promoPeriodExpired
		;
	}

	private function shouldShowBanner(): bool
	{
		return
			$this->globalShowPreconditions()
			&& $this->getEntityTypeId() === \CCrmOwnerType::Deal
			&& !$this->isBannerWatched()
		;
	}

	private function shouldShowAha(): bool
	{
		return $this->getEntityTypeId() === \CCrmOwnerType::Deal;
	}

	private function getAhaMoments(): array
	{
		$ahaMomentToShow = [];

		if (
			$this->globalShowPreconditions()
			&& !$this->isFirstResourceAdded()
			&& !$this->isBeforeResourceAhaWatched()
		)
		{
			$ahaMomentToShow[] = self::BEFORE_FIRST_RESOURCE_AHA;
		}

		if (
			$this->isFirstResourceAdded()
			&& !$this->isAfterResourceAhaWatched()
		)
		{
			$ahaMomentToShow[] = self::AFTER_FIRST_RESOURCE_AHA;
		}

		return $ahaMomentToShow;
	}

	private function isFirstResourceAdded(): bool
	{
		return (bool)Option::get(
			moduleId: 'booking',
			name: self::FIRST_RESOURCE_ADDED_OPTION,
			default: 0,
		);
	}

	private function isBannerWatched(): bool
	{
		return $this->getUserOptionBool(self::START_BANNER);
	}

	private function isBeforeResourceAhaWatched(): bool
	{
		return $this->getUserOptionBool(self::BEFORE_FIRST_RESOURCE_AHA);
	}

	private function isAfterResourceAhaWatched(): bool
	{
		return $this->getUserOptionBool(self::AFTER_FIRST_RESOURCE_AHA);
	}

	private function getUserOptionBool(string $name): bool
	{
		$values = \CUserOptions::GetOption(
			category: 'crm',
			name: self::BOOKING_ADS_OPTION,
			default_value: 0,
			user_id: $this->userId,
		);

		return (bool)($values[$name] ?? false);
	}
}
