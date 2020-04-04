<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage faceid
 * @copyright  2001-2017 Bitrix
 */

namespace Bitrix\Faceid;


use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Update\Stepper;
use Bitrix\Main\UserTable;

final class ProfilePhotoIndex extends \Bitrix\Main\Update\Stepper
{
	protected static $moduleId = "faceid";

	protected static $photosPerRequestCount = 30;

	function execute(array &$result)
	{
		if (!Option::get('faceid', 'user_index_enabled', 0))
		{
			return false;
		}

		/** @var bool $isFirstRun No indexing during first run */
		$isFirstRun = false;

		// set general flag of index processing
		if (!Option::get('faceid', 'user_index_processing', 0))
		{
			$isFirstRun = true;
			Option::set('faceid', 'user_index_processing', 1);

			$r = UserTable::getList(array(
				'select' => array(new ExpressionField('CNT', 'COUNT(1)')),
				'filter' => array('=ACTIVE' => 'Y', '>PERSONAL_PHOTO' => 0)
			))->fetch();

			if (!empty($r['CNT']))
			{
				Option::set('faceid', 'user_index_total', $r['CNT']);
			}
		}

		// get current values
		$ixCurrent = Option::get('faceid', 'user_index_current', 0);
		$ixTotal = Option::get('faceid', 'user_index_total', 0);

		// check lock
		$locked = Option::get('faceid', 'user_index_lock', 0);

		if (!$locked && !$isFirstRun)
		{
			// lock
			Option::set('faceid', 'user_index_lock', 1);

			// get portion
			$portionSize = 0;

			$r = UserTable::getList(array(
				'select' => array('ID', 'PERSONAL_PHOTO'),
				'filter' => array('=ACTIVE' => 'Y', '>PERSONAL_PHOTO' => 0),
				'order' => array('ID'),
				'offset' => $ixCurrent,
				'limit' => static::$photosPerRequestCount
			));

			while ($user = $r->fetch())
			{
				UsersTable::indexUser($user);
				$portionSize++;
			}

			$ixCurrent += $portionSize;

			// finalize indexing
			$final = $portionSize < static::$photosPerRequestCount;

			if (!$final)
			{
				Option::set('faceid', 'user_index_current', $ixCurrent);
			}
			else
			{
				Option::set('faceid', 'user_indexed', 1);

				Option::delete('faceid', array('name' => 'user_index_processing'));
				Option::delete('faceid', array('name' => 'user_index_current'));
				Option::delete('faceid', array('name' => 'user_index_total'));
			}

			// free lock
			Option::set('faceid', 'user_index_lock', 0);
		}

		$result["steps"] = $ixCurrent;
		$result["count"] = $ixTotal;

		return empty($final) ? true : false;
	}

	public static function bindCustom($delay = 60)
	{
		/** @var Stepper $c */
		$c = get_called_class();

		\CAgent::AddAgent(
			$c.'::execAgent();',
			$c::getModuleId(),
			"Y",
			1,
			"",
			"Y",
			\ConvertTimeStamp(time()+\CTimeZone::GetOffset() + (int) $delay, "FULL"),
			100,
			false,
			false
		);

		if (empty($delay))
		{
			$c::execAgent();
		}
	}
}