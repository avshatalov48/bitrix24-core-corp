<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Source\Level;

use Bitrix\Main\Localization\Loc;

use Bitrix\Crm\Tracking;

Loc::loadMessages(__FILE__);

/**
 * Class Type
 *
 * @package Bitrix\Crm\Tracking\Source\Level
 */
class Type
{
	const Campaign = 0;
	const Adgroup = 1;
	const Keyword = 100;

	/**
	 * Get level sequence.
	 *
	 * @return int[]
	 */
	public static function getSequence()
	{
		return [
			self::Campaign,
			self::Adgroup,
			self::Keyword,
		];
	}

	/**
	 * Get next ID.
	 *
	 * @param int $id ID.
	 * @return int|null
	 */
	public static function getNextId($id)
	{
		$id = (int) $id;
		$goal = false;
		foreach (self::getSequence() as $currentId)
		{
			if ($goal)
			{
				return $currentId;
			}

			$goal = $id === $currentId;
		}

		return null;
	}

	/**
	 * Get prev ID.
	 *
	 * @param int $id ID.
	 * @return int|null
	 */
	public static function getPrevId($id)
	{
		$id = (int) $id;
		$prev = self::Campaign;
		foreach (self::getSequence() as $currentId)
		{
			if ($id === $currentId)
			{
				return $prev;
			}

			$prev = $currentId;
		}

		return null;
	}

	/**
	 * Get level captions.
	 *
	 * @return array
	 */
	public static function getCaptions()
	{
		$list = [];
		foreach (static::getSequence() as $id)
		{
			$list[$id] = static::getCaption($id);
		}

		return $list;
	}

	protected static function resolveCode($id)
	{
		switch ($id)
		{
			case self::Campaign:
				return 'CAMPAIGN';

			case self::Adgroup:
				return 'ADGROUP';

			case self::Keyword:
			default:
				return 'AD';
		}
	}

	/**
	 * Get level caption.
	 *
	 * @param int $id Level ID.
	 * @param string $sourceCode Source code.
	 * @return string
	 */
	public static function getCaption($id, $sourceCode = null)
	{
		$code = 'CRM_TRACKING_SOURCE_LEVEL_CAPTION_' . static::resolveCode($id);
		return Loc::getMessage($code . '_' . strtoupper($sourceCode)) ?: Loc::getMessage($code);
	}
}