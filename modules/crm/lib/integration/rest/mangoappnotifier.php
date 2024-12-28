<?php

namespace Bitrix\Crm\Integration\Rest;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Application;
use Bitrix\Crm\Service\Container;

class MangoAppNotifier
{
	public function __construct(private ItemIdentifier $element)
	{
	}

	public function needShow(): bool
	{
		if (!Loader::includeModule('rest') || !Loader::includeModule('bitrix24'))
		{
			return false;
		}
		if (\CBitrix24::getPortalZone() !== 'ru')
		{
			return false;
		}
		if (!defined('LANGUAGE_ID') || LANGUAGE_ID !== 'ru')
		{
			return false;
		}
		if (Option::get('crm', 'mango_notification_skip_v2', false) === 'Y')
		{
			return false;
		}

		$now = time();
		if ($now > $this->getEndNotificationTs())
		{
			return false;
		}
		if ($now < $this->getStartNotificationTs())
		{
			return false;
		}
		if ($now < $this->getNotificationSkippedTo())
		{
			return false;
		}
		if ($this->isSubscriptionActive())
		{
			return false;
		}

		return ($this->getAppStatus() === \Bitrix\Rest\AppTable::STATUS_FREE);
	}

	public function setSkippedToNextDay(): int
	{
		$secondsToNextDayInUserTimezone = (new DateTime())->toUserTime()->add('+1day')->setTime(0,0,0)->getTimestamp() - (new DateTime())->toUserTime()->getTimestamp();

		return (int)\CUserOptions::SetOption('crm', 'mango_notification_skip_to_v2', ['value' => time() + $secondsToNextDayInUserTimezone]);
	}

	public function getNextPopupTypeTs(): int
	{
		$now = time();
		if ($now > $this->getPopupType4Ts())
		{
			return (new \Bitrix\Main\Type\Date('31.01.2025', 'd.m.Y'))->getTimestamp(); // forever
		}
		if ($now > $this->getPopupType3Ts())
		{
			return $this->getPopupType4Ts();
		}
		if ($now > $this->getPopupType2Ts())
		{
			return $this->getPopupType3Ts();
		}

		return $this->getPopupType2Ts();
	}

	public function getPopupType(): int
	{
		$now = time();
		if ($now > $this->getPopupType4Ts())
		{
			return 4;
		}
		if ($now > $this->getPopupType3Ts())
		{
			return 3;
		}
		if ($now > $this->getPopupType2Ts())
		{
			return 2;
		}

		return 1;
	}

	public function getCallTimelineId(): ?string
	{
		$activity = ActivityTable::query()
			->setSelect(['ID', 'COMPLETED'])
			->where('TYPE_ID', \CCrmActivityType::Call)
			->where('BINDINGS.OWNER_ID', $this->element->getEntityId())
			->where('BINDINGS.OWNER_TYPE_ID',  $this->element->getEntityTypeId())
			->setLimit(1)
			->setOrder(['ID' => 'DESC'])
			->fetch();

		if (!$activity)
		{
			return null;
		}
		if ($activity['COMPLETED'] === 'Y')
		{
			$timelineItem = TimelineTable::query()
				->setSelect(['ID'])
				->where('ASSOCIATED_ENTITY_TYPE_ID', \CCrmOwnerType::Activity)
				->where('ASSOCIATED_ENTITY_ID', $activity['ID'])
				->where('TYPE_ID', TimelineType::ACTIVITY)
				->where('TYPE_CATEGORY_ID', \CCrmActivityType::Call)
				->setLimit(1)
				->setOrder(['ID' => 'DESC'])
				->fetch();

			return $timelineItem ? (string)$timelineItem['ID'] : null;
		}

		return 'ACTIVITY_' . $activity['ID'];
	}


	public function sendAnalyticsEvent(): void
	{
		$host = Application::getInstance()->getContext()->getServer()->getHttpHost();
		$user = Container::getInstance()->getContext()->getUserId();
		$itemId =  $this->element->getHash();

		AddMessage2Log("crm.mango_notification.shown for user {$user} in {$itemId} at {$host}", 'crm');
	}

	private function getAppStatus(): ?string
	{
		$result = \Bitrix\Rest\AppTable::query()->setSelect(['ID', 'STATUS'])
			->where('CODE', 'mangotelecom.vats')
			->where('ACTIVE',\Bitrix\Rest\AppTable::ACTIVE)
			->exec()
			->fetch();

		return $result ? $result['STATUS'] : null;
	}

	private function getNotificationSkippedTo(): int
	{
		$value = \CUserOptions::GetOption('crm', 'mango_notification_skip_to_v2', ['value' => 0]);

		return is_array($value) ? ($value['value'] ?? 0) : 0;
	}

	private function getPopupType4Ts(): int
	{
		return Option::get('crm', 'mango_notification_type_4_ts_v2', (new \Bitrix\Main\Type\Date('23.12.2024', 'd.m.Y'))->getTimestamp());
	}

	private function getPopupType3Ts(): int
	{
		return Option::get('crm', 'mango_notification_type_3_ts_v2', (new \Bitrix\Main\Type\Date('16.12.2024', 'd.m.Y'))->getTimestamp());
	}

	private function getPopupType2Ts(): int
	{
		return Option::get('crm', 'mango_notification_type_2_ts_v2', (new \Bitrix\Main\Type\Date('09.12.2024', 'd.m.Y'))->getTimestamp());
	}

	private function getStartNotificationTs(): int
	{
		return Option::get('crm', 'mango_notification_start_ts_v2', (new \Bitrix\Main\Type\Date('02.12.2024', 'd.m.Y'))->getTimestamp());
	}

	private function getEndNotificationTs(): int
	{
		return Option::get('crm', 'mango_notification_end_ts_v2', (new \Bitrix\Main\Type\Date('30.12.2024', 'd.m.Y'))->getTimestamp());
	}

	private function isSubscriptionActive(): bool
	{
		$subscriptionOptionCode = 'mango_notification_subscr_available_v2';

		if (Option::get('crm', $subscriptionOptionCode, false) === 'Y')
		{
			return true;
		}
		if (\Bitrix\Rest\Marketplace\Client::isSubscriptionAvailable())
		{
			Option::set('crm', $subscriptionOptionCode, 'Y');

			return true;
		}

		return false;
	}
}
