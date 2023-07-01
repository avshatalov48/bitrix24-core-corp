<?php

namespace Bitrix\Market\Integration;

/**
 * class TagHandlerInterface
 *
 * @package Bitrix\Market\Integration
 */
interface TagHandlerInterface
{
	/**
	 * Returns tags list.
	 *
	 * @return array
	 */
	public static function list(): array;
}