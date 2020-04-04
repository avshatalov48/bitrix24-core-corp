<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Crm\Communication;

/**
 * Class Pool
 *
 * @package Bitrix\Crm\Tracking
 */
class Pool
{
	protected static $instance;

	/**
	 * Return instance.
	 *
	 * @return static
	 */
	public static function instance()
	{
		if (!self::$instance)
		{
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Append item to pool.
	 *
	 * @param int $typeId Type ID.
	 * @param string $value Value.
	 * @return bool
	 */
	public function addItem($typeId, $value)
	{
		return Internals\PoolTable::appendPoolItem($typeId, $value);
	}

	/**
	 * Remove item to pool.
	 *
	 * @param int $typeId Type ID.
	 * @param string $value Value.
	 * @return bool
	 */
	public function removeItem($typeId, $value)
	{
		return Internals\PoolTable::removePoolItem($typeId, $value);
	}

	/**
	 * Get items.
	 *
	 * @param int $typeId Type ID.
	 * @return array
	 */
	public function getItems($typeId)
	{
		switch ($typeId)
		{
			case Communication\Type::EMAIL:
				return $this->getEmails();

			case Communication\Type::PHONE:
				return $this->getPhones();

			default:
				throw new ArgumentException("Unknown type `$typeId`.");
		}
	}

	/**
	 * Get using by number.
	 *
	 * @param int $typeId Type ID.
	 * @param string $value Value.
	 * @return array
	 */
	public function getUsingByValue($typeId, $value)
	{
		switch ($typeId)
		{
			case Communication\Type::EMAIL:
				return [];

			case Communication\Type::PHONE:
				return Internals\PhoneNumberTable::getUsingByNumber($value);

			default:
				throw new ArgumentException("Unknown type `$typeId`.");
		}
	}

	/**
	 * Get phones.
	 *
	 * @return array
	 */
	public function getPhones()
	{
		$list = [];

		if (Loader::includeModule('voximplant'))
		{
			$numbers = \CVoxImplantConfig::GetLinesEx([
				"showRestApps" => true,
				"showInboundOnly" => true
			]);
			foreach ($numbers as $numberCode => $numberData)
			{
				$numberName = $numberData['SHORT_NAME']; // TODO: change to LINE_NUMBER when normalization will disabled
				if (!$numberCode || !$numberName)
				{
					continue;
				}

				$numberName = Communication\Normalizer::normalizePhone($numberName);
				if (!Communication\Validator::validatePhone($numberName))
				{
					continue;
				}

				$list[] = [
					'NAME' => $numberName,
					'VALUE' => $numberName,
					'CAN_REMOVE' => false
				];
			}
		}

		$userNumbers = Internals\PoolTable::getPoolItemsByTypeId(Communication\Type::PHONE);
		foreach ($userNumbers as $numberCode)
		{
			$list[] = [
				'NAME' => null,
				'VALUE' => $numberCode,
				'CAN_REMOVE' => true
			];
		}

		foreach ($list as $index => $item)
		{
			$item['USING'] = $this->getUsingByValue(Communication\Type::PHONE, $item['VALUE']);
			$list[$index] = $item;
		}

		return $list;
	}

	/**
	 * Get emails.
	 *
	 * @return array
	 */
	public function getEmails()
	{
		$emails = Internals\PoolTable::getPoolItemsByTypeId(Communication\Type::EMAIL);

		$list = [];
		foreach ($emails as $email)
		{
			$list[] = [
				'NAME' => null,
				'VALUE' => $email,
				'CAN_REMOVE' => true
			];
		}

		return $list;
	}
}