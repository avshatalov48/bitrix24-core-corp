<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity;
use Bitrix\Crm\Order\BindingsMaker\ActivityBindingsMaker;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Order;

Loc::loadMessages(__FILE__);

class Payment extends Activity\Provider\Base
{
	private const PROVIDER_TYPE_DEFAULT = 'PAYMENT';

	public static function getId()
	{
		return 'CRM_PAYMENT';
	}

	public static function getTypeId(array $activity)
	{
		return self::PROVIDER_TYPE_DEFAULT;
	}

	public static function getTypes()
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_PAYMENT_TYPE_DEFAULT_NAME'),
				'PROVIDER_ID' => self::getId(),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
			]
		];
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_PAYMENT_NAME');
	}

	public static function getTypeName($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_PAYMENT_TYPE_DEFAULT_NAME');
	}

	public static function getFieldsForEdit(array $activity)
	{
		return [];
	}

	public static function addActivity(Order\Payment $payment): ?int
	{
		$paymentId = $payment->getId();
		if (!$paymentId)
		{
			return null;
		}

		$typeId = self::PROVIDER_TYPE_DEFAULT;
		$associatedEntityId = $paymentId;

		$existingActivity = \CCrmActivity::getList(
			[],
			[
				'=PROVIDER_ID' => self::getId(),
				'=PROVIDER_TYPE_ID' => $typeId,
				'=ASSOCIATED_ENTITY_ID' => $associatedEntityId,
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
			]
		)->fetch();
		if ($existingActivity)
		{
			return null;
		}

		$authorId = $payment->getField('RESPONSIBLE_ID')
			? (int)$payment->getField('RESPONSIBLE_ID')
			: (int)$payment->getField('EMP_RESPONSIBLE_ID');

		$fields = [
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => self::getId(),
			'PROVIDER_TYPE_ID' => $typeId,
			'ASSOCIATED_ENTITY_ID' => $associatedEntityId,
			'SUBJECT' => self::getActivitySubject($payment, $typeId),
			'IS_HANDLEABLE' => 'Y',
			'IS_INCOMING_CHANNEL' => 'Y',
			'COMPLETED' => 'N',
			'STATUS' => \CCrmActivityStatus::Waiting,
			'RESPONSIBLE_ID' => $authorId,
			'PRIORITY' => \CCrmActivityPriority::Medium,
			'AUTHOR_ID' => $authorId,
			'BINDINGS' => ActivityBindingsMaker::makeByPayment($payment),
		];

		$activityId = (int)\CCrmActivity::add($fields, false);

		return $activityId ?? null;
	}

	public static function onPaymentDeleted(int $paymentId): void
	{
		$activitiesList = \CCrmActivity::getList(
			[],
			[
				'=PROVIDER_ID' => self::getId(),
				'=PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_DEFAULT,
				'=ASSOCIATED_ENTITY_ID' => $paymentId,
				'=COMPLETED' => 'N',
				'CHECK_PERMISSIONS' => 'N',
			]
		);
		while ($activity = $activitiesList->fetch())
		{
			\CCrmActivity::Delete($activity['ID'], false);
		}
	}

	private static function getActivitySubject(Order\Payment $payment, string $typeId): string
	{
		$result = (string)self::getTypeName($typeId);

		$paySystemName = null;
		$paySystem = $payment->getPaySystem();
		if ($paySystem)
		{
			$paySystemName = $paySystem->getField('NAME');
		}

		return $paySystemName
			? sprintf('%s: %s', $result, $paySystemName)
			: $result;
	}
}
