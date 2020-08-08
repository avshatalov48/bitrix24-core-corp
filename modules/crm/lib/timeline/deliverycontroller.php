<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Main;

/**
 * Class DeliveryController
 * @package Bitrix\Crm\Timeline
 */
class DeliveryController extends EntityController
{
	/** @var DeliveryController|null */
	protected static $instance = null;

	/**
	 * @return DeliveryController
	 */
	public static function getInstance()
	{
		if(self::$instance === null)
		{
			self::$instance = new DeliveryController();
		}
		return self::$instance;
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @return int|null
	 * @throws Main\ArgumentException
	 */
	public function createTaxiEstimationReceivedHistoryMessage($ownerId, array $params)
	{
		return $this->createTaxiHistoryMessage(
			$ownerId,
			DeliveryCategoryType::TAXI_ESTIMATION_REQUEST,
			$params
		);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @return int|null
	 * @throws Main\ArgumentException
	 */
	public function createTaxiCallHistoryMessage($ownerId, array $params)
	{
		return $this->createTaxiHistoryMessage(
			$ownerId,
			DeliveryCategoryType::TAXI_CALL_REQUEST,
			$params
		);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @return int|null
	 * @throws Main\ArgumentException
	 */
	public function createTaxiCancelledByManagerMessage($ownerId, array $params)
	{
		return $this->createTaxiHistoryMessage(
			$ownerId,
			DeliveryCategoryType::TAXI_CANCELLED_BY_MANAGER,
			$params
		);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @return int|null
	 * @throws Main\ArgumentException
	 */
	public function createTaxiCancelledByDriverMessage($ownerId, array $params)
	{
		return $this->createTaxiHistoryMessage(
			$ownerId,
			DeliveryCategoryType::TAXI_CANCELLED_BY_DRIVER,
			$params
		);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @return int|null
	 * @throws Main\ArgumentException
	 */
	public function createTaxiReturnedFinish($ownerId, array $params)
	{
		return $this->createTaxiHistoryMessage(
			$ownerId,
			DeliveryCategoryType::TAXI_RETURNED_FINISH,
			$params
		);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @return int|null
	 * @throws Main\ArgumentException
	 */
	public function createTaxiPerformerNotFoundMessage($ownerId, array $params)
	{
		return $this->createTaxiHistoryMessage(
			$ownerId,
			DeliveryCategoryType::TAXI_PERFORMER_NOT_FOUND,
			$params
		);
	}

	/**
	 * @param $ownerId
	 * @param array $params
	 * @return int|null
	 * @throws Main\ArgumentException
	 */
	public function createTaxiSmsProviderIssueMessage($ownerId, array $params)
	{
		return $this->createTaxiHistoryMessage(
			$ownerId,
			DeliveryCategoryType::TAXI_SMS_PROVIDER_ISSUE,
			$params
		);
	}

	/**
	 * @param $ownerId
	 * @param int $typeCategoryId
	 * @param array $params
	 * @return int|null
	 * @throws Main\ArgumentException
	 */
	private function createTaxiHistoryMessage($ownerId, int $typeCategoryId, array $params)
	{
		if ($ownerId <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.');
		}

		$settings = $params['SETTINGS'] ?? [];
		$bindings = $params['BINDINGS'] ?? [];

		$result = TimelineTable::add(
			array(
				'TYPE_ID' => TimelineType::DELIVERY,
				'TYPE_CATEGORY_ID' => $typeCategoryId,
				'CREATED' => new Main\Type\DateTime(),
				'AUTHOR_ID' => isset($params['AUTHOR_ID']) ? $params['AUTHOR_ID'] : \CCrmSecurityHelper::GetCurrentUserID(),
				'SETTINGS' => $settings,
				'ASSOCIATED_ENTITY_TYPE_ID' => \CCrmOwnerType::OrderShipment,
				'ASSOCIATED_ENTITY_ID' => $ownerId
			)
		);

		if(!$result->isSuccess())
		{
			return null;
		}

		TimelineEntry::registerBindings($result->getId(), $bindings);

		foreach($bindings as $binding)
		{
			$tag = TimelineEntry::prepareEntityPushTag(
				$binding['ENTITY_TYPE_ID'],
				$binding['ENTITY_ID']
			);
			self::pushHistoryEntry($result->getId(), $tag, 'timeline_activity_add');
		}

		return (int)$result->getId();
	}

	/**
	 * @inheritdoc
	 */
	public function prepareHistoryDataModel(array $data, array $options = null)
	{
		$data['FIELDS'] = $data['SETTINGS']['FIELDS'];

		unset($data['SETTINGS']);

		return parent::prepareHistoryDataModel($data, $options);
	}
}
