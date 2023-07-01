<?php

namespace Bitrix\Crm\Multifield;

use Bitrix\Crm\Multifield\Type\Email;
use Bitrix\Crm\Multifield\Type\Im;
use Bitrix\Crm\Multifield\Type\Link;
use Bitrix\Crm\Multifield\Type\Phone;
use Bitrix\Crm\Multifield\Type\Web;

final class TypeRepository
{
	public static function isTypeDefined($typeId): bool
	{
		foreach (self::getAll() as $type)
		{
			if ((string)$typeId === $type::ID)
			{
				return true;
			}
		}

		return false;
	}

	public static function getTypeCaption($typeId): string
	{
		foreach (self::getAll() as $type)
		{
			if ((string)$typeId === $type::ID)
			{
				return $type::getCaption();
			}
		}

		return '';
	}

	/**
	 * @return Type[]
	 */
	private static function getAll(): array
	{
		return [
			new Email(),
			new Im(),
			new Link(),
			new Phone(),
			new Web(),
		];
	}

	private function __construct()
	{
	}
}
