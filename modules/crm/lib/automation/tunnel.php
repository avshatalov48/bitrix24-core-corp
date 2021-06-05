<?php

namespace Bitrix\Crm\Automation;

class Tunnel
{
	/**
	 * @param $userId
	 * @param $categoryId
	 * @return bool
	 */
	public static function canUserEditTunnel($userId, $categoryId)
	{
		return self::getDealTunnelManager()->canUserEditTunnel($userId, $categoryId);
	}

	/**
	 * @return array
	 */
	public static function getScheme()
	{
		return self::getDealTunnelManager()->getScheme();
	}

	/**
	 * @param $userId
	 * @param $srcCategory
	 * @param $srcStage
	 * @param $dstCategory
	 * @param $dstStage
	 * @return \Bitrix\Main\Result
	 */
	public static function add($userId, $srcCategory, $srcStage, $dstCategory, $dstStage)
	{
		return self::getDealTunnelManager()->addTunnel(...func_get_args());
	}

	/**
	 * @param $userId
	 * @param array $tunnel
	 * @return \Bitrix\Main\Result
	 */
	public static function remove($userId, array $tunnel)
	{
		return self::getDealTunnelManager()->removeTunnel($userId, $tunnel);
	}

	/**
	 * @param $userId
	 * @param array $tunnel
	 * @return \Bitrix\Main\Result
	 */
	public static function update($userId, array $tunnel)
	{
		return self::getDealTunnelManager()->updateTunnel($userId, $tunnel);
	}

	/**
	 * @return TunnelManager
	 */
	protected static function getDealTunnelManager(): TunnelManager
	{
		return new TunnelManager(\CCrmOwnerType::Deal);
	}
}
