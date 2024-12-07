<?php

namespace Bitrix\Crm\Order;

use Bitrix\Sale\Helpers\Order\Builder\BuildingException;
use Bitrix\Sale\Helpers\Order\Builder\SettingsContainer;
use Bitrix\Crm\Order\Builder\OrderBuilderCrm;
use Bitrix\Crm\ClientInfo;
use Bitrix\Crm\Order\TradingPlatform\DynamicEntity;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Context;
use CCrmOwnerType;

/**
 * Creator of orders from entities.
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
	private int $ownerId;

	/**
	 * @var int
	 */
	private int $ownerTypeId;

	/**
	 * Get the order id created from this hit by the entity.
	 *
	 * @param int $ownerId
	 * @param int $ownerTypeId
	 *
	 * @return int|null
	 */
	public static function getCreatedOrderId(int $ownerId, int $ownerTypeId = CCrmOwnerType::Deal): ?int
	{
		return self::$createdOrders[$ownerTypeId][$ownerId] ?? null;
	}

	/**
	 * @param int $ownerId
	 * @param int $ownerTypeId
	 */
	public function __construct(int $ownerId, int $ownerTypeId = CCrmOwnerType::Deal)
	{
		$this->ownerId = $ownerId;
		$this->ownerTypeId = $ownerTypeId;
	}

	/**
	 * Build order from entity.
	 *
	 * @param int|null $orderId
	 *
	 * @return Order|null
	 */
	public function build(?int $orderId = null): ?Order
	{
		$settings = new SettingsContainer([
			'createDefaultPaymentIfNeed' => false,
			'createDefaultShipmentIfNeed' => false,
			'clearReservesIfEmpty' => false,
			'createUserIfNeed' => SettingsContainer::SET_ANONYMOUS_USER,
		]);
		$builder = new OrderBuilderCrm($settings);

		$entityFields = $this->getEntityFields();
		$userId = (int)($entityFields['ASSIGNED_BY_ID'] ?? 0);
		$currencyId = (string)($entityFields['CURRENCY_ID'] ?? '');

		try
		{
			$fields = [
				'ID' => $orderId,
				'SITE_ID' => Context::getCurrent()->getSite(),
				'USER_ID' => $userId,
				'RESPONSIBLE_ID' => $userId,
				'OWNER_ID' => $this->ownerId,
				'OWNER_TYPE_ID' => $this->ownerTypeId,
			];

			if (!empty($currencyId))
			{
				$fields['CURRENCY'] = $currencyId;
			}

			// if order exist - don't changes part of the fields.
			if ($orderId)
			{
				$fields['CLIENT'] = ClientInfo::createFromOwner(CCrmOwnerType::Order, $orderId)->toArray(false);

				unset(
					$fields['USER_ID'],
					$fields['CURRENCY']
				);
			}
			else
			{
				$fields['TRADING_PLATFORM'] = $this->getDealTradingPlatformId();
				$fields['CLIENT'] = ClientInfo::createFromOwner($this->ownerTypeId, $this->ownerId)->toArray(false);
			}

			$builder->build($fields);

			/**
			 * @var Order $order
			 */
			$order = $builder->getOrder();
			if ($order)
			{
				$order->getContactCompanyCollection()->disableAutoCreationMode();
			}

			if (
				$orderId > 0
				&& !empty($currencyId)
				&& $currencyId !== $order->getCurrency()
			)
			{
				$order->changeCurrency($currencyId);
			}

			return $order;
		}
		catch (BuildingException $e)
		{
			// pass
		}

		return null;
	}

	/**
	 * Order creater from entity.
	 *
	 * @return Order|null
	 */
	public function create(): ?Order
	{
		$order = $this->build();
		if ($order)
		{
			$result = $order->save();
			if ($result->isSuccess())
			{
				self::$createdOrders[$this->ownerTypeId][$this->ownerId] = $order->getId();

				return $order;
			}
		}

		return null;
	}

	/**
	 * Update exist order from entity.
	 *
	 * @param int $orderId
	 *
	 * @return Order|null
	 */
	public function update(int $orderId): ?Order
	{
		$order = $this->build($orderId);
		if ($order)
		{
			$result = $order->save();
			if ($result->isSuccess())
			{
				return $order;
			}
		}

		return null;
	}

	/**
	 * Gets entity fields for order builder.
	 *
	 * @return array|null
	 */
	private function getEntityFields(): ?array
	{
		$result = [];

		$factory = Container::getInstance()->getFactory($this->ownerTypeId);
		if ($factory)
		{
			$item = $factory->getItem($this->ownerId, ['ASSIGNED_BY_ID', 'CURRENCY_ID']);
			if ($item && $item->hasField('ASSIGNED_BY_ID'))
			{
				$result['ASSIGNED_BY_ID'] = (int)$item->getAssignedById();
			}

			if ($item && $item->hasField('CURRENCY_ID'))
			{
				$result['CURRENCY_ID'] = $item->getCurrencyId();
			}
		}

		return $result;
	}

	/**
	 * Trading platform for deal.
	 *
	 * @return int|null
	 */
	private function getDealTradingPlatformId(): ?int
	{
		$code = DynamicEntity::getCodeByEntityTypeId(CCrmOwnerType::Deal);
		$platform = DynamicEntity::getInstanceByCode($code);

		if (!$platform->isInstalled())
		{
			if (!$platform->install())
			{
				return null;
			}

			$platform = DynamicEntity::getInstanceByCode($code);
			$platform->setActive();
		}

		return (int)$platform->getId();
	}
}
