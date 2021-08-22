<?php

namespace Bitrix\Crm\Order\SendingChannels;

use Bitrix\Crm\Order;
use Bitrix\Main\ORM;
use Bitrix\Main\Entity;
use Bitrix\Main;

class Manager
{
	/**
	 * @return ORM\Entity
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected function getEntity() : ORM\Entity
	{
		return Internals\SendingChannelsTable::getEntity();
	}

	/**
	 * @param Order\Order $order
	 * @param AbstractChannel $channel
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function bindChannelToOrder(Order\Order $order, AbstractChannel $channel) : bool
	{
		$query = new ORM\Query\Query($this->getEntity());
		$query->addSelect('ID');
		$query->where(
			Entity\Query::filter()
				->where('ENTITY_TYPE', '=', $order::getRegistryEntity())
				->where('ENTITY_ID', '=', $order->getId())
		);

		$data = $query->fetch();
		if ($data)
		{
			Internals\SendingChannelsTable::delete($data['ID']);
		}

		$res = Internals\SendingChannelsTable::add([
			'CHANNEL_TYPE' => $channel::getType(),
			'CHANNEL_NAME' => $channel->getName(),
			'ENTITY_TYPE' => $order::getRegistryEntity(),
			'ENTITY_ID' => $order->getId(),
		]);

		return $res->isSuccess();
	}

	/**
	 * @param Order\Order $order
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getChannelList(Order\Order $order) : array
	{
		$query = new ORM\Query\Query($this->getEntity());

		$query->addSelect('*');
		$query->where(
			Entity\Query::filter()
				->where('ENTITY_TYPE', '=', $order::getRegistryEntity())
				->where('ENTITY_ID', '=', $order->getId())
		);

		return $query->fetch() ?: [];
	}
}