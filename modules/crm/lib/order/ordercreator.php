<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale\Helpers\Order\Builder\BuildingException;
use Bitrix\Sale\Helpers\Order\Builder\SettingsContainer;
use Bitrix\Crm\Order\Builder\OrderBuilderCrm;
use Bitrix\Crm\ClientInfo;
use Bitrix\Crm\DealTable;
use Bitrix\Main\Context;
use CCrmOwnerType;

/**
 * Creator of orders from deals.
 */
class OrderCreator
{
	/**
	 * History of created orders.
	 *
	 * Stores only the successful saving of orders.
	 *
	 * @var array
	 */
	private static array $createdOrders = [];

	/**
	 * @var int
	 */
	private int $dealId;

	/**
	 * Get the order id created from this hit by the deal.
	 *
	 * @param int $dealId
	 *
	 * @return int|null
	 */
	public static function getCreatedOrderId(int $dealId): ?int
	{
		return self::$createdOrders[$dealId] ?? null;
	}

	/**
	 * @param int $dealId
	 */
	public function __construct(int $dealId)
	{
		$this->dealId = $dealId;
	}

	/**
	 * Order creater from deal.
	 *
	 * @param int $dealId
	 *
	 * @return Order|null
	 */
	public function create(): ?Order
	{
		$settings = new SettingsContainer([
			'createDefaultPaymentIfNeed' => false,
			'createDefaultShipmentIfNeed' => false,
			'clearReservesIfEmpty' => false,
			'createUserIfNeed' => SettingsContainer::SET_ANONYMOUS_USER,
		]);
		$builder = new OrderBuilderCrm($settings);

		$dealFields = $this->getDealFields();
		$userId = (int)($dealFields['ASSIGNED_BY_ID'] ?? 0);
		$currencyId = (string)($dealFields['CURRENCY_ID'] ?? '');

		try
		{
			$clientInfo = ClientInfo::createFromOwner(
				CCrmOwnerType::Deal,
				$this->dealId
			)->toArray(false);

			$builder->build([
				'SITE_ID' => Context::getCurrent()->getSite(),
				'USER_ID' => $userId,
				'CURRENCY' => $currencyId,
				'OWNER_ID' => $this->dealId,
				'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
				'CLIENT' => $clientInfo,
			]);

			/**
			 * @var Order $order
			 */
			$order = $builder->getOrder();
			if ($order)
			{
				$order->getContactCompanyCollection()->disableAutoCreationMode();

				$result = $order->save();
				if ($result->isSuccess())
				{
					self::$createdOrders[$this->dealId] = $order->getId();

					return $order;
				}
			}
		}
		catch (BuildingException $e)
		{
			// pass
		}

		return null;
	}

	/**
	 * Gets deal fields for order builder.
	 *
	 * @param int $dealId
	 *
	 * @return array|null
	 */
	private function getDealFields(): ?array
	{
		return DealTable::getRow([
			'select' => [
				'ASSIGNED_BY_ID',
				'CURRENCY_ID',
			],
			'filter' => [
				'=ID' => $this->dealId,
			],
		]);
	}
}
