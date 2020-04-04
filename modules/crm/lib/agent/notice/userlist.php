<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2018 Bitrix
 */

namespace Bitrix\Crm\Agent\Notice;

use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\ContactTable;
use Bitrix\Crm\LeadTable;

/**
 * Class UserList
 *
 * @package Bitrix\Crm\Agent\Notice
 */
class UserList
{
	/**
	 * Get portal admins.
	 *
	 * @return array
	 */
	public static function getPortalAdmins()
	{
		if (Loader::includeModule('bitrix24'))
		{
			return \CBitrix24::getAllAdminId();
		}
		else
		{
			return [];
		}
	}

	/**
	 * Get lead assigned users.
	 *
	 * @param int $daysLeft Days left.
	 * @return array
	 */
	public static function getLeadAssignedUsers($daysLeft = 30)
	{

		return self::getQueryAssignedUsers(LeadTable::query(), $daysLeft);
	}

	/**
	 * Get company assigned users.
	 *
	 * @param int $daysLeft Days left.
	 * @return array
	 */
	public static function getCompanyAssignedUsers($daysLeft = 30)
	{
		return self::getQueryAssignedUsers(CompanyTable::query(), $daysLeft);
	}

	/**
	 * Get contact assigned users.
	 *
	 * @param int $daysLeft Days left.
	 * @return array
	 */
	public static function getContactAssignedUsers($daysLeft = 30)
	{
		return self::getQueryAssignedUsers(ContactTable::query(), $daysLeft);
	}

	protected static function getQueryAssignedUsers(Query $query, $daysLeft = 30)
	{
		$result = [];
		if ($daysLeft)
		{
			$query->addFilter(
				'>DATE_CREATE',
				(new DateTime())->add("-$daysLeft days")
			);
		}
		$list = $query->addSelect('ASSIGNED_BY_ID')->addGroup('ASSIGNED_BY_ID')->exec();
		foreach ($list as $item)
		{
			if ($item['ASSIGNED_BY_ID'])
			{
				$result[] = (int) $item['ASSIGNED_BY_ID'];
			}
		}

		return $result;
	}
}