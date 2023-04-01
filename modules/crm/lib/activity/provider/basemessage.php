<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\Order\Payment;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

/**
 * Class Message
 * @package Bitrix\Crm\Activity\Provider
 */
abstract class BaseMessage extends Base
{
	public const PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT = 'SALESCENTER_PAYMENT_SENT';
	public const PROVIDER_TYPE_SALESCENTER_DELIVERY = 'SALESCENTER_DELIVERY';

	/**
	 * @inheritDoc
	 */
	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function isCompletable()
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public static function generateSubject(
		$providerTypeId = null,
		$direction = \CCrmActivityDirection::Undefined,
		array $replace = null
	)
	{
		if ($direction === \CCrmActivityDirection::Incoming)
		{
			return Loc::getMessage(
				sprintf(
					'CRM_ACTIVITY_PROVIDER_%s_INCOMING',
					static::getLangProviderId()
				),
				$replace
			);
		}
		elseif ($direction === \CCrmActivityDirection::Outgoing)
		{
			return Loc::getMessage(
				sprintf(
					'CRM_ACTIVITY_PROVIDER_%s_OUTGOING',
					static::getLangProviderId()
				),
				$replace
			);
		}

		return parent::generateSubject($providerTypeId, $direction, $replace);
	}

	/**
	 * @inheritDoc
	 */
	public static function renderView(array $activity)
	{
		global $APPLICATION;

		ob_start();

		$APPLICATION->IncludeComponent(
			static::getRenderViewComponentName(), '',
			['ACTIVITY' => $activity]
		);

		return ob_get_clean();
	}

	/**
	 * @inheritDoc
	 */
	public static function getCommunicationType($providerTypeId = null)
	{
		return static::COMMUNICATION_TYPE_PHONE;
	}

	/**
	 * @inheritDoc
	 */
	public static function getSupportedCommunicationStatistics()
	{
		return [CommunicationStatistics::STATISTICS_QUANTITY];
	}

	/**
	 * @inheritDoc
	 */
	public static function getTypeId(array $activity)
	{
		if (
			isset($activity['PROVIDER_TYPE_ID'])
			&& in_array($activity['PROVIDER_TYPE_ID'], static::getAvailableProviderTypeIds(), true)
		)
		{
			return $activity['PROVIDER_TYPE_ID'];
		}

		return static::getDefaultTypeId();
	}

	/**
	 * @return array
	 */
	protected static function getAvailableProviderTypeIds(): array
	{
		return array_column(static::getTypes(), 'PROVIDER_TYPE_ID');
	}

	/**
	 * @inheritDoc
	 */
	public static function getTypes()
	{
		$result = [];

		$result[] = [
			'NAME' => Loc::getMessage(
				sprintf(
					'CRM_ACTIVITY_PROVIDER_%s_NAME',
					static::getLangProviderId()
				)
			),
			'PROVIDER_ID' => static::getId(),
			'PROVIDER_TYPE_ID' => static::getDefaultTypeId(),
			'DIRECTIONS' => [
				\CCrmActivityDirection::Incoming => Loc::getMessage(
					sprintf(
						'CRM_ACTIVITY_PROVIDER_%s_INCOMING',
						static::getLangProviderId()
					)
				),
				\CCrmActivityDirection::Outgoing => Loc::getMessage(
					sprintf(
						'CRM_ACTIVITY_PROVIDER_%s_OUTGOING',
						static::getLangProviderId()
					)
				),
			],
		];

		$availableProviderTypeIds = [
			static::PROVIDER_TYPE_SALESCENTER_PAYMENT_SENT,
			static::PROVIDER_TYPE_SALESCENTER_DELIVERY,
		];
		foreach ($availableProviderTypeIds as $providerTypeId)
		{
			$result[] = [
				'NAME' => Loc::getMessage(
					sprintf(
						'CRM_ACTIVITY_PROVIDER_%s_%s_NAME',
						static::getLangProviderId(),
						$providerTypeId
					)
				),
				'PROVIDER_ID' => static::getId(),
				'PROVIDER_TYPE_ID' => $providerTypeId,
				'DIRECTIONS' => [
					\CCrmActivityDirection::Incoming => Loc::getMessage(
						sprintf(
							'CRM_ACTIVITY_PROVIDER_%s_INCOMING',
							static::getLangProviderId()
						)
					),
					\CCrmActivityDirection::Outgoing => Loc::getMessage(
						sprintf(
							'CRM_ACTIVITY_PROVIDER_%s_OUTGOING',
							static::getLangProviderId()
						)
					),
				],
			];
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public static function getTypesFilterPresets()
	{
		return [
			[
				'NAME' => Loc::getMessage(sprintf(
					'CRM_ACTIVITY_PROVIDER_%s_NAME',
					static::getLangProviderId()
				)),
			]
		];
	}

	/**
	 * @param array $fields
	 * @param bool $checkPerms
	 * @return false|int|mixed|string
	 */
	public static function addActivity(array $fields, bool $checkPerms = true)
	{
		$fields['PROVIDER_ID'] = static::getId();

		if (!isset($fields['PROVIDER_TYPE_ID']))
		{
			$fields['PROVIDER_TYPE_ID'] = static::getTypeId($fields);
		}
		if (!isset($fields['DIRECTION']))
		{
			$fields['DIRECTION'] = \CCrmActivityDirection::Outgoing;
		}
		if (empty($fields['SUBJECT']))
		{
			$fields['SUBJECT'] = static::generateSubject($fields['PROVIDER_TYPE_ID'], $fields['DIRECTION']);
		}
		if (!isset($fields['START_TIME']))
		{
			$fields['START_TIME'] = \ConvertTimeStamp(time() + \CTimeZone::GetOffset(), 'FULL');
		}
		if (!isset($fields['DESCRIPTION_TYPE']))
		{
			$fields['DESCRIPTION_TYPE'] = \CCrmContentType::PlainText;
		}
		if (!isset($fields['COMPLETED']))
		{
			$fields['COMPLETED'] = 'Y';
		}
		if (!isset($fields['RESPONSIBLE_ID']))
		{
			$fields['RESPONSIBLE_ID'] = $fields['AUTHOR_ID'];
		}

		return \CCrmActivity::Add($fields, $checkPerms, true, ['REGISTER_SONET_EVENT' => true]);
	}

	/**
	 * @param Event $event
	 */
	public static function onMessageSent(Event $event)
	{
		/** @var int $id */
		$id = (int)$event->getParameter('ID');

		/** @var array $additionalFields */
		$additionalFields = $event->getParameter('ADDITIONAL_FIELDS');

		if ($id <= 0 || !is_array($additionalFields) || !isset($additionalFields['ACTIVITY_PROVIDER_TYPE_ID']))
		{
			return;
		}

		static::addActivity(
			[
				'PROVIDER_TYPE_ID' => $additionalFields['ACTIVITY_PROVIDER_TYPE_ID'] ?? static::getDefaultTypeId(),
				'AUTHOR_ID' => $additionalFields['ACTIVITY_AUTHOR_ID'],
				'DESCRIPTION' => $additionalFields['ACTIVITY_DESCRIPTION'],
				'ASSOCIATED_ENTITY_ID' => $id,
				'BINDINGS' => $additionalFields['BINDINGS'],
				'COMMUNICATIONS' => [
					[
						'ENTITY_TYPE' => $additionalFields['ENTITY_TYPE'],
						'ENTITY_TYPE_ID' => $additionalFields['ENTITY_TYPE_ID'],
						'ENTITY_ID' => $additionalFields['ENTITY_ID'],
						'TYPE' => \CCrmFieldMulti::PHONE,
						'VALUE' => $additionalFields['MESSAGE_TO']
					]
				],
				'SETTINGS' => [
					'FIELDS' => self::makeActivityFields($additionalFields),
				],
			]
		);
	}

	private static function makeActivityFields(array $additionalFields): array
	{
		$result = [];

		if (
			isset($additionalFields['ENTITIES']['PAYMENT'])
			&& $additionalFields['ENTITIES']['PAYMENT'] instanceof Payment
		)
		{
			$payment = $additionalFields['ENTITIES']['PAYMENT'];

			$result = [
				'ORDER_ID' => $payment->getOrder()->getId(),
				'PAYMENT_ID' => $payment->getId(),
			];
		}

		return $result;
	}

	/**
	 * @return string
	 */
	abstract protected static function getDefaultTypeId(): string;

	/**
	 * @return string
	 */
	abstract protected static function getRenderViewComponentName(): string;

	/**
	 * @return string
	 */
	protected static function getLangProviderId(): string
	{
		return str_replace('CRM_', '', static::getId());
	}

	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new Result();

		if ($action === 'UPDATE' && ($fields['COMPLETED'] ?? 'Y') === 'N')
		{
			$result->addError(new Error(Loc::getMessage('CRM_ACTIVITY_PROVIDER_BASEMESSAGE_CAN_NOT_UNCOMPLETE')));
		}

		return $result;
	}
}
