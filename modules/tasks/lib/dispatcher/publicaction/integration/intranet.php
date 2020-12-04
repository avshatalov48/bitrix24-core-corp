<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Integration;

use Bitrix\Main;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Dispatcher\PublicAction;

/**
 * Class Intranet
 *
 * @package Bitrix\Tasks\Dispatcher\PublicAction\Integration
 */
final class Intranet extends PublicAction
{
	/**
	 * @param array $userIds
	 * @return array|false
	 * @throws Main\LoaderException
	 */
	public static function absence(array $userIds)
	{
		return User::isAbsence($userIds);
	}
}