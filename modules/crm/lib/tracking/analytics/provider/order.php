<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics\Provider;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Orm;
use Bitrix\Sale;
use Bitrix\Crm;

/**
 * Class Order
 * @package Bitrix\Crm\Tracking\Analytics\Provider
 */
class Order extends Base
{
	/**
	 * Get code.
	 *
	 * @return string
	 */
	public function getCode()
	{
		return 'deals';
	}

	/**
	 * Get name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Loc::getMessage('CRM_TRACKING_ANALYTICS_PROVIDER_NAME_ORDERS');
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
		return '/shop/orders/list/';
	}

	/**
	 * Return true if can use.
	 *
	 * @return bool
	 */
	public function canUse()
	{
		return Main\Loader::includeModule('sale');
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

		return $this->performQuery(
			$this->getOrderQuery(),
			\CCrmOwnerType::Order,
			[
				'dateFieldName' => 'DATE_INSERT',
				'assignedByFieldName' => 'RESPONSIBLE_ID',
			]
		);
	}

	protected function getOrderQuery()
	{
		$query = Sale\OrderTable::query();
		$query->registerRuntimeField(new Orm\Fields\Relations\Reference(
			'CRM_ORDER',
			Crm\Binding\OrderContactCompanyTable::class,
			['=this.ID' => 'ref.ORDER_ID'],
			[
				//'join_type' => Orm\Query\Join::TYPE_INNER
			]
		));

		$query->registerRuntimeField(new Orm\Fields\ExpressionField(
			'ACCOUNT_CURRENCY_ID', '%s', ['CURRENCY']
		));
		$query->registerRuntimeField(new Orm\Fields\ExpressionField(
			'OPPORTUNITY_ACCOUNT', '%s', ['PRICE']
		));

		if ($this->isGroupedByAssigned())
		{
			$query->addSelect('RESPONSIBLE_ID', 'ASSIGNED_BY_ID');
		}


		return $query;
	}

	public function getData()
	{
		return parent::getData();
	}
}