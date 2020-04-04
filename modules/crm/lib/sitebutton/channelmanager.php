<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\SiteButton\Channel\iProvider;

Loc::loadMessages(__FILE__);

/**
 * Class ChannelManager
 * @package Bitrix\Crm\SiteButton
 */
class ChannelManager
{
	/**
	 * Get channel providers.
	 *
	 * @return iProvider[]
	 */
	protected static function getChannels()
	{
		/** @var iProvider[] $providers */
		$providers = array(
			'\Bitrix\Crm\SiteButton\Channel\ChannelOpenLine',
			'\Bitrix\Crm\SiteButton\Channel\ChannelWebForm',
			'\Bitrix\Crm\SiteButton\Channel\ChannelCallback',
		);

		$list = array();
		foreach ($providers as $provider)
		{
			if (!$provider::canUse())
			{
				continue;
			}

			$list[] = $provider;
		}

		return $list;
	}

	/**
	 * Get channel by type.
	 *
	 * @param string $type Type
	 * @return iProvider|null
	 */
	public static function getByType($type)
	{
		$list = self::getChannels();
		foreach ($list as $channel)
		{
			if ($channel::getType() == $type)
			{
				return $channel;
			}
		}

		return null;
	}

	/**
	 * Return true if channel can be used.
	 *
	 * @param string $type Type
	 * @return bool
	 */
	public static function canUse($type)
	{
		$channel = self::getByType($type);
		if (!$channel)
		{
			return false;
		}

		return $channel::canUse();
	}

	/**
	 * Get types.
	 *
	 * @return array
	 */
	public static function getTypes()
	{
		$result = array();
		$list = self::getChannels();
		foreach ($list as $channel)
		{
			$result[] = $channel::getType();
		}

		return $result;
	}

	/**
	 * Get type names.
	 *
	 * @return array
	 */
	public static function getTypeNames()
	{
		$result = array();
		$list = self::getChannels();
		foreach ($list as $channel)
		{
			$result[$channel::getType()] = $channel::getName();
		}

		return $result;
	}

	/**
	 * Get channel as array.
	 *
	 * @param string $type Type
	 * @return array|null
	 */
	public static function getChannelArray($type)
	{
		$channel = self::getByType($type);
		if (!$channel)
		{
			return null;
		}

		return array(
			'TYPE' => $channel::getType(),
			'NAME' => $channel::getName(),
			'PATH_LIST' => $channel::getPathList(),
			'PATH_ADD' => $channel::getPathAdd(),
			'PATH_EDIT' => $channel::getPathEdit(),
			'RESOURCES' => $channel::getResources(),
			'LIST' => $channel::getList()
		);
	}

	/**
	 * Get presets.
	 *
	 * @param string $type Type
	 * @return array
	 */
	public static function getPresets($type)
	{
		$channel = self::getByType($type);
		if (!$channel)
		{
			return array();
		}

		return $channel::getPresets();
	}

	/**
	 * Get widgets.
	 *
	 * @param string $type Type
	 * @param string $id Channel id
	 * @param bool $removeCopyright Remove copyright
	 * @param string|null $lang Language ID
	 * @param array $config Config
	 * @return array
	 */
	public static function getWidgets($type, $id, $removeCopyright = true, $lang = null, array $config = array())
	{
		$channel = self::getByType($type);
		if (!$channel)
		{
			return array();
		}

		return $channel::getWidgets($id, $removeCopyright, $lang, $config);
	}
}
