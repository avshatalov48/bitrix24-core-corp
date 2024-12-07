<?php
declare(strict_types=1);

namespace Bitrix\Disk\ProxyType;

/**
 * Class Type
 * Describes types of storage.
 */
enum Type: string
{
	case USER = 'user';
	case GROUP = 'group';
	case COMMON = 'common';

	case REST = 'rest';

	case CUSTOM = 'custom';

	/**
	 * Returns type by proxy type class name.
	 * @param string $proxyType Proxy type class name.
	 * @return Type
	 */
	public static function tryFromProxyTypeClass(string $proxyType): Type
	{
		return match ($proxyType)
		{
			User::class => self::USER,
			Group::class => self::GROUP,
			Common::class => self::COMMON,
			RestApp::class => self::REST,
			default => self::CUSTOM,
		};
	}

	/**
	 * Returns type by proxy type.
	 * @param Base $type Proxy type.
	 * @return Type
	 */
	public static function tryFromProxyType(Base $type): Type
	{
		return self::tryFromProxyTypeClass($type::class);
	}
}