<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Badge;
use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\MessageService\MessageStatus;
use CCrmActivity;
use CCrmActivityType;

Loc::loadMessages(__FILE__);

/**
 * Class Sms
 * @package Bitrix\Crm\Activity\Provider
 */
class Sms extends BaseMessage
{
	public const PROVIDER_TYPE_SMS = 'SMS';

	private const ALLOWED_PROVIDER_TYPES = [
		self::PROVIDER_TYPE_SMS,
		self::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT,
		self::PROVIDER_TYPE_SALESCENTER_DELIVERY,
	];

	/**
	 * @inheritDoc
	 */
	protected static function getDefaultTypeId(): string
	{
		return self::PROVIDER_TYPE_SMS;
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
		$id = (int)$event->getParameter('ID');
		$statusId = (int)$event->getParameter('STATUS_ID');

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
			'@PROVIDER_TYPE_ID' => self::ALLOWED_PROVIDER_TYPES,
			'ASSOCIATED_ENTITY_ID' => $id,
			'CHECK_PERMISSIONS' => 'N',
		])->Fetch();

		return is_array($activity) ? $activity : [];
	}

	/**
	 * @inheritDoc
	 */
	protected static function fetchOriginalMessageFields(int $messageId): array
	{
		if ($messageId <= 0)
		{
			return [];
		}

		$fields = SmsManager::getMessageFields($messageId);
		if ($fields)
		{
			return [
				'ID' => $fields['ID'],
				'SENDER_ID' => $fields['SENDER_ID'],
				'MESSAGE_FROM' => $fields['MESSAGE_FROM'],
				'MESSAGE_TO' => $fields['MESSAGE_TO'],
				'STATUS_ID' => $fields['STATUS_ID'],
				'EXEC_ERROR' => $fields['EXEC_ERROR'],
			];
		}

		return [];
	}

	/**
	 * @inheritDoc
	 */
	protected static function syncActivitySettings(int $messageId, array $activity): void
	{
		if (empty($activity))
		{
			return;
		}

		// update settings field
		$activity['SETTINGS']['ORIGINAL_MESSAGE'] = static::fetchOriginalMessageFields($messageId);

		CCrmActivity::Update((int)$activity['ID'], ['SETTINGS' => $activity['SETTINGS']]);
	}

	/**
	 * @inheritDoc
	 */
	public static function getId()
	{
		return 'CRM_SMS';
	}

	/**
	 * @inheritDoc
	 */
	public static function getName()
	{
		return 'SMS';
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

		if (in_array($statusId, [MessageStatus::ERROR, MessageStatus::EXCEPTION, MessageStatus::UNDELIVERED, MessageStatus::FAILED], true))
		{
			static::bindBadge(
				$activityId,
				Badge\Type\SmsStatus::SENDING_SMS_ERROR_VALUE,
				$bindings
			);
		}
		else if (in_array($statusId, [MessageStatus::SENT, MessageStatus::DELIVERED], true))
		{
			static::unBindBadge($bindings);
		}
	}

	public static function getMessageStatusCode(int $statusId, Event $event): ?int
	{
		if ($statusId === MessageStatus::DELIVERED)
		{
			return static::MESSAGE_SUCCESS;
		}

		if ($statusId === MessageStatus::READ)
		{
			return static::MESSAGE_READ;
		}

		if (in_array($statusId, [MessageStatus::ERROR, MessageStatus::EXCEPTION, MessageStatus::UNDELIVERED, MessageStatus::FAILED], true))
		{
			return static::MESSAGE_FAILURE;
		}

		return null;
	}

	public static function onMessageSent(Event $event): void
	{
		$additionalFields = $event->getParameter('ADDITIONAL_FIELDS') ?? [];
		$providerTypeId = $additionalFields['ACTIVITY_PROVIDER_TYPE_ID'] ?? static::getDefaultTypeId();
		if (!in_array($providerTypeId, self::ALLOWED_PROVIDER_TYPES, true))
		{
			return;
		}

		parent::onMessageSent($event);
	}
}
