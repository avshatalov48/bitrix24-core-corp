<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Main;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;

Loc::loadMessages(__FILE__);

class StoreDocument extends Base
{
	private const PROVIDER_TYPE_DEFAULT = 'STORE_DOCUMENT';
	public const PROVIDER_TYPE_ID_PRODUCT = 'STORE_DOCUMENT_PRODUCT';
	public const PROVIDER_TYPE_ID_SERVICE = 'STORE_DOCUMENT_SERVICE';

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_STORE_DOCUMENT_NAME');
	}

	public static function getId()
	{
		return self::PROVIDER_TYPE_DEFAULT;
	}

	/**
	 * @inheritdoc
	 */
	public static function getTypeId(array $activity)
	{
		if (isset($activity['PROVIDER_TYPE_ID']) && $activity['PROVIDER_TYPE_ID'] === self::PROVIDER_TYPE_ID_SERVICE)
		{
			return self::PROVIDER_TYPE_ID_SERVICE;
		}

		return self::PROVIDER_TYPE_ID_PRODUCT;
	}

	/**
	 * Checks provider status.
	 * @return bool
	 */
	public static function isActive()
	{
		return true;
	}

	public static function addProductActivity(int $dealId): ?int
	{
		return self::internalAdd(
			$dealId,
			[
				'SUBJECT' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_STORE_DOCUMENT_PRODUCT_SUBJECT'),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_PRODUCT,
			]
		);
	}

	public static function addServiceActivity(int $dealId): ?int
	{
		return self::internalAdd(
			$dealId,
			[
				'SUBJECT' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_STORE_DOCUMENT_SERVICE_SUBJECT'),
				'PROVIDER_TYPE_ID' => self::PROVIDER_TYPE_ID_SERVICE,
			]
		);
	}

	private static function internalAdd(int $dealId, $fields): ?int
	{
		$deal = \CCrmDeal::GetByID($dealId, false);
		if (!$deal)
		{
			return null;
		}

		$authorId = $responsibleId = self::getResponsibleId($deal);
		$ownerTypeId = \CCrmOwnerType::Deal;
		$ownerId = $dealId;

		$startTime = new Type\DateTime();
		$endTime = $deadlineTime = self::getDeadlineTime();

		$fields =
			[
				'TYPE_ID' => \CCrmActivityType::Provider,
				'PROVIDER_ID' => self::PROVIDER_TYPE_DEFAULT,
				'IS_HANDLEABLE' => 'Y',
				'COMPLETED' => 'N',
				'STATUS' => \CCrmActivityStatus::Waiting,
				'RESPONSIBLE_ID' => $responsibleId,
				'PRIORITY' => \CCrmActivityPriority::Medium,
				'AUTHOR_ID' => $authorId,
				'START_TIME' => $startTime,
				'END_TIME' => $endTime,
				'DEADLINE' => $deadlineTime,
				'OWNER_ID' => $ownerId,
				'OWNER_TYPE_ID' => $ownerTypeId,
				'ASSOCIATED_ENTITY_ID' => $dealId,
			]
			+ $fields
		;

		$activityId = (int)\CCrmActivity::add($fields, false);
		return $activityId > 0 ? $activityId : null;
	}

	/**
	 * @param string $action Action ADD or UPDATE.
	 * @param array $fields Activity fields.
	 * @param int $id Activity ID.
	 * @param null|array $params Additional parameters.
	 * @return Main\Result Check fields result.
	 */
	public static function checkFields($action, &$fields, $id, $params = null)
	{
		$result = new Main\Result();

		$previousFields = (isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS']))
			? $params['PREVIOUS_FIELDS']
			: []
		;

		if (
			$action === self::ACTION_UPDATE
			&& isset($fields['COMPLETED'])
			&& $fields['COMPLETED'] === 'Y'
			&& empty($previousFields['END_TIME'])
		)
		{
			$end = new Main\Type\DateTime();
			$fields['END_TIME'] = $end->toString();
		}

		if (isset($fields['END_TIME']))
		{
			if($fields['END_TIME'] !== '')
			{
				$fields['DEADLINE'] = $fields['END_TIME'];
			}
			else
			{
				$fields['~DEADLINE'] = \CCrmDateTimeHelper::GetMaxDatabaseDate();
			}
		}

		return $result;
	}

	private static function getResponsibleId(array $dealFields): int
	{
		$responsibleId = $dealFields['ASSIGNED_BY_ID'];
		if (!$responsibleId)
		{
			$responsibleId = Crm\Settings\OrderSettings::getCurrent()->getDefaultResponsibleId();
		}

		return (int)$responsibleId;
	}

	private static function getDeadlineTime(): Type\DateTime
	{
		$currentTime = new Type\DateTime();
		$deadlineTime = (new Type\DateTime())->setTime(19, 0, 0);

		if ($deadlineTime->getTimestamp() < $currentTime->getTimestamp())
		{
			$deadlineTime->add('+1 day');
		}

		return $deadlineTime;
	}

	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}
}
