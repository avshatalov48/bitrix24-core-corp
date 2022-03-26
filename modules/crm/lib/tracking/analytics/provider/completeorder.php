<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics\Provider;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;

/**
 * Class CompleteOrder
 * @package Bitrix\Crm\Tracking\Analytics\Provider
 */
class CompleteOrder extends Order
{
	/**
	 * Get code.
	 *
	 * @return string
	 */
	public function getCode()
	{
		return 'deals-success';
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Loc::getMessage('CRM_TRACKING_ANALYTICS_PROVIDER_NAME_ORDERS_SUCCESS');
	}

	/**
	 * Get entity ID.
	 *
	 * @return int|null
	 */
	public function getEntityId()
	{
		return \CCrmOwnerType::Order;
	}

	/**
	 * Get entity name.
	 *
	 * @return string|null
	 */
	public function getEntityName()
	{
		return \CCrmOwnerType::getCategoryCaption($this->getEntityId());
	}

	/**
	 * Get path.
	 *
	 * @return string
	 */
	public function getPath()
	{
		$statusId = $this->canUse() ? Sale\OrderStatus::getFinalStatus() : 'F';
		return '/shop/orders/list/?STATUS_ID=' . $statusId . '&apply_filter=Y';
	}

	/**
	 * Query data.
	 *
	 * @return array
	 */
	public function query()
	{
		if (!$this->canUse())
		{
			return [];
		}

		$query = $this->getOrderQuery();
		$query->addFilter('STATUS_ID', Sale\OrderStatus::getFinalStatus());
		return $this->performQuery(
			$query,
			\CCrmOwnerType::Order,
			[
				'dateFieldName' => 'DATE_INSERT',
				'assignedByFieldName' => 'RESPONSIBLE_ID',
			]
		);
	}
}