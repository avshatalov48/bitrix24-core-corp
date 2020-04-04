<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2018 Bitrix
 */
namespace Bitrix\Crm\Tracking\Analytics\Provider;

use Bitrix\Crm;


class Deal extends Base
{
	public function getCode()
	{
		return 'deals';
	}

	public function getPath()
	{
		return '/crm/deal/list/';
	}

	public function query()
	{
		$query = Crm\DealTable::query();
		return $this->performQuery($query, \CCrmOwnerType::Deal);
	}
}