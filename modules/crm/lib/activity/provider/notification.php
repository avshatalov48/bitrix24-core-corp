<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Badge;
use Bitrix\Crm\Integration\NotificationsManager;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Notifications\MessageStatus;
use Bitrix\Notifications\ProviderEnum;
use CCrmActivity;
use CCrmActivityType;

Loc::loadMessages(__FILE__);

/**
 * Class Notification
 * @package Bitrix\Crm\Activity\Provider
 */
class Notification extends BaseMessage
{
	public const PROVIDER_TYPE_NOTIFICATION = 'NOTIFICATION';

	/**
	 * @inheritDoc
	 */
	protected static function getDefaultTypeId(): string
	{
		return self::PROVIDER_TYPE_NOTIFICATION;
	}

	/**
	 * @inheritDoc
	 */
	protected static function getRenderViewComponentName(): string
	{
		return 'bitrix:crm.activity.notification';
	}

	/**
	 * @inheritDoc
	 */
	protected static function fetchEventParams(Event $event): array
	{
		$id = (int)$event->getParameter('ID');
		$statusId = (string)$event->getParameter('STATUS');

		return [$id, $statusId];
	}

	/**
	 * @inheritDoc
	 */
	protected static function fetchActivityByMessageId(int $id): array
	{
		$activity = CCrmActivity::GetList([], [
			'TYPE_ID' => CCrmActivityType::Provider,
			'PROVIDER_ID' => static::getId(),
			'@PROVIDER_TYPE_ID' => [static::PROVIDER_TYPE_NOTIFICATION, static::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT, static::PROVIDER_TYPE_SALESCENTER_DELIVERY],
			'ASSOCIATED_ENTITY_ID' => $id,
			'CHECK_PERMISSIONS' => 'N',
		])->Fetch();

		return is_array($activity) ? $activity : [];
	}

	/**
	 * @inheritDoc
	 */
	protected static function isWhatsappMessage(Event $event): bool
	{
		$history = $event->getParameter('LAST_HISTORY_RECORD') ?? [];
		if (empty($history))
		{
			return false;
		}

		return $history['PROVIDER_CODE'] === ProviderEnum::MFMS_WHATSAPP;
	}

	/**
	 * @inheritDoc
	 */
	public static function getId()
	{
		return 'CRM_NOTIFICATION';
	}

	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return 'NOTIFICATION';
	}

	/**
	 * @inheritdoc
	 */
	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		$messageFields = NotificationsManager::getMessageByInfoId(
			$activityFields['ASSOCIATED_ENTITY_ID'],
			['needHistory' => false]
		)['MESSAGE'];
		if (is_null($messageFields))
		{
			return;
		}

		$statusId = isset($messageFields['STATUS']) ? (string)$messageFields['STATUS'] : null;
		if (is_null($statusId))
		{
			return;
		}

		if (in_array($statusId, [MessageStatus::ERROR, MessageStatus::UNDELIVERED, MessageStatus::FAILED], true))
		{
			static::bindBadge(
				$activityId,
				Badge\Type\SmsStatus::SENDING_NOTIFICATION_ERROR_VALUE,
				$bindings
			);
		}
		else if (in_array($statusId, [MessageStatus::DELIVERED, MessageStatus::READ], true))
		{
			static::unBindBadge($bindings);
		}
	}

	public static function getMessageStatusCode(string $statusId, Event $event = null): ?int
	{
		$isDelivered = $statusId === MessageStatus::DELIVERED;
		// WORKAROUND: to correct update Bitrix24 notification
		$isSmsNotificationDelivered = isset($event)
			&& !self::isWhatsappMessage($event)
			&& $statusId === MessageStatus::IN_DELIVERY;

		if ($isDelivered || $isSmsNotificationDelivered)
		{
			return static::MESSAGE_SUCCESS;
		}

		if ($statusId === MessageStatus::READ)
		{
			return static::MESSAGE_READ;
		}

		if (in_array($statusId, [MessageStatus::UNDELIVERED, MessageStatus::ERROR, MessageStatus::FAILED], true))
		{
			return static::MESSAGE_FAILURE;
		}

		return null;
	}
}
