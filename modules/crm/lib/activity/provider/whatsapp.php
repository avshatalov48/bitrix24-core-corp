<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\Provider\Sms\SenderExtra;
use Bitrix\Crm\Badge;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\MessageService\MessageStatus;
use CCrmActivity;
use CCrmActivityType;


Loc::loadMessages(__FILE__);

class WhatsApp extends BaseMessage
{
	public const PROVIDER_TYPE_WHATSAPP = 'WHATSAPP';

	/**
	 * @inheritDoc
	 */
	protected static function getDefaultTypeId(): string
	{
		return self::PROVIDER_TYPE_WHATSAPP;
	}

	/**
	 * @inheritDoc
	 */
	protected static function getRenderViewComponentName(): string
	{
		return 'bitrix:crm.activity.sms';
	}

	/**
	 * @inheritDoc
	 */
	protected static function fetchEventParams(Event $event): array
	{
		return Sms::fetchEventParams($event);
	}

	/**
	 * @inheritDoc
	 */
	protected static function fetchActivityByMessageId(int $id): array
	{
		$activity = CCrmActivity::GetList([], [
			'TYPE_ID' => CCrmActivityType::Provider,
			'PROVIDER_ID' => static::getId(),
			'@PROVIDER_TYPE_ID' => [static::PROVIDER_TYPE_WHATSAPP, static::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT, static::PROVIDER_TYPE_SALESCENTER_DELIVERY],
			'ASSOCIATED_ENTITY_ID' => $id,
			'CHECK_PERMISSIONS' => 'N',
		])?->Fetch();

		return is_array($activity) ? $activity : [];
	}

	/**
	 * @inheritDoc
	 */
	protected static function fetchOriginalMessageFields(int $messageId): array
	{
		return Sms::fetchOriginalMessageFields($messageId);
	}

	/**
	 * @inheritDoc
	 */
	protected static function syncActivitySettings(int $messageId, array $activity): void
	{
		Sms::syncActivitySettings($messageId, $activity);
	}

	public static function getId()
	{
		return 'CRM_WHATSAPP';
	}

	public static function getName()
	{
		return 'WHATSAPP';
	}

	/**
	 * @inheritdoc
	 */
	public static function syncBadges(int $activityId, array $activityFields, array $bindings): void
	{
		$smsFields = SmsManager::getMessageFields($activityFields['ASSOCIATED_ENTITY_ID']);
		if (!$smsFields)
		{
			return;
		}

		$statusId = isset($smsFields['STATUS_ID']) ? (int)$smsFields['STATUS_ID'] : null;
		if (is_null($statusId))
		{
			return;
		}

		$settings = $activityFields['SETTINGS'] ?? [];
		$messageTag = $settings['ASSOCIATED_MESSAGE_TAG'] ?? null;
		if (
			$messageTag === SenderExtra::SENT_MESSAGE_TAG_GROUP_WHATSAPP_MESSAGE &&
			in_array($statusId, [MessageStatus::SENT, MessageStatus::DELIVERED], true)
		)
		{
			static::bindBadge(
				$activityId,
				Badge\Type\SmsStatus::SENDING_SMS_SUCCESS_VALUE,
				$bindings
			);

			return;
		}

		Sms::syncBadges($activityId, $activityFields, $bindings);
	}

	public static function getMessageStatusCode(int $statusId, Event $event): ?int
	{
		return Sms::getMessageStatusCode($statusId, $event);
	}

	public static function onMessageSent(Event $event): void
	{
		$additionalFields = $event->getParameter('ADDITIONAL_FIELDS') ?? [];
		$providerTypeId = $additionalFields['ACTIVITY_PROVIDER_TYPE_ID'] ?? static::getDefaultTypeId();
		if ($providerTypeId !== static::PROVIDER_TYPE_WHATSAPP)
		{
			return;
		}

		parent::onMessageSent($event);
	}
}
